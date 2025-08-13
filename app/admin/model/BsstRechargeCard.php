<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class BsstRechargeCard extends TimeModel
{

    protected function getOptions(): array
    {
        return [
            'name'       => "bsst_recharge_card",
            'table'      => "",
            'deleteTime' => "delete_time",
        ];
    }

    public static array $notes = [
];

    

}