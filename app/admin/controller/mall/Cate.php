<?php

namespace app\admin\controller\mall;

use app\admin\model\MallCate;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;

#[ControllerAnnotation(title: '商品分类管理')]
class Cate extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = MallCate::class;
    }

}