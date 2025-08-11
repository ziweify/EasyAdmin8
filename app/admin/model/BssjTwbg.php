<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class BssjTwbg extends TimeModel
{
    // 设置主键字段为 issueid
    protected $pk = 'issueid';

    protected function getOptions(): array
    {
        return [
            'name'       => "bssj_twbg",
            'table'      => "",
            'deleteTime' => "delete_time",
        ];
    }

    public static array $notes = [
];

    

}