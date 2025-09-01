<?php

namespace app\api\controller;

use think\Request;
use think\Response;

// 跟单大师
class Gdds
{

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
} 