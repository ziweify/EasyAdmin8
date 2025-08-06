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
        // 获取请求参数
        $name = input('name');
        $pwd = input('pwd');

        if (empty($name) || empty($pwd)) {
            return json([
                'code' => 400,
                'message' => '用户名和密码不能为空'
            ]);
        }

        // 使用模型处理登录逻辑
        $userModel = new \app\admin\model\GddsUser();
        $result = $userModel->login($name, $pwd);

        if (!$result['success']) {
            return json([
                'code' => 401,
                'message' => $result['message']
            ]);
        }

        return json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'user_id' => $result['data']['user_id'],
                'name' => $result['data']['name'],
                'api_token' => $result['data']['api_token'],
                'api_public_key' => $result['data']['api_public_key']
            ]
        ]);



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
} 