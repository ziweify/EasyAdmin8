<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\admin\model\GddsUser;

class UpdateUserStatus extends Command
{
    protected function configure()
    {
        $this->setName('update:user-status')
             ->setDescription('自动更新用户VIP状态');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始更新用户状态...');
        
        try {
            $updatedCount = GddsUser::autoUpdateStatus();
            $output->writeln("状态更新完成，共更新 {$updatedCount} 条记录");
            
            if ($updatedCount > 0) {
                $output->writeln('<info>状态更新成功！</info>');
            } else {
                $output->writeln('<comment>无需更新状态</comment>');
            }
            
        } catch (\Exception $e) {
            $output->writeln('<error>状态更新失败：' . $e->getMessage() . '</error>');
            return 1;
        }
        
        return 0;
    }
} 