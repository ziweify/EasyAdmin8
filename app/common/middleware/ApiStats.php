<?php

namespace app\common\middleware;

use app\common\aspect\ApiStatsAspect;
use think\facade\Log;

/**
 * API统计中间件
 * 用于在API控制器中自动应用统计功能
 */
class ApiStats
{
    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        $aspect = new ApiStatsAspect();
        $startTime = microtime(true);
        
        // 获取控制器和方法信息
        $controller = $request->controller();
        $action = $request->action();
        $method = $controller . '::' . $action;
        
        // 记录请求开始
        \think\facade\Log::info("API调用开始: {$method}, IP: {$request->ip()}");
        
        try {
            // 前置处理
            $beforeData = $aspect->before($method, $request->param());
            
            // 执行控制器方法
            $response = $next($request);
            
            // 后置处理
            $result = $this->getResponseData($response);
            $aspect->after($method, $request->param(), $result, $beforeData);
            
            // 记录请求完成
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            \think\facade\Log::info("API调用完成: {$method}, 耗时: {$duration}ms");
            
            return $response;
            
        } catch (\Exception $e) {
            // 异常处理
            $beforeData = ['start_time' => $startTime];
            $aspect->exception($method, $request->param(), $e, $beforeData);
            
            // 记录异常
            \think\facade\Log::error("API调用异常: {$method}, 错误: {$e->getMessage()}");
            
            throw $e;
        }
    }

    /**
     * 获取响应数据
     */
    private function getResponseData($response)
    {
        if (method_exists($response, 'getContent')) {
            $content = $response->getContent();
            return json_decode($content, true) ?: $content;
        }
        
        return $response;
    }
}