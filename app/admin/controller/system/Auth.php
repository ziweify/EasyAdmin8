<?php

namespace app\admin\controller\system;

use app\admin\model\SystemAuth;
use app\admin\model\SystemAuthNode;
use app\admin\service\TriggerService;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\Request;
use think\App;

#[ControllerAnnotation(title: '角色权限管理', auth: true)]
class Auth extends AdminController
{

    protected array $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = SystemAuth::class;
    }

    #[NodeAnnotation(title: '授权', auth: true)]
    public function authorize(Request $request, $id): string
    {
        $row = self::$model::find($id);
        empty($row) && $this->error('数据不存在');
        if ($request->isAjax()) {
            $list = self::$model::getAuthorizeNodeListByAdminId($id);
            $this->success('获取成功', $list);
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    #[NodeAnnotation(title: '授权保存', auth: true)]
    public function saveAuthorize(Request $request): void
    {
        $this->checkPostRequest();
        $id   = $request->post('id');
        $node = $request->post('node', "[]");
        $node = json_decode($node, true);
        $row  = self::$model::find($id);
        empty($row) && $this->error('数据不存在');
        try {
            $authNode = new SystemAuthNode();
            $authNode->where('auth_id', $id)->delete();
            if (!empty($node)) {
                $saveAll = [];
                foreach ($node as $vo) {
                    $saveAll[] = [
                        'auth_id' => $id,
                        'node_id' => $vo,
                    ];
                }
                $authNode->saveAll($saveAll);
            }
            TriggerService::updateMenu();
        }catch (\Exception $e) {
            $this->error('保存失败');
        }
        $this->success('保存成功');
    }

}