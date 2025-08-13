<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class BsstTwbg extends TimeModel
{
    // 设置主键字段为 issueid
    protected $pk = 'issueid';
    
    // 设置数据表名（不带前缀）
    protected $name = 'bsst_twbg';
    
    protected function getOptions(): array
    {
        return [
            'deleteTime'       => "delete_time",
            'autoWriteTimestamp' => true,      // 自动写入时间戳
            'createTime'       => 'create_time', // 创建时间字段
            'updateTime'       => 'update_time', // 更新时间字段
        ];
    }

    public static array $notes = [
];

    

}