<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class GddsUser extends TimeModel
{
    protected $name = 'gdds_user';
    protected $table = 'ea8_gdds_user';
    protected $deleteTime = 'delete_time';
    
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            'deleteTime' => $this->deleteTime,
        ]);
    }

    public static array $notes = [];

    /**
     * 自动检测VIP时间并更新状态
     * 当VIP时间过期或为空时，自动将状态设置为禁用(1)  
     * 当VIP时间有效时，如果状态为禁用且应该启用，则更新为启用(2)
     */
    public static function autoUpdateStatus()
    {
        $now = time(); // 使用时间戳
        $updatedCount = 0;
        
        try {
            // 查询需要更新的数据
            $debugInfo = [
                'now' => $now,
                'expired_users' => self::where('vip_off_time', '<', $now)->where('vip_off_time', '>', 0)->count(),
                'no_vip_users' => self::where(function($query) {
                    $query->where('vip_off_time', '=', 0)
                          ->whereOr('vip_off_time', '=', '')
                          ->whereOr('vip_off_time', 'is', null);
                })->count(),
                'active_users' => self::where('vip_off_time', '>', $now)->count()
            ];
            
            // 批量更新VIP时间为0或空的用户状态为禁用
            $count1 = self::where(function($query) {
                    $query->where('vip_off_time', '=', 0)
                          ->whereOr('vip_off_time', '=', '')
                          ->whereOr('vip_off_time', 'is', null);
                })
                ->where('status', 2) // 2表示启用状态
                ->update(['status' => 1]); // 1表示禁用状态
            
            // 批量更新VIP时间已过期的用户状态为禁用
            $count2 = self::where('vip_off_time', '<', $now)
                ->where('vip_off_time', '>', 0) // 排除vip_off_time为0的情况
                ->where('status', 2) // 2表示启用状态
                ->update(['status' => 1]); // 1表示禁用状态
            
            // 不再自动将VIP有效的禁用用户改为启用，允许管理员手动控制
            $count3 = 0; // 移除自动启用逻辑
            
            $updatedCount = $count1 + $count2 + $count3;
            
            // 记录详细的更新日志
            if ($updatedCount > 0 || true) { // 总是记录日志
                \think\facade\Log::info("自动更新用户状态执行: 未开通用户禁用{$count1}个, 过期用户禁用{$count2}个, 共更新{$updatedCount}条记录", $debugInfo);
            }
            
        } catch (\Exception $e) {
            \think\facade\Log::error("自动更新用户状态失败：" . $e->getMessage());
        }
        
        return $updatedCount;
    }

    /**
     * 温和的过期状态更新 - 只处理明确过期的情况
     * 这个方法只更新那些VIP时间明确过期的用户，不会影响"未开通"的用户
     * 让管理员能够手动控制"未开通"用户的状态
     */
    public static function autoUpdateExpiredStatus()
    {
        $now = time();
        $updatedCount = 0;
        
        try {
            // 只更新VIP时间已过期且当前为启用状态的用户
            // 不处理未开通(vip_off_time=0或null)的情况，允许管理员手动控制这些用户的状态
            $count = self::where('vip_off_time', '<', $now)
                ->where('vip_off_time', '>', 0) // 排除未开通的情况 (vip_off_time=0)
                ->where('status', 2) // 只更新当前启用的用户
                ->update(['status' => 1]); // 设置为禁用状态
            
            $updatedCount = $count;
            
            // 记录更新日志
            if ($updatedCount > 0) {
                \think\facade\Log::info("温和更新过期用户状态完成，共更新 {$updatedCount} 条记录");
            }
            
        } catch (\Exception $e) {
            \think\facade\Log::error("温和更新过期用户状态失败：" . $e->getMessage());
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
        
        // 获取用户信息
        $user = self::find($id);
        
        // 如果用户存在，确保其状态是最新的
        if ($user) {
            $user->updateUserStatus();
        }
        
        return $user;
    }

    /**
     * VIP时间获取器 - 返回原始时间戳，用于前端JavaScript处理
     * 前端需要时间戳来正确显示日期，而不是格式化后的字符串
     */
    public function getVipOffTimeAttr($value)
    {
        // 直接返回原始值，让前端处理格式化
        // 这样可以避免"invalid date"的问题
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
        
        // 如果已经是数字时间戳，直接返回
        if (is_numeric($value)) {
            $timestamp = intval($value);
            if ($timestamp > 0) {
                return $timestamp;
            }
        }
        
        // 尝试解析日期时间字符串
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new \Exception('VIP时间格式不正确');
        }
        
        return $timestamp;
    }

    /**
     * 更新单个用户状态
     * 只在VIP时间过期时自动将状态改为禁用
     */
    public function updateUserStatus()
    {
        $currentStatus = $this->getData('status');
        $vipOffTime = $this->getData('vip_off_time');
        
        // 只在以下情况自动更新状态：
        // 1. 当前状态为启用(2)
        // 2. VIP时间已过期或未设置
        if ($currentStatus == 2) {
            $shouldDisable = false;
            
            // VIP时间为0或空，表示未开通
            if (empty($vipOffTime) || $vipOffTime == 0) {
                $shouldDisable = true;
            }
            // VIP时间已过期
            elseif ($vipOffTime < time()) {
                $shouldDisable = true;
            }
            
            // 只有需要禁用时才更新状态
            if ($shouldDisable) {
                $this->set('status', 1);
                $this->save(['status' => 1]);
                return 1; // 返回新状态
            }
        }
        
        return $currentStatus; // 返回当前状态
    }

    /**
     * 状态获取器 - 直接返回数据库中的状态值
     * 因为状态已经在autoUpdateStatus中自动维护，所以直接返回即可
     */
    public function getStatusAttr($value)
    {
        // 状态已经在autoUpdateStatus中自动维护，直接返回数据库中的值
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
     * 在返回数据之前，自动更新所有用户的状态到数据库
     */
    public static function getListWithStatusCheck($where = [], $order = 'id desc', $limit = null, $page = null)
    {
        // 先自动更新所有用户的状态到数据库
        self::autoUpdateStatus();
        
        $query = self::where($where)->order($order);
        
        if ($page && $limit) {
            $list = $query->page($page, $limit)->select();
        } elseif ($limit) {
            $list = $query->limit($limit)->select();
        } else {
            $list = $query->select();
        }
        
        // 由于已经更新了数据库，直接返回查询结果即可
        // 前端显示的就是数据库中的真实状态，无需额外处理
        return $list;
    }

    /**
     * 获取VIP时间显示格式 - 用于前端显示
     * 这个方法专门为前端提供格式化的VIP时间显示
     */
    public function getVipOffTimeDisplayAttr()
    {
        $vipOffTime = $this->getData('vip_off_time');
        if (empty($vipOffTime) || $vipOffTime == 0) {
            return '未开通';
        }
        // 如果是时间戳，转换为可读的日期时间格式
        if (is_numeric($vipOffTime)) {
            return date('Y-m-d H:i:s', $vipOffTime);
        }
        return $vipOffTime;
    }

    /**
     * 强制刷新所有用户状态
     * 这个方法会强制更新所有用户的状态到数据库
     */
    public static function forceRefreshAllStatus()
    {
        $users = self::select();
        $updatedCount = 0;
        
        foreach ($users as $user) {
            $oldStatus = $user->getData('status');
            $newStatus = $user->updateUserStatus();
            if ($oldStatus != $newStatus) {
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }

    public function login($name, $pwd)
    {
        try {
            $user = self::where('username', $name)->find();
            if (!$user) {
                return ['success' => false, 'message' => '用户不存在'];
            }

            if ($user->password !== $pwd) {
                return ['success' => false, 'message' => '密码错误'];
            }

            return [
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->username,
                    'api_token' => md5($user->id . time() . rand(1000, 9999))
                ]
            ];
        } catch (\Exception $e) {
            \think\facade\Log::error('Login error in model: ' . $e->getMessage());
            return ['success' => false, 'message' => '登录过程发生错误'];
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