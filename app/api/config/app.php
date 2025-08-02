<?php
// +----------------------------------------------------------------------
// | API 应用配置
// +----------------------------------------------------------------------

return [
    // API 应用特有配置
    'api_version' => 'v1',
    'api_prefix' => 'api',
    
    // 应用调试模式
    'app_debug'   => env('APP_DEBUG', true),
    
    // 应用Trace
    'app_trace'   => env('APP_TRACE', false),
    
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    
    // 是否启用路由
    'with_route' => true,
    
    // 默认控制器名
    'default_controller' => 'Index',
    
    // 默认操作名
    'default_action' => 'index',
    
    // 操作方法后缀
    'action_suffix' => '',
    
    // 自动搜索控制器
    'auto_multi_module' => true,
    
    // 注册的根命名空间
    'root_namespace' => [],
    
    // 扩展函数文件
    'extra_file_list' => [],
    
    // 默认输出类型
    'default_return_type' => 'json',
    
    // 默认AJAX数据返回格式,可选json xml ...
    'default_ajax_return' => 'json',
    
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler' => 'jsonpReturn',
    
    // 默认JSONP处理方法
    'var_jsonp_handler' => 'callback',
]; 