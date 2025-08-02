<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::any('install', '\app\index\controller\Install@index');


// 跟单大师API路由组 - 使用多应用模式
Route::group('api', function () {
    Route::get('getdate', 'api/Gdds/getDate');
    Route::get('getSystemInfo', 'api/Gdds/getSystemInfo');
    Route::post('login', 'api/Gdds/login');
});//->prefix('api/');

// 测试路由
Route::get('test', function() {
    return json(['message' => 'Test route works!']);
});

//Route::get('api/getdate', 'api/Gdds/getDate');

// API测试路由
Route::get('api/test', 'api/Test/index');
Route::get('api/hello', 'api/Test/hello');
