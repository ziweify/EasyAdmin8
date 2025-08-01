<?php

namespace app\api\controller;

use app\BaseController;
use think\Response;

// 跟单大师
class Gdds extends BaseController
{
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
} 