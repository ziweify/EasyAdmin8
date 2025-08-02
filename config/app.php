<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // 应用地址
    'app_host'              => env('APP_HOST', ''),
    // 应用的命名空间
    'app_namespace'         => '',
    // 是否启用路由
    'with_route'            => true,
    // 是否启用多应用模式
    'auto_multi_app'        => true,
    // 默认应用
    'default_app'           => 'index',
    // 默认时区
    'default_timezone'      => 'Asia/Shanghai',

    // 应用映射（自动多应用模式有效）
    'app_map'               => [
        Env::get('EASYADMIN.ADMIN', 'admin') => 'admin',
        'api' => 'api',  // 添加 api 应用映射
    ],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'           => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'         => ['common'],

    // 异常页面的模板文件
    'exception_tmpl'        => Env::get('APP_DEBUG') == 1 ? app()->getThinkPath() . 'tpl/think_exception.tpl' : app()->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'think_exception.tpl',
    // 跳转页面的成功模板文件
    'dispatch_success_tmpl' => app()->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'dispatch_jump.tpl',
    // 跳转页面的失败模板文件
    'dispatch_error_tmpl'   => app()->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'dispatch_jump.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'         => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'        => false,
    // 静态资源上传到OSS前缀
    'oss_static_prefix'     => Env::get('EASYADMIN.OSS_STATIC_PREFIX', 'static_easyadmin'),
];
