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

// API 路由 - 使用多应用模式的路由方式
Route::group('api', function () {
    Route::get('getuser', 'api/Api/getUser');
    Route::get('getdate', 'api/Api/getDate');
    Route::get('getsysteminfo', 'api/Api/getSystemInfo');
});
