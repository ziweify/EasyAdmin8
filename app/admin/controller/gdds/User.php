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
            
            // 查询数据后，确保调用模型的获取器
            $list = self::$model::where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            
            // 手动调用获取器确保状态字段正确
            $list = $list->each(function($item) {
                // 手动调用状态获取器
                $item->status = $item->getStatusAttr($item->status);
                return $item;
            })->toArray();
            
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
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
            $rule = [];
            $this->validate($post, $rule);
            try {
                \think\facade\Db::transaction(function() use ($post, &$save) {
                    $save = self::$model::create($post);
                });
            }catch (\Exception $e) {
                $this->error('新增失败:' . $e->getMessage());
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
        $row = \app\admin\model\GddsUser::getUserWithAutoStatus($id);
        if (empty($row)) {
            $this->error('用户不存在');
        }
        
        if ($request->isPost()) {
            $post = $request->post();
            $rule = [];
            $this->validate($post, $rule);
            try {
                \think\facade\Db::transaction(function() use ($post, $row, &$save) {
                    $save = $row->save($post);
                });
            }catch (\Exception $e) {
                $this->error('保存失败');
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
        // 获取用户信息时自动更新状态
        $row = \app\admin\model\GddsUser::getUserWithAutoStatus($id);
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