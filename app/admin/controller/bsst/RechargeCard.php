<?php

namespace app\admin\controller\bsst;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;
use think\Request;
use think\facade\Log;
use think\facade\Db;

#[ControllerAnnotation(title: '充值卡管理')]
class RechargeCard extends AdminController
{
    private array $notes;

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = new \app\admin\model\BsstRechargeCard();
        $notes = self::$model::$notes;
        
        $this->sort = [
            'id' => 'desc',
        ];
        
        $this->notes = $notes;
        $this->assign(compact('notes'));
    }

    /**
     * 百胜系统充值卡列表
     */
    #[NodeAnnotation(title: '充值卡列表', auth: true)]
    public function index(\app\Request $request): \think\response\Json|string
    {
        if ($request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParams();
            
            // 百胜系统：不添加默认筛选条件
            $count = self::$model::where($where)->count();
            $list = self::$model::where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select()
                ->toArray();
            
            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 跟单大师充值卡列表
     */
    #[NodeAnnotation(title: '跟单大师充值卡', auth: true)]
    public function indexGdds(\app\Request $request): \think\response\Json|string
    {
        if ($request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParams();
            
            // 跟单大师：默认添加 soft_name 筛选条件
            $where[] = ['soft_name', '=', '跟单大师'];
            
            $count = self::$model::where($where)->count();
            $list = self::$model::where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select()
                ->toArray();
            
            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
            ];
            return json($data);
        }
        return $this->fetch('index_gdds');
    }

    /**
     * 创建充值卡
     */
    #[NodeAnnotation(title: '创建充值卡', auth: true)]
    public function add(Request $request): string
    {
        if ($request->isPost()) {
            $post = $request->post();
            $rule = [
                'amount' => 'require|number|gt:0',
                'quantity' => 'require|number|gt:0',
                'expire_days' => 'number|egt:0',
                'soft_name' => 'require|max:50'
            ];
            $this->validate($post, $rule);
            
            try {
                Db::transaction(function() use ($post) {
                    $expireTime = $post['expire_days'] > 0 ? time() + ($post['expire_days'] * 86400) : 0;
                    
                    for ($i = 0; $i < $post['quantity']; $i++) {
                        self::$model::create([
                            'card_no' => self::$model::generateCardNo(),
                            'card_type' => $post['card_type'] ?? 1,
                            'amount' => $post['amount'],
                            'original_amount' => $post['amount'],
                            'status' => self::$model::STATUS_UNUSED,
                            'batch_no' => $post['batch_no'] ?? '',
                            'expire_time' => $expireTime,
                            'soft_name' => $post['soft_name'],
                            'creator_id' => $this->adminUid,
                            'remark' => $post['remark'] ?? ''
                        ]);
                    }
                });
                $this->success('创建成功');
            } catch (\Exception $e) {
                $this->error('创建失败：' . $e->getMessage());
            }
        }
        return $this->fetch();
    }

    /**
     * 编辑充值卡
     */
    #[NodeAnnotation(title: '编辑充值卡', auth: true)]
    public function edit(Request $request, $id = 0): string
    {
        $row = self::$model::find($id);
        empty($row) && $this->error('数据不存在');
        
        if ($request->isPost()) {
            $post = $request->post();
            $rule = [
                'amount' => 'require|number|gt:0',
                'soft_name' => 'require|max:50'
            ];
            $this->validate($post, $rule);
            
            try {
                $row->save($post);
                $this->success('保存成功');
            } catch (\Exception $e) {
                $this->error('保存失败：' . $e->getMessage());
            }
        }
        
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * 批量创建充值卡
     */
    #[NodeAnnotation(title: '批量创建', auth: true)]
    public function batchCreate(Request $request): string
    {
        if ($request->isPost()) {
            $post = $request->post();
            $rule = [
                'amount' => 'require|number|gt:0',
                'quantity' => 'require|number|gt:0|elt:1000',
                'soft_name' => 'require|max:50'
            ];
            $this->validate($post, $rule);
            
            try {
                Db::transaction(function() use ($post) {
                    $batchNo = 'BATCH_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
                    $expireTime = $post['expire_days'] > 0 ? time() + ($post['expire_days'] * 86400) : 0;
                    
                    for ($i = 0; $i < $post['quantity']; $i++) {
                        self::$model::create([
                            'card_no' => self::$model::generateCardNo(),
                            'card_type' => $post['card_type'] ?? 1,
                            'amount' => $post['amount'],
                            'original_amount' => $post['amount'],
                            'status' => self::$model::STATUS_UNUSED,
                            'batch_no' => $batchNo,
                            'expire_time' => $expireTime,
                            'soft_name' => $post['soft_name'],
                            'creator_id' => $this->adminUid,
                            'remark' => $post['remark'] ?? ''
                        ]);
                    }
                });
                $this->success('批量创建成功');
            } catch (\Exception $e) {
                $this->error('批量创建失败：' . $e->getMessage());
            }
        }
        return $this->fetch();
    }

    /**
     * 跟单大师批量创建充值卡
     */
    #[NodeAnnotation(title: '跟单大师批量创建', auth: true)]
    public function batchCreateGdds(Request $request): string
    {
        if ($request->isPost()) {
            $post = $request->post();
            $rule = [
                'amount' => 'require|number|gt:0',
                'quantity' => 'require|number|gt:0|elt:1000',
            ];
            $this->validate($post, $rule);
            
            try {
                Db::transaction(function() use ($post) {
                    $batchNo = 'GDDS_BATCH_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
                    $expireTime = $post['expire_days'] > 0 ? time() + ($post['expire_days'] * 86400) : 0;
                    
                    for ($i = 0; $i < $post['quantity']; $i++) {
                        self::$model::create([
                            'card_no' => self::$model::generateCardNo(),
                            'card_type' => $post['card_type'] ?? 1,
                            'amount' => $post['amount'],
                            'original_amount' => $post['amount'],
                            'status' => self::$model::STATUS_UNUSED,
                            'batch_no' => $batchNo,
                            'expire_time' => $expireTime,
                            'soft_name' => '跟单大师', // 固定为跟单大师
                            'creator_id' => $this->adminUid,
                            'remark' => $post['remark'] ?? ''
                        ]);
                    }
                });
                $this->success('跟单大师充值卡批量创建成功');
            } catch (\Exception $e) {
                $this->error('批量创建失败：' . $e->getMessage());
            }
        }
        return $this->fetch('batch_create_gdds');
    }

    /**
     * 导出充值卡
     */
    #[NodeAnnotation(title: '导出充值卡', auth: true)]
    public function export()
    {
        $softName = $this->request->param('soft_name', '');
        
        list($page, $limit, $where) = $this->buildTableParams();
        
        // 如果指定了软件名称，添加筛选条件
        if (!empty($softName)) {
            $where[] = ['soft_name', '=', $softName];
        }
        
        $list = self::$model::where($where)
            ->limit(10000)
            ->order($this->sort)
            ->select()
            ->toArray();
        
        $header = [
            ['充值卡号', 'card_no'],
            ['卡类型', 'card_type'],
            ['金额', 'amount'],
            ['状态', 'status'],
            ['批次号', 'batch_no'],
            ['过期时间', 'expire_time'],
            ['软件名称', 'soft_name'],
            ['创建时间', 'create_time'],
            ['备注', 'remark']
        ];
        
        try {
            exportExcel($header, $list);
        } catch (\Throwable $exception) {
            $this->error('导出失败: ' . $exception->getMessage());
        }
    }
}