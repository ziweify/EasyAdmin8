<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\admin\service\VipStatusService;

/**
 * 检查VIP状态命令
 * 可以通过cron定时任务调用
 * 例如：每5分钟执行一次：php think CheckVipStatus
 */
class CheckVipStatus extends Command
{
    protected function configure()
    {
        $this->setName('CheckVipStatus')
            ->setDescription('自动检查并更新过期的VIP用户状态');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始检查VIP状态...');
        
        try {
            $service = new VipStatusService();
            $result = $service::autoUpdateExpiredVipUsers();
            
            if ($result['success']) {
                $output->writeln("检查完成！{$result['message']}");
                
                // 获取即将过期的用户
                $expiringResult = $service::getExpiringSoonUsers(7);
                if ($expiringResult['success'] && $expiringResult['count'] > 0) {
                    $output->writeln("发现 {$expiringResult['count']} 个用户VIP即将过期（7天内）");
                }
            } else {
                $output->writeln("检查失败：{$result['message']}");
                return 1;
            }
            
        } catch (\Exception $e) {
            $output->writeln("执行出错：" . $e->getMessage());
            return 1;
        }
        
        $output->writeln('VIP状态检查完成！');
        return 0;
    }
} 