<?php

namespace app\admin\controller\bssj;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;

#[ControllerAnnotation(title: 'bssj_twbg')]
class Twbg extends AdminController
{

    private array $notes;

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = new \app\admin\model\BssjTwbg();
        $notes = self::$model::$notes;
        
        // 修改排序字段为 issueid
        $this->sort = [
            'issueid' => 'desc',
        ];
        
        $this->notes =$notes;
        $this->assign(compact('notes'));
    }

    

}