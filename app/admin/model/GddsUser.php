<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class GddsUser extends TimeModel
{

    protected function getOptions(): array
    {
        return [
            'name'       => "gdds_user",
            'table'      => "",
            'deleteTime' => "delete_time",
        ];
    }

    public static array $notes = [
];

    

}