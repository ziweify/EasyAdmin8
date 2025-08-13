<?php

namespace app\admin\controller\bsst;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;

#[ControllerAnnotation(title: 'bsst_recharge_card')]
class RechargeCard extends AdminController
{

    private array $notes;

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = new \app\admin\model\BsstRechargeCard();
        $notes = self::$model::$notes;
        
        $this->notes =$notes;
        $this->assign(compact('notes'));
    }

    

}