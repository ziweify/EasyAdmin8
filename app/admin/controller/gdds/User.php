<?php

namespace app\admin\controller\gdds;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;
use think\Request;
use think\response\Json;

#[ControllerAnnotation(title: 'gdds_user')]
class User extends AdminController
{

    private array $notes;

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = new \app\admin\model\GddsUser();
        $notes = self::$model::$notes;
        
        $this->notes =$notes;
        $this->assign(compact('notes'));
    }

    /**
     * 重写获取列表方法，自动更新过期用户状态
     */
    public function index(Request $request): Json|string
    {
        if ($request->isAjax()) {
            // 自动更新过期用户状态
            \app\admin\model\GddsUser::autoUpdateStatus();
            
            if (input('selectFields')) {
                return $this->selectList();
            }
            
            list($page, $limit, $where) = $this->buildTableParams();
            $count = self::$model::where($where)->count();
            
            // 使用模型的方法自动处理状态一致性
            $sort = $this->sort;
            $order = '';
            if (!empty($sort)) {
                $field = array_key_first($sort);
                $direction = $sort[$field];
                $order = "{$field} {$direction}";
            }
            $list = self::$model::getListWithStatusCheck($where, $order, $limit, $page);
            
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list->toArray(),
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 重写添加方法，确保方法签名兼容
     */
    public function add(Request $request): string
    {
        if ($request->isPost()) {
            $post = $request->post();
            
            // 添加表单验证规则
            $rule = [
                'username' => 'require|length:2,20',
                'password' => 'require|length:6,20',
                'soft_version' => 'require',
                'allow_window' => 'require|number|between:1,100',
                'api_public_key' => 'require',
                'api_private_key' => 'require',
                'api_token' => 'require',
                'status' => 'require|in:1,2',
                'sort' => 'number',
                'carday_consumption' => 'integer|egt:0',
                'carweek_consumption' => 'integer|egt:0',
                'carmonth_consumption' => 'integer|egt:0',
            ];
            
            $message = [
                'username.require' => '用户名不能为空',
                'username.length' => '用户名长度应在2-20位之间',
                'password.require' => '密码不能为空',
                'password.length' => '密码长度应在6-20位之间',
                'soft_version.require' => '软件版本不能为空',
                'allow_window.require' => '窗口数量不能为空',
                'allow_window.number' => '窗口数量必须是数字',
                'allow_window.between' => '窗口数量应在1-100之间',
                'api_public_key.require' => 'API公钥不能为空',
                'api_private_key.require' => 'API私钥不能为空',
                'api_token.require' => 'API令牌不能为空',
                'status.require' => '状态不能为空',
                'status.in' => '状态值无效',
                'sort.number' => '排序必须是数字',
                'carday_consumption.integer' => '日卡消费必须是整数',
                'carday_consumption.egt' => '日卡消费不能为负数',
                'carweek_consumption.integer' => '周卡消费必须是整数',
                'carweek_consumption.egt' => '周卡消费不能为负数',
                'carmonth_consumption.integer' => '月卡消费必须是整数',
                'carmonth_consumption.egt' => '月卡消费不能为负数',
            ];
            
            $this->validate($post, $rule, $message);
            
            try {
                // 处理VIP状态逻辑
                if (isset($post['vip_status'])) {
                    if ($post['vip_status'] == '0') {
                        // 未开通VIP
                        $post['vip_off_time'] = 0;
                        $post['status'] = 1; // 强制设置为禁用状态
                    } else {
                        // 开通VIP，验证时间
                        if (empty($post['vip_off_time'])) {
                            $this->error('请选择VIP到期时间');
                        }
                        $vipTime = strtotime($post['vip_off_time']);
                        if ($vipTime === false) {
                            $this->error('VIP时间格式不正确');
                        }
                        if ($vipTime <= time()) {
                            $this->error('VIP到期时间不能是过去的时间');
                        }
                        $post['vip_off_time'] = $vipTime;
                        $post['status'] = 2; // 设置为启用状态
                    }
                    unset($post['vip_status']); // 移除临时字段
                }
                
                // 设置默认值
                if (!isset($post['sort'])) {
                    $post['sort'] = 0;
                }
                if (!isset($post['carday_consumption'])) {
                    $post['carday_consumption'] = 0;
                }
                if (!isset($post['carweek_consumption'])) {
                    $post['carweek_consumption'] = 0;
                }
                if (!isset($post['carmonth_consumption'])) {
                    $post['carmonth_consumption'] = 0;
                }
                
                \think\facade\Db::transaction(function() use ($post, &$save) {
                    $save = self::$model::create($post);
                });
            } catch (\Exception $e) {
                $this->error('新增失败：' . $e->getMessage());
            }
            
            $save ? $this->success('新增成功') : $this->error('新增失败');
        }
        return $this->fetch();
    }

    /**
     * 重写编辑方法，自动更新过期用户状态
     */
    public function edit(Request $request, $id = 0): string
    {
        $row = \app\admin\model\GddsUser::getUserWithStatusCheck($id);
        if (empty($row)) {
            $this->error('用户不存在');
        }
        
        if ($request->isPost()) {
            $post = $request->post();
            
            // 添加表单验证规则
            $rule = [
                'username' => 'require|length:2,20',
                'password' => 'require|length:6,20',
                'soft_version' => 'require',
                'allow_window' => 'require|number|between:1,100',
                'api_public_key' => 'require',
                'api_private_key' => 'require',
                'api_token' => 'require',
                'status' => 'require|in:1,2',
                'sort' => 'number',
                'carday_consumption' => 'integer|egt:0',
                'carweek_consumption' => 'integer|egt:0',
                'carmonth_consumption' => 'integer|egt:0',
            ];
            
            $message = [
                'username.require' => '用户名不能为空',
                'username.length' => '用户名长度应在2-20位之间',
                'password.require' => '密码不能为空',
                'password.length' => '密码长度应在6-20位之间',
                'soft_version.require' => '软件版本不能为空',
                'allow_window.require' => '窗口数量不能为空',
                'allow_window.number' => '窗口数量必须是数字',
                'allow_window.between' => '窗口数量应在1-100之间',
                'api_public_key.require' => 'API公钥不能为空',
                'api_private_key.require' => 'API私钥不能为空',
                'api_token.require' => 'API令牌不能为空',
                'status.require' => '状态不能为空',
                'status.in' => '状态值无效',
                'sort.number' => '排序必须是数字',
                'carday_consumption.integer' => '日卡消费必须是整数',
                'carday_consumption.egt' => '日卡消费不能为负数',
                'carweek_consumption.integer' => '周卡消费必须是整数',
                'carweek_consumption.egt' => '周卡消费不能为负数',
                'carmonth_consumption.integer' => '月卡消费必须是整数',
                'carmonth_consumption.egt' => '月卡消费不能为负数',
            ];
            
            $this->validate($post, $rule, $message);
            
            try {
                // 处理VIP状态逻辑
                if (isset($post['vip_status'])) {
                    if ($post['vip_status'] == '0') {
                        // 未开通VIP
                        $post['vip_off_time'] = 0;
                        $post['status'] = 1; // 强制设置为禁用状态
                    } else {
                        // 开通VIP，验证时间
                        if (empty($post['vip_off_time'])) {
                            $this->error('请选择VIP到期时间');
                        }
                        $vipTime = strtotime($post['vip_off_time']);
                        if ($vipTime === false) {
                            $this->error('VIP时间格式不正确');
                        }
                        if ($vipTime <= time()) {
                            $this->error('VIP到期时间不能是过去的时间');
                        }
                        $post['vip_off_time'] = $vipTime;
                        $post['status'] = 2; // 设置为启用状态
                    }
                    unset($post['vip_status']); // 移除临时字段
                }
                
                \think\facade\Db::transaction(function() use ($post, $row, &$save) {
                    $save = $row->save($post);
                });
            } catch (\Exception $e) {
                $this->error('保存失败：' . $e->getMessage());
            }
            
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        
        // 获取原始数据，避免获取器的影响
        $rawData = $row->getData();
        $this->assign('row', $rawData);
        return $this->fetch();
    }

    /**
     * 重写查看方法，自动更新过期用户状态
     */
    public function read(Request $request, $id = 0): string
    {
        // 获取用户信息时自动检查状态一致性
        $row = \app\admin\model\GddsUser::getUserWithStatusCheck($id);
        if (empty($row)) {
            $this->error('用户不存在');
        }
        
        // 获取原始数据，避免获取器的影响
        $rawData = $row->getData();
        $this->assign('row', $rawData);
        return $this->fetch();
    }

    /**
     * 手动恢复用户VIP状态
     */
    public function restoreVipStatus(Request $request): void
    {
        if ($request->isPost()) {
            $userId = $request->post('user_id');
            $newVipTime = $request->post('vip_off_time');
            
            if (empty($userId) || empty($newVipTime)) {
                $this->error('参数不完整');
            }
            
            $service = new \app\admin\service\VipStatusService();
            $result = $service::manuallyRestoreVipStatus($userId, $newVipTime);
            
            if ($result['success']) {
                $this->success($result['message']);
            } else {
                $this->error($result['message']);
            }
        }
        
        $this->error('请求方式错误');
    }

    /**
     * 获取即将过期的VIP用户列表
     */
    public function getExpiringVipUsers(Request $request): void
    {
        if ($request->isAjax()) {
            $days = $request->get('days', 7);
            
            $service = new \app\admin\service\VipStatusService();
            $result = $service::getExpiringSoonUsers($days);
            
            if ($result['success']) {
                $this->success('获取成功', '', $result['data']);
            } else {
                $this->error($result['message']);
            }
        }
        
        $this->error('请求方式错误');
    }

}