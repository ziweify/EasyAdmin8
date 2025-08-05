<?php
// +----------------------------------------------------------------------
// | API 应用路由配置
// +----------------------------------------------------------------------

use think\facade\Route;

// 跟单大师API路由组
Route::group('gdds', function () {
    Route::get('getUser', 'Gdds/getUser');
    Route::get('getDate', 'Gdds/getDate'); 
    Route::get('getSystemInfo', 'Gdds/getSystemInfo');
    Route::post('login', 'Gdds/login');
}); 