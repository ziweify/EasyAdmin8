<?php

namespace app\common\middleware;

use think\facade\Cache;
use think\facade\Log;

/**
 * API认证中间件
 * 用于验证API调用权限
 */
class ApiAuth
{
    /**
     * 不需要认证的方法
     */
    protected $noAuthMethods = [
        'login',
        'index'
    ];

    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        $action = $request->action();
        
        // 跳过不需要认证的方法
        if (in_array($action, $this->noAuthMethods)) {
            return $next($request);
        }

        // 获取API Token
        $token = $request->header('api-token') ?: $request->param('api_token', '');
        
        if (empty($token)) {
            return json([
                'code' => 401,
                'message' => 'API Token不能为空'
            ]);
        }

        // 验证Token
        try {
            $redis = Cache::store('redis');
            $tokenData = $redis->get($token);
            
            if (!$tokenData) {
                return json([
                    'code' => 401,
                    'message' => 'API Token无效或已过期'
                ]);
            }

            $userData = json_decode($tokenData, true);
            if (!$userData || !isset($userData['id'])) {
                return json([
                    'code' => 401,
                    'message' => 'API Token数据格式错误'
                ]);
            }

            // 检查是否有错误标记
            if (isset($userData['err']) && $userData['err'] > 0) {
                return json([
                    'code' => 401,
                    'message' => 'API Token已被标记为异常'
                ]);
            }

            // 将用户信息添加到请求中
            $request->userId = $userData['id'];
            $request->username = $userData['username'];
            $request->apiToken = $token;

        } catch (\Exception $e) {
            Log::error('API认证失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'API认证过程发生错误'
            ]);
        }

        return $next($request);
    }
}