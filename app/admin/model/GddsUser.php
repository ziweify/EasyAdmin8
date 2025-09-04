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
            \think\facade\Log::info("开始登录验证: {$name}");
            
            $user = self::where('username', $name)->find();
            if (!$user) {
                \think\facade\Log::info("用户不存在: {$name}");
                return ['success' => false, 'message' => '用户不存在'];
            }

            if ($user->password !== $pwd) {
                \think\facade\Log::info("密码错误: {$name}");
                return ['success' => false, 'message' => '密码错误'];
            }

            \think\facade\Log::info("密码验证成功，开始生成RSA密钥对: {$name}");

            // 1. 生成RSA密钥对
            try {
                $config = [
                    "digest_alg" => "sha512",
                    "private_key_bits" => 2048,
                    "private_key_type" => OPENSSL_KEYTYPE_RSA,
                ];
                
                $res = openssl_pkey_new($config);
                if (!$res) {
                    throw new \Exception('生成RSA密钥对失败');
                }
                
                openssl_pkey_export($res, $privateKey);
                $details = openssl_pkey_get_details($res);
                $publicKey = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n"], '', $details['key']);
                \think\facade\Log::info("RSA密钥对生成成功: {$name}");
            } catch (\Exception $e) {
                \think\facade\Log::error("RSA密钥对生成失败: " . $e->getMessage());
                throw $e;
            }
             
            // 2. 记录登录IP和时间
            $user->last_login_ip = request()->ip();
            $user->last_login_time = time();
             
            // 3. 保存RSA密钥对和生成token
            $user->api_public_key = $publicKey;
            $user->api_private_key = $privateKey;
            $user->api_token = md5($user->id . $privateKey);
             
            // 4. 保存用户信息
            $user->save();
            \think\facade\Log::info("用户信息保存成功: {$name}");
             
            // 5. 记录登录日志到系统日志表
            try {
                \think\facade\Db::name('system_log')->insert([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'action' => 'login',
                    'module' => 'api',
                    'controller' => 'gdds',
                    'method' => 'login',
                    'ip' => $user->last_login_ip,
                    'description' => '用户API登录成功',
                    'user_agent' => request()->header('user-agent') ?: '',
                    'request_data' => json_encode([
                        'username' => $name,
                        'login_time' => date('Y-m-d H:i:s'),
                        'ip' => $user->last_login_ip
                    ]),
                    'response_data' => json_encode([
                        'success' => true,
                        'user_id' => $user->id,
                        'api_token' => substr($user->api_token, 0, 10) . '...' // 只记录token前10位
                    ]),
                    'create_time' => time(),
                    'update_time' => time()
                ]);
                \think\facade\Log::info("系统日志记录成功: {$name}");
            } catch (\Exception $e) {
                \think\facade\Log::error('记录登录日志失败: ' . $e->getMessage());
                // 日志记录失败不影响登录
            }
            
            // 6. 存储Redis数据
            try {
                $redis = \think\facade\Cache::store('redis');
                $tokenData = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'api_private_key' => $privateKey,
                    'api_public_key' => $publicKey,
                    'login_time' => time(),
                    'login_ip' => $user->last_login_ip,
                    'err' => 0,
                    'expire_time' => time() + 7200
                ];
                $redis->set($user->api_token, json_encode($tokenData), 7200); // 2小时过期
                
                // 同时存储用户ID到token的映射，方便根据用户ID查找token
                $redis->set('user_token:' . $user->id, $user->api_token, 7200);
                
                \think\facade\Log::info('Redis数据存储成功: token=' . substr($user->api_token, 0, 10) . '...');
                
            } catch (\Exception $e) {
                \think\facade\Log::error('存储Redis数据失败: ' . $e->getMessage());
                // Redis失败不影响登录，继续执行
            }
            
            // 7. 初始化或更新API调用统计表
            try {
                $today = date('Y-m-d');
                $apiStats = \think\facade\Db::name('api_stats')
                    ->where('user_id', $user->id)
                    ->where('date', $today)
                    ->where('api_method', 'login')
                    ->find();
                
                if (!$apiStats) {
                    \think\facade\Db::name('api_stats')->insert([
                        'user_id' => $user->id,
                        'date' => $today,
                        'api_method' => 'login',
                        'total_calls' => 0,
                        'success_calls' => 0,
                        'failed_calls' => 0,
                        'avg_response_time' => 0.00,
                        'create_time' => time(),
                        'update_time' => time()
                    ]);
                }
                \think\facade\Log::info("API统计初始化成功: {$name}");
            } catch (\Exception $e) {
                \think\facade\Log::error('初始化API统计失败: ' . $e->getMessage());
                // 统计初始化失败不影响登录
            }

            \think\facade\Log::info("登录流程全部完成: {$name}");

            return [
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->username,
                    'api_token' => $user->api_token,
                    'code' => $user->api_public_key
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