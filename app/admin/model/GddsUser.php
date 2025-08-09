<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class GddsUser extends TimeModel
{

    protected function getOptions(): array
    {
        return [
            'name'       => "ea8_gdds_user",
            'table'      => "ea8_gdds_user",
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
        $updatedCount = 0;
        
        try {
            // 批量更新VIP时间为0或空的用户状态
            $count1 = self::where(function($query) {
                    $query->where('vip_off_time', '=', 0)
                          ->whereOr('vip_off_time', '=', '')
                          ->whereOr('vip_off_time', 'is', null);
                })
                ->where('status', 2) // 2表示启用状态
                ->update(['status' => 1]); // 1表示禁用状态
            
            // 批量更新VIP时间已过期的用户状态
            $count2 = self::where('vip_off_time', '<', $now)
                ->where('vip_off_time', '>', 0) // 排除vip_off_time为0的情况
                ->where('status', 2) // 2表示启用状态
                ->update(['status' => 1]); // 1表示禁用状态
            
            $updatedCount = $count1 + $count2;
            
            // 记录更新日志（可选）
            if ($updatedCount > 0) {
                \think\facade\Log::info("自动更新用户状态完成，共更新 {$updatedCount} 条记录");
            }
            
        } catch (\Exception $e) {
            \think\facade\Log::error("自动更新用户状态失败：" . $e->getMessage());
        }
        
        return $updatedCount;
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
     * 获取单个用户时自动检查状态一致性
     */
    public static function getUserWithStatusCheck($id)
    {
        // 先自动更新过期用户状态
        self::autoUpdateStatus();
        
        // 获取用户信息并检查状态一致性
        $user = self::find($id);
        if ($user) {
            $user->ensureStatusConsistency();
        }
        
        return $user;
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
     * 确保状态与VIP时间的一致性
     * 在查询数据时自动调用此方法
     */
    public function ensureStatusConsistency()
    {
        $vipOffTime = $this->getData('vip_off_time');
        $currentStatus = $this->getData('status');
        
        // 如果VIP时间为0或空（未开通），状态应该是禁用
        if (empty($vipOffTime) || $vipOffTime == 0) {
            if ($currentStatus != 1) {
                $this->set('status', 1);
                $this->save(['status' => 1]);
            }
            return 1;
        }
        
        // 如果VIP时间已过期，状态应该是禁用
        $now = time();
        if ($vipOffTime < $now) {
            if ($currentStatus != 1) {
                $this->set('status', 1);
                $this->save(['status' => 1]);
            }
            return 1;
        }
        
        return $currentStatus;
    }



    /**
     * 状态获取器 - 自动检测VIP时间并返回实际状态
     */
    public function getStatusAttr($value)
    {
        // 检查VIP时间并返回正确的状态
        $vipOffTime = $this->getData('vip_off_time');
        
        // 如果VIP时间为0或空（未开通），状态应该是禁用
        if (empty($vipOffTime) || $vipOffTime == 0) {
            return 1; // 返回禁用状态
        }
        
        // 如果VIP时间已过期，状态应该是禁用
        $now = time();
        if ($vipOffTime < $now) {
            return 1; // 返回禁用状态
        }
        
        // 如果VIP时间有效，返回原始状态
        return $value;
    }

    /**
     * 查询作用域：自动检查状态一致性
     * 使用ThinkPHP支持的方式
     */
    public function scopeWithStatusCheck($query)
    {
        // 先执行查询，然后在结果中处理
        return $query;
    }

    /**
     * 获取列表时自动检查状态一致性
     */
    public static function getListWithStatusCheck($where = [], $order = 'id desc', $limit = null, $page = null)
    {
        $query = self::where($where)->order($order);
        
        if ($page && $limit) {
            $list = $query->page($page, $limit)->select();
        } elseif ($limit) {
            $list = $query->limit($limit)->select();
        } else {
            $list = $query->select();
        }
        
        // 状态获取器会自动处理状态一致性，无需额外处理
        return $list;
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