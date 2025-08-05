<?php

namespace app\api\controller;

use think\Request;

class Test
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

    public function hello()
    {
        return json([
            'code' => 200,
            'message' => 'Hello from API!',
            'data' => [
                'greeting' => 'Hello World',
                'timestamp' => time()
            ]
        ]);
    }
} 