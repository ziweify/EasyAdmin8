<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class BsstSoftware extends TimeModel
{

    protected function getOptions(): array
    {
        return [
            'name'       => "bsst_software",
            'table'      => "",
            'deleteTime' => "delete_time",
        ];
    }

    public static array $notes = [
];

    

}