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

    public static array $notes = [];

    public function login($name, $pwd)
    {
        $user = self::where('name', $name)->find();
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }
    }

    public function getUser($user_id)   
    {
        $user = self::where('user_id', $user_id)->find();
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }
        return ['success' => true, 'data' => $user];
    }

    public function test()
    {
        return "test";
    }

}