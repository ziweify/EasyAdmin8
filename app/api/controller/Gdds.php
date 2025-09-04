<?php

namespace app\api\controller;

use think\Request;
use think\Response;

/**
 * 跟单大师API控制器
 * 应用API统计中间件
 */

// 跟单大师
class Gdds
{
    /**
     * 中间件定义
     */
    protected $middleware = [
        'app\common\middleware\ApiStats',
        'app\common\middleware\ApiAuth' => ['except' => ['login', 'index']]
    ];

    public function index()
    {
        return json([
            'code' => 200,
            'message' => 'API Test Controller works!',
            'data' => [
                'time' => date('Y-m-d H:i:s'),
                'controller' => 'Test',
                'action' => 'index'
            ]
        ]);
    }

    public function test()
    {
        $userModel = new \app\api\model\GddsUser();
        $result = $userModel->test();
        return $result;
    }
    
    //http://172.28.35.182:8850/api/gdds/login?name=ds1000&pwd=Aaa111
    public function login()
    {
        try {
            // 获取POST数据
            $raw_post = file_get_contents('php://input');
            parse_str($raw_post, $post_data);
            
            $name = $post_data['name'] ?? '';
            $pwd = $post_data['pwd'] ?? '';

            if (empty($name) || empty($pwd)) {
                return json(['code' => 400, 'message' => '用户名和密码不能为空']);
            }

            // 处理登录
            $result = (new \app\admin\model\GddsUser())->login($name, $pwd);
            
            return json($result['success'] ? [
                'code' => 200,
                'message' => '登录成功',
                'data' => $result['data']
            ] : [
                'code' => 401,
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            \think\facade\Log::error('Login error: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }


    /**
     * 获取用户信息
     * @return Response
     */
    public function getUser()
    {
        // 这里可以根据实际需求返回用户数据
        $userData = [
            'code' => 200,
            'message' => 'success',
            'data' => [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        return json($userData);
    }

    /**
     * 获取日期时间
     * @return Response
     */
    public function getDate()
    {
        $dateData = [
            'code' => 200,
            'message' => 'success',
            'data' => [
                'current_time' => date('Y-m-d H:i:s'),
                'timestamp' => time(),
                'timezone' => date_default_timezone_get()
            ]
        ];
        
        return json($dateData);
    }

    /**
     * 获取系统信息
     * @return Response
     */
    public function getSystemInfo()
    {
        $systemData = [
            'code' => 200,
            'message' => 'success',
            'data' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'framework' => 'ThinkPHP',
                'memory_usage' => memory_get_usage(true)
            ]
        ];
        
        return json($systemData);
    }

    /**
     * 获取宾果开奖数据
     * @return Response
     */
    public function getBslotteryTwbg()
    {
        $issueid = input('issueid');
        if(empty($issueid)) {
            return json(['code' => 400, 'message' => '期号不能为空']);
        }

        // 从Redis获取缓存数据
        $redis = cache('redis');
        $cacheKey = "bslottery_twbg:{$issueid}";
        $result = $redis->get($cacheKey);

        if($result) {
            // 有缓存直接返回
            // 由于Redis的get操作是原子的,不会出现并发问题
            // 多个请求同时读取缓存是安全的
            // 即使缓存失效导致多个请求同时查询数据库,最坏情况也只是多次写入相同的缓存数据
            // 不会影响数据一致性
            return json(json_decode($result, true));
        }

        // 无缓存,查询数据库
        $bslotteryTwbg = new \app\admin\model\BslotteryTwbg();
        $result = $bslotteryTwbg->getBslotteryTwbg($issueid);

        if($result) {
            // 写入Redis缓存,设置过期时间1小时
            $redis->setex($cacheKey, 3600, json_encode($result));
            
            // 异步通知后台更新开奖结果
            try {
                $this->asyncNotify($issueid);
            } catch(\Exception $e) {
                // 记录日志但不影响返回
                \think\facade\Log::error("异步通知失败: {$e->getMessage()}");
            }
        } else {
            // 未查到数据也缓存空结果,避免频繁查询,设置较短过期时间
            $redis->setex($cacheKey, 60, json_encode(['code' => 404, 'message' => '未找到该期开奖数据']));
        }

        return json($result);
    }

    /**
     * 异步通知后台更新开奖结果
     */
    private function asyncNotify($issueid)
    {
        // 投递异步任务,通知后台更新开奖结果
        $task = [
            'type' => 'update_lottery',
            'issueid' => $issueid,
            'time' => time()
        ];
        
        // 这里可以使用队列系统如RabbitMQ等
        // 示例使用Redis List实现简单队列
        $redis = cache('redis');
        $redis->lpush('lottery_update_queue', json_encode($task));
    }

    /**
     * 获取API调用统计
     */
    public function getApiStats()
    {
        try {
            $userId = $this->request->userId ?? 0;
            $date = input('date', date('Y-m-d'));
            
            if (!$userId) {
                return json(['code' => 401, 'message' => '用户未认证']);
            }

            // 获取统计数据
            $stats = \think\facade\Db::name('api_stats')
                ->where('user_id', $userId)
                ->where('date', $date)
                ->select()
                ->toArray();

            // 计算总计
            $totalStats = [
                'total_calls' => array_sum(array_column($stats, 'total_calls')),
                'success_calls' => array_sum(array_column($stats, 'success_calls')),
                'failed_calls' => array_sum(array_column($stats, 'failed_calls')),
                'success_rate' => 0
            ];

            if ($totalStats['total_calls'] > 0) {
                $totalStats['success_rate'] = round(
                    ($totalStats['success_calls'] / $totalStats['total_calls']) * 100, 2
                );
            }

            return json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'date' => $date,
                    'user_id' => $userId,
                    'total_stats' => $totalStats,
                    'method_stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            \think\facade\Log::error('获取API统计失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取统计数据失败']);
        }
    }

    /**
     * 获取API调用日志
     */
    public function getApiLogs()
    {
        try {
            $userId = $this->request->userId ?? 0;
            $page = input('page', 1);
            $limit = input('limit', 20);
            $date = input('date', date('Y-m-d'));
            
            if (!$userId) {
                return json(['code' => 401, 'message' => '用户未认证']);
            }

            $where = [
                ['user_id', '=', $userId],
                ['create_time', 'between', [
                    strtotime($date . ' 00:00:00'),
                    strtotime($date . ' 23:59:59')
                ]]
            ];

            $logs = \think\facade\Db::name('api_logs')
                ->where($where)
                ->order('create_time desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            $total = \think\facade\Db::name('api_logs')
                ->where($where)
                ->count();

            return json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'logs' => $logs,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit
                ]
            ]);

        } catch (\Exception $e) {
            \think\facade\Log::error('获取API日志失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取日志数据失败']);
        }
    }

    /**
     * 测试API统计功能
     */
    public function testApiStats()
    {
        try {
            // 模拟一些处理时间
            usleep(rand(100000, 500000)); // 0.1-0.5秒随机延时
            
            return json([
                'code' => 200,
                'message' => 'API统计测试成功',
                'data' => [
                    'timestamp' => time(),
                    'random' => rand(1000, 9999),
                    'test_info' => '这是一个用于测试API统计功能的接口'
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => 'API统计测试失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 查看所有可用的API接口
     */
    public function getApiList()
    {
        $apiList = [
            'index' => '基本测试接口',
            'test' => '测试方法',
            'login' => '用户登录接口',
            'getUser' => '获取用户信息',
            'getDate' => '获取日期时间',
            'getSystemInfo' => '获取系统信息',
            'getBslotteryTwbg' => '获取宾果开奖数据',
            'getApiStats' => '获取API调用统计',
            'getApiLogs' => '获取API调用日志',
            'testApiStats' => '测试API统计功能',
            'getApiList' => '获取API接口列表'
        ];

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'total_apis' => count($apiList),
                'api_list' => $apiList,
                'stats_info' => '所有API都已自动启用统计功能'
            ]
        ]);
    }
} 