<?php

namespace app\common\aspect;

use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * API统计切面类
 * 用于记录API调用统计信息
 */
class ApiStatsAspect
{
    /**
     * 前置通知 - 记录请求开始时间
     */
    public function before($method, $params)
    {
        $startTime = microtime(true);
        return ['start_time' => $startTime];
    }

    /**
     * 后置通知 - 记录API调用统计
     */
    public function after($method, $params, $result, $beforeData = [])
    {
        try {
            $endTime = microtime(true);
            $startTime = $beforeData['start_time'] ?? $endTime;
            $responseTime = round(($endTime - $startTime) * 1000, 2); // 转换为毫秒

            // 获取请求信息
            $request = request();
            $apiMethod = $this->getApiMethodName($method);
            $userId = $this->getUserIdFromToken($request);
            $username = $this->getUsernameFromToken($request);
            
            // 判断响应是否成功
            $responseCode = $this->getResponseCode($result);
            $isSuccess = $responseCode >= 200 && $responseCode < 300;
            
            // 记录详细日志
            $this->logApiCall([
                'user_id' => $userId,
                'username' => $username,
                'api_method' => $apiMethod,
                'request_url' => $request->url(true),
                'request_method' => $request->method(),
                'request_params' => json_encode($request->param()),
                'response_code' => $responseCode,
                'response_data' => json_encode($result),
                'response_time' => $responseTime,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('user-agent'),
                'api_token' => $request->header('api-token') ?: $request->param('api_token', ''),
                'error_message' => $isSuccess ? '' : $this->getErrorMessage($result),
                'create_time' => time()
            ]);

            // 更新统计数据
            if ($userId) {
                $this->updateApiStats($userId, $apiMethod, $isSuccess, $responseTime);
            }

        } catch (\Exception $e) {
            Log::error('API统计记录失败: ' . $e->getMessage());
        }
    }

    /**
     * 异常通知 - 记录异常信息
     */
    public function exception($method, $params, $exception, $beforeData = [])
    {
        try {
            $endTime = microtime(true);
            $startTime = $beforeData['start_time'] ?? $endTime;
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            $request = request();
            $apiMethod = $this->getApiMethodName($method);
            $userId = $this->getUserIdFromToken($request);
            $username = $this->getUsernameFromToken($request);

            // 记录异常日志
            $this->logApiCall([
                'user_id' => $userId,
                'username' => $username,
                'api_method' => $apiMethod,
                'request_url' => $request->url(true),
                'request_method' => $request->method(),
                'request_params' => json_encode($request->param()),
                'response_code' => 500,
                'response_data' => '',
                'response_time' => $responseTime,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('user-agent'),
                'api_token' => $request->header('api-token') ?: $request->param('api_token', ''),
                'error_message' => $exception->getMessage(),
                'create_time' => time()
            ]);

            // 更新统计数据（记为失败）
            if ($userId) {
                $this->updateApiStats($userId, $apiMethod, false, $responseTime);
            }

        } catch (\Exception $e) {
            Log::error('API异常统计记录失败: ' . $e->getMessage());
        }
    }

    /**
     * 记录API调用详细日志
     */
    private function logApiCall($logData)
    {
        try {
            Db::name('api_logs')->insert($logData);
        } catch (\Exception $e) {
            Log::error('记录API日志失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新API统计数据
     */
    private function updateApiStats($userId, $apiMethod, $isSuccess, $responseTime)
    {
        try {
            $today = date('Y-m-d');
            
            // 使用Redis缓存统计数据，减少数据库压力
            $cacheKey = "api_stats:{$userId}:{$today}:{$apiMethod}";
            $stats = Cache::get($cacheKey, [
                'total_calls' => 0,
                'success_calls' => 0,
                'failed_calls' => 0,
                'total_response_time' => 0
            ]);

            // 更新统计
            $stats['total_calls']++;
            if ($isSuccess) {
                $stats['success_calls']++;
            } else {
                $stats['failed_calls']++;
            }
            $stats['total_response_time'] += $responseTime;

            // 缓存统计数据，5分钟过期
            Cache::set($cacheKey, $stats, 300);

            // 异步更新数据库（使用队列或定时任务）
            $this->asyncUpdateDatabase($userId, $apiMethod, $today, $stats);

        } catch (\Exception $e) {
            Log::error('更新API统计失败: ' . $e->getMessage());
        }
    }

    /**
     * 异步更新数据库
     */
    private function asyncUpdateDatabase($userId, $apiMethod, $date, $stats)
    {
        try {
            $avgResponseTime = $stats['total_calls'] > 0 ? 
                round($stats['total_response_time'] / $stats['total_calls'], 2) : 0;

            $existingStats = Db::name('api_stats')
                ->where('user_id', $userId)
                ->where('date', $date)
                ->where('api_method', $apiMethod)
                ->find();

            if ($existingStats) {
                Db::name('api_stats')
                    ->where('id', $existingStats['id'])
                    ->update([
                        'total_calls' => $stats['total_calls'],
                        'success_calls' => $stats['success_calls'],
                        'failed_calls' => $stats['failed_calls'],
                        'avg_response_time' => $avgResponseTime,
                        'update_time' => time()
                    ]);
            } else {
                Db::name('api_stats')->insert([
                    'user_id' => $userId,
                    'date' => $date,
                    'api_method' => $apiMethod,
                    'total_calls' => $stats['total_calls'],
                    'success_calls' => $stats['success_calls'],
                    'failed_calls' => $stats['failed_calls'],
                    'avg_response_time' => $avgResponseTime,
                    'create_time' => time(),
                    'update_time' => time()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('异步更新API统计数据库失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取API方法名
     */
    private function getApiMethodName($method)
    {
        if (is_string($method)) {
            // 如果已经包含类名，直接返回
            if (strpos($method, '::') !== false) {
                return $method;
            }
            // 否则添加默认控制器前缀
            return 'app\\api\\controller\\Gdds::' . $method;
        }
        
        if (is_array($method) && count($method) >= 2) {
            $className = is_object($method[0]) ? get_class($method[0]) : $method[0];
            return $className . '::' . $method[1];
        }
        
        return 'unknown';
    }

    /**
     * 从Token获取用户ID
     */
    private function getUserIdFromToken($request)
    {
        $token = $request->header('api-token') ?: $request->param('api_token', '');
        if (empty($token)) {
            return 0;
        }

        try {
            $userData = Cache::store('redis')->get($token);
            if ($userData) {
                $data = json_decode($userData, true);
                return $data['id'] ?? 0;
            }
        } catch (\Exception $e) {
            Log::error('从Token获取用户ID失败: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * 从Token获取用户名
     */
    private function getUsernameFromToken($request)
    {
        $token = $request->header('api-token') ?: $request->param('api_token', '');
        if (empty($token)) {
            return '';
        }

        try {
            $userData = Cache::store('redis')->get($token);
            if ($userData) {
                $data = json_decode($userData, true);
                return $data['username'] ?? '';
            }
        } catch (\Exception $e) {
            Log::error('从Token获取用户名失败: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * 获取响应状态码
     */
    private function getResponseCode($result)
    {
        if (is_array($result) && isset($result['code'])) {
            return $result['code'];
        }
        
        if (is_object($result) && method_exists($result, 'getCode')) {
            return $result->getCode();
        }
        
        return 200; // 默认成功
    }

    /**
     * 获取错误信息
     */
    private function getErrorMessage($result)
    {
        if (is_array($result) && isset($result['message'])) {
            return $result['message'];
        }
        
        if (is_object($result) && method_exists($result, 'getMessage')) {
            return $result->getMessage();
        }
        
        return '';
    }
}