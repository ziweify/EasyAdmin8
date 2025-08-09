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

    /**
     * 自动检测VIP时间并更新状态
     * 当VIP时间过期或为空时，自动将状态设置为禁用(1)
     */
    public static function autoUpdateStatus()
    {
        $now = time(); // 使用时间戳
        
        // 查找所有VIP时间已过期且状态为启用的用户
        $expiredUsers = self::where('vip_off_time', '<', $now)
            ->where('vip_off_time', '>', 0) // 排除vip_off_time为0的情况
            ->where('status', 2) // 2表示启用状态
            ->update(['status' => 1]); // 1表示禁用状态
        
        // 查找所有VIP时间为0或空且状态为启用的用户（未开通VIP）
        $noVipUsers = self::where(function($query) {
                $query->where('vip_off_time', '=', 0)
                      ->whereOr('vip_off_time', '=', '')
                      ->whereOr('vip_off_time', 'is', null);
            })
            ->where('status', 2) // 2表示启用状态
            ->update(['status' => 1]); // 1表示禁用状态
            
        return $expiredUsers + $noVipUsers;
    }

    /**
     * 获取用户列表时自动更新状态
     */
    public static function getListWithAutoStatus($where = [], $order = 'id desc', $limit = null)
    {
        // 先自动更新过期用户状态
        self::autoUpdateStatus();
        
        // 返回查询结果
        $query = self::where($where);
        if ($limit) {
            return $query->order($order)->limit($limit)->select();
        }
        return $query->order($order)->select();
    }

    /**
     * 获取单个用户时自动更新状态
     */
    public static function getUserWithAutoStatus($id)
    {
        // 先自动更新过期用户状态
        self::autoUpdateStatus();
        
        // 返回用户信息
        return self::find($id);
    }

    /**
     * VIP时间获取器 - 格式化显示
     */
    public function getVipOffTimeAttr($value)
    {
        if (empty($value) || $value == 0) {
            return '未开通';
        }
        // 如果是时间戳，转换为可读的日期时间格式
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', $value);
        }
        return $value;
    }

    /**
     * VIP时间修改器 - 保存时验证格式
     */
    public function setVipOffTimeAttr($value)
    {
        if (empty($value) || $value == '未开通') {
            return 0; // 设置为0表示未开通
        }
        // 验证日期时间格式并转换为时间戳
        if (strtotime($value) === false) {
            throw new \Exception('VIP时间格式不正确');
        }
        return strtotime($value); // 转换为时间戳
    }

    /**
     * 状态获取器 - 自动检测VIP时间并返回实际状态
     */
    public function getStatusAttr($value)
    {
        // 如果VIP时间为0或空（未开通），强制状态为禁用
        if (empty($this->vip_off_time) || $this->vip_off_time == 0) {
            if ($value == 2) {
                // 如果当前状态是启用，但VIP时间为0或空，则自动更新为禁用
                $this->status = 1;
                $this->save();
            }
            return 1; // 返回禁用状态
        }
        
        // 如果状态为启用(2)，检查VIP时间是否过期
        if ($value == 2) {
            $now = time(); // 使用时间戳
            if ($this->vip_off_time < $now) {
                // VIP时间已过期，自动更新状态为禁用
                $this->status = 1;
                $this->save();
                return 1; // 返回禁用状态
            }
        }
        
        return $value;
    }

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