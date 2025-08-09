<?php

namespace app\admin\service;

/**
 * VIP状态管理服务
 * 用于自动检测和更新过期的VIP用户状态
 */
class VipStatusService
{
    /**
     * 自动更新过期VIP用户状态
     * 可以在定时任务、钩子或手动调用时使用
     */
    public static function autoUpdateExpiredVipUsers()
    {
        try {
            $model = new \app\admin\model\GddsUser();
            $updatedCount = $model::autoUpdateStatus();
            
            // 记录日志
            if ($updatedCount > 0) {
                \think\facade\Log::info("自动更新了 {$updatedCount} 个过期VIP用户状态");
            }
            
            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'message' => "成功更新了 {$updatedCount} 个过期VIP用户状态"
            ];
        } catch (\Exception $e) {
            \think\facade\Log::error("自动更新VIP用户状态失败: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "更新失败: " . $e->getMessage()
            ];
        }
    }

    /**
     * 手动恢复用户VIP状态
     * 管理员手动操作时使用
     */
    public static function manuallyRestoreVipStatus($userId, $newVipTime)
    {
        try {
            $user = \app\admin\model\GddsUser::find($userId);
            if (!$user) {
                return ['success' => false, 'message' => '用户不存在'];
            }

            // 更新VIP时间和状态
            $user->vip_off_time = $newVipTime;
            $user->status = 2; // 设置为启用状态
            $user->save();

            \think\facade\Log::info("手动恢复了用户 {$userId} 的VIP状态，新到期时间: {$newVipTime}");
            
            return [
                'success' => true,
                'message' => 'VIP状态恢复成功'
            ];
        } catch (\Exception $e) {
            \think\facade\Log::error("手动恢复VIP状态失败: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "恢复失败: " . $e->getMessage()
            ];
        }
    }

    /**
     * 获取即将过期的VIP用户列表
     * 用于提醒管理员
     */
    public static function getExpiringSoonUsers($days = 7)
    {
        try {
            $now = time(); // 使用时间戳
            $futureDate = $now + ($days * 24 * 3600); // 计算未来时间戳
            
            $users = \app\admin\model\GddsUser::where('vip_off_time', '>', $now)
                ->where('vip_off_time', '<', $futureDate)
                ->where('status', 2) // 只查询启用状态的用户
                ->field('id,username,vip_off_time,status')
                ->select();
                
            return [
                'success' => true,
                'data' => $users,
                'count' => count($users)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "查询失败: " . $e->getMessage()
            ];
        }
    }
} 