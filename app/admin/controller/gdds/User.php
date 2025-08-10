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
     * 重写获取列表方法，适度更新过期用户状态
     */
    public function index(Request $request): Json|string
    {
        if ($request->isAjax()) {
            // 强制自动更新所有过期和未开通用户状态
            \app\admin\model\GddsUser::autoUpdateStatus();
            
            // 强制刷新所有用户状态，确保数据一致性
            \app\admin\model\GddsUser::forceRefreshAllStatus();
            
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
            
            // 直接查询，避免过度的状态重置
            $query = self::$model::where($where);
            if (!empty($order)) {
                $query = $query->order($order);
            }
            if ($page && $limit) {
                $list = $query->page($page, $limit)->select();
            } elseif ($limit) {
                $list = $query->limit($limit)->select();
            } else {
                $list = $query->select();
            }
            
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
                // 处理VIP时间逻辑，自动设置状态
                if (!empty($post['vip_off_time'])) {
                    $vipTime = strtotime($post['vip_off_time']);
                    if ($vipTime === false) {
                        $this->error('VIP时间格式不正确');
                    }
                    if ($vipTime <= time()) {
                        $this->error('VIP到期时间不能是过去的时间');
                    }
                    $post['vip_off_time'] = $vipTime;
                    $post['status'] = 2; // 设置为启用状态
                } else {
                    // 没有VIP时间，设置为禁用状态
                    $post['vip_off_time'] = 0;
                    $post['status'] = 1; // 设置为禁用状态
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
                // 处理VIP时间逻辑，自动设置状态
                \think\facade\Log::info('VIP时间处理调试：', [
                    'raw_vip_off_time' => $post['vip_off_time'] ?? null,
                    'is_empty' => empty($post['vip_off_time']),
                    'value_type' => gettype($post['vip_off_time'] ?? null),
                    'post_data' => $post,
                    'user_id' => $id
                ]);
                
                if (!empty($post['vip_off_time']) && $post['vip_off_time'] !== '0') {
                    // 简化的VIP时间验证逻辑
                    $vipTime = $post['vip_off_time'];
                    
                    \think\facade\Log::info('后端调试 - 接收到的VIP时间数据：', [
                        'original_value' => $vipTime,
                        'type' => gettype($vipTime),
                        'is_numeric' => is_numeric($vipTime),
                        'is_string' => is_string($vipTime)
                    ]);
                    
                    // 统一处理：先转为整数时间戳
                    if (is_numeric($vipTime)) {
                        $vipTime = intval($vipTime);
                    } else {
                        // 如果不是数字，尝试字符串解析
                        $vipTime = strtotime($vipTime);
                        if ($vipTime === false) {
                            \think\facade\Log::error('VIP时间格式错误：无法解析', [
                                'original' => $post['vip_off_time']
                            ]);
                            $this->error('VIP时间格式不正确');
                        }
                    }
                    
                    \think\facade\Log::info('后端调试 - VIP时间转换结果：', [
                        'original' => $post['vip_off_time'],
                        'converted' => $vipTime,
                        'current_time' => time(),
                        'is_future' => $vipTime > time()
                    ]);
                    
                    // 验证时间戳有效性
                    if ($vipTime <= 0) {
                        \think\facade\Log::error('VIP时间无效：时间戳为0或负数', [
                            'converted' => $vipTime,
                            'original' => $post['vip_off_time']
                        ]);
                        $this->error('VIP时间格式不正确');
                    }
                    
                    // 检查是否是未来时间（允许等于当前时间，给一些容错）
                    $currentTime = time();
                    if ($vipTime < $currentTime) {
                        \think\facade\Log::error('VIP时间是过去时间', [
                            'vip_time' => $vipTime,
                            'current_time' => $currentTime,
                            'vip_date' => date('Y-m-d H:i:s', $vipTime),
                            'current_date' => date('Y-m-d H:i:s', $currentTime)
                        ]);
                        $this->error('VIP到期时间不能是过去的时间');
                    }
                    
                    $post['vip_off_time'] = $vipTime;
                    $post['status'] = 2; // 设置为启用状态
                } else {
                    // 没有VIP时间，设置为禁用状态
                    $post['vip_off_time'] = 0;
                    $post['status'] = 1; // 设置为禁用状态
                }
                
                // 记录调试信息
                \think\facade\Log::info('保存用户数据：', [
                    'vip_off_time' => $post['vip_off_time'],
                    'status' => $post['status'],
                    'current_time' => time()
                ]);
                
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

    /**
     * 重写状态修改方法，处理开关值映射
     * 开关值：0 -> 禁用(1), 1 -> 启用(2)
     */
    public function modify(Request $request): void
    {
        $this->checkPostRequest();
        $post = $request->post();
        $rule = [
            'id|ID'      => 'require',
            'field|字段' => 'require',
            'value|值'   => 'require',
        ];
        $this->validate($post, $rule);
        
        // 限制可修改的字段
        $allowModifyFields = ['status', 'sort', 'remark'];
        if (!in_array($post['field'], $allowModifyFields)) {
            $this->error('该字段不允许修改：' . $post['field']);
        }
        
        $row = self::$model::find($post['id']);
        empty($row) && $this->error('数据不存在');
        
        try {
            // 如果是状态字段，需要处理VIP时间检查
            if ($post['field'] === 'status') {
                // 获取用户VIP状态
                $vipOffTime = $row->getData('vip_off_time');
                
                // 记录详细的VIP和状态信息
                \think\facade\Log::info(sprintf(
                    '状态修改请求：用户ID=%s, 当前状态=%s, 请求状态=%s, VIP时间=%s',
                    $post['id'],
                    $row->getData('status'),
                    $post['value'],
                    $vipOffTime
                ));
                
                // 检查是否允许修改状态
                $canChangeStatus = true;
                $reason = '';
                
                // 如果要启用（状态值=2），需要检查VIP时间
                if ($post['value'] == 2) {
                    if (empty($vipOffTime) || $vipOffTime == 0) {
                        $canChangeStatus = false;
                        $reason = 'VIP未开通';
                        $post['value'] = 1; // 强制设置为禁用
                    } elseif ($vipOffTime < time()) {
                        $canChangeStatus = false;
                        $reason = 'VIP已过期';
                        $post['value'] = 1; // 强制设置为禁用
                    }
                }
                
                // 记录状态检查结果
                \think\facade\Log::info(sprintf(
                    '状态检查结果：允许修改=%s, 原因=%s, 最终状态值=%s',
                    $canChangeStatus ? 'true' : 'false',
                    $reason ?: '无限制',
                    $post['value']
                ));
                
                if (!$canChangeStatus) {
                    $this->error($reason . '，不能启用账号', [], 0);
                }
            }
            
            // 记录更新前的状态
            \think\facade\Log::info(sprintf(
                '更新前状态：ID=%s, 当前状态=%s, 新状态=%s',
                $post['id'],
                $row->getData('status'),
                $post['value']
            ));
            
            try {
                // 使用模型更新
                $updateResult = $row->save([
                    $post['field'] => $post['value']
                ]);
                
                // 记录更新结果
                \think\facade\Log::info(sprintf(
                    '更新结果：成功=%s, SQL=%s',
                    $updateResult ? 'true' : 'false',
                    \think\facade\Db::getLastSql()
                ));
                
                // 重新获取并验证更新
                $afterUpdate = self::$model::find($post['id']);
                if ($afterUpdate) {
                    \think\facade\Log::info(sprintf(
                        '更新后状态：ID=%s, 状态=%s, VIP时间=%s',
                        $afterUpdate['id'],
                        $afterUpdate->getData('status'),
                        $afterUpdate->getData('vip_off_time')
                    ));
                    
                    // 验证更新是否成功
                    if ($afterUpdate->getData('status') != $post['value']) {
                        throw new \Exception('状态更新失败，数据库值未改变');
                    }
                } else {
                    throw new \Exception('更新后未找到记录');
                }
            } catch (\Exception $e) {
                \think\facade\Log::error('状态更新异常：' . $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('保存成功');
    }

}