<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class BssjTwbg extends TimeModel
{
    // 设置主键字段为 issueid
    protected $pk = 'issueid';
    
    // 设置数据表名（不带前缀）
    protected $name = 'bssj_twbg';
    
    protected function getOptions(): array
    {
        return [
            'deleteTime' => "delete_time",
        ];
    }

    public static array $notes = [
];

    

}