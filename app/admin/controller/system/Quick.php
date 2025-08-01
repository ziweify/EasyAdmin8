<?php

namespace app\admin\controller\system;


use app\admin\model\SystemQuick;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;

#[ControllerAnnotation(title: '快捷入口管理')]
class Quick extends AdminController
{

    protected array $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = SystemQuick::class;
    }

}