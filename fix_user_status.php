<?php
/**
 * 用户状态修复脚本
 * 用于修复"到期:未开通"但状态显示为"开启"的问题
 */

// 引入框架
require_once __DIR__ . '/vendor/autoload.php';

// 设置应用路径
define('APP_PATH', __DIR__ . '/app/');

// 启动框架
$app = new \think\App();
$app->http->run();

// 导入用户模型
use app\admin\model\GddsUser;

echo "开始修复用户状态...\n";

try {
    $now = time();
    
    echo "当前时间戳: {$now}\n";
    echo "当前时间: " . date('Y-m-d H:i:s', $now) . "\n\n";
    
    // 1. 查询所有用户的状态
    $allUsers = GddsUser::select();
    echo "总用户数: " . count($allUsers) . "\n";
    
    // 2. 分类统计
    $stats = [
        'no_vip' => 0,        // 未开通VIP
        'expired' => 0,       // VIP已过期  
        'active' => 0,        // VIP有效
        'no_vip_wrong' => 0,  // 未开通但状态为启用的(错误)
        'expired_wrong' => 0, // 已过期但状态为启用的(错误)
    ];
    
    $wrongUsers = [];
    
    foreach ($allUsers as $user) {
        $vipTime = $user->vip_off_time;
        $status = $user->status;
        $username = $user->username;
        
        if (empty($vipTime) || $vipTime == 0) {
            // 未开通VIP
            $stats['no_vip']++;
            if ($status == 2) {
                $stats['no_vip_wrong']++;
                $wrongUsers[] = [
                    'id' => $user->id,
                    'username' => $username,
                    'type' => '未开通VIP但状态为启用',
                    'vip_time' => '未开通',
                    'current_status' => $status
                ];
            }
        } elseif ($vipTime < $now) {
            // VIP已过期
            $stats['expired']++;
            if ($status == 2) {
                $stats['expired_wrong']++;
                $wrongUsers[] = [
                    'id' => $user->id,
                    'username' => $username,
                    'type' => 'VIP已过期但状态为启用',
                    'vip_time' => date('Y-m-d H:i:s', $vipTime),
                    'current_status' => $status
                ];
            }
        } else {
            // VIP有效
            $stats['active']++;
        }
    }
    
    echo "\n状态统计:\n";
    echo "- 未开通VIP用户: {$stats['no_vip']} 个\n";
    echo "- VIP已过期用户: {$stats['expired']} 个\n";
    echo "- VIP有效用户: {$stats['active']} 个\n";
    echo "- 未开通但错误启用: {$stats['no_vip_wrong']} 个\n";
    echo "- 过期但错误启用: {$stats['expired_wrong']} 个\n";
    
    if (!empty($wrongUsers)) {
        echo "\n发现以下用户状态错误:\n";
        foreach ($wrongUsers as $user) {
            echo "- ID:{$user['id']} 用户名:{$user['username']} 类型:{$user['type']} VIP时间:{$user['vip_time']}\n";
        }
        
        echo "\n开始修复...\n";
        
        // 3. 执行修复
        $fixCount = GddsUser::autoUpdateStatus();
        echo "自动更新状态完成，更新了 {$fixCount} 条记录\n";
        
        // 4. 强制刷新所有用户状态
        $refreshCount = GddsUser::forceRefreshAllStatus();
        echo "强制刷新状态完成，更新了 {$refreshCount} 条记录\n";
        
        // 5. 再次检查
        echo "\n修复后再次检查...\n";
        $afterStats = [
            'no_vip_wrong' => GddsUser::where(function($query) {
                $query->where('vip_off_time', '=', 0)
                      ->whereOr('vip_off_time', '=', '')
                      ->whereOr('vip_off_time', 'is', null);
            })->where('status', 2)->count(),
            'expired_wrong' => GddsUser::where('vip_off_time', '<', $now)
                ->where('vip_off_time', '>', 0)
                ->where('status', 2)->count()
        ];
        
        echo "- 修复后仍有未开通但错误启用: {$afterStats['no_vip_wrong']} 个\n";
        echo "- 修复后仍有过期但错误启用: {$afterStats['expired_wrong']} 个\n";
        
        if ($afterStats['no_vip_wrong'] == 0 && $afterStats['expired_wrong'] == 0) {
            echo "\n✅ 所有状态已修复完成！\n";
        } else {
            echo "\n⚠️  仍有部分用户状态需要手动检查\n";
        }
    } else {
        echo "\n✅ 没有发现状态错误的用户！\n";
    }
    
} catch (Exception $e) {
    echo "修复过程中出现错误: " . $e->getMessage() . "\n";
    echo "错误文件: " . $e->getFile() . " 行号: " . $e->getLine() . "\n";
}

echo "\n修复脚本执行完成！\n";