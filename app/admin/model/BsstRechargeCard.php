<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class BsstRechargeCard extends TimeModel
{
    protected $name = 'bsst_recharge_card';
    
    // 状态常量
    const STATUS_UNUSED = 1;    // 未使用
    const STATUS_USED = 2;      // 已使用
    const STATUS_EXPIRED = 3;   // 已过期
    const STATUS_DISABLED = 4;  // 已作废
    
    protected function getOptions(): array
    {
        return [
            'deleteTime' => "delete_time",
            'autoWriteTimestamp' => true,
            'createTime' => 'create_time',
            'updateTime' => 'update_time',
        ];
    }
    
    /**
     * 生成充值卡号
     */
    public static function generateCardNo(): string
    {
        $prefix = 'RC';
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return $prefix . $timestamp . $random;
    }
    
    /**
     * 检查充值卡是否可用
     */
    public function isUsable(): bool
    {
        return $this->status == self::STATUS_UNUSED && 
               ($this->expire_time == 0 || $this->expire_time > time());
    }
    
    /**
     * 使用充值卡
     */
    public function useCard(int $userId): bool
    {
        if (!$this->isUsable()) {
            return false;
        }
        
        $this->status = self::STATUS_USED;
        $this->used_user_id = $userId;
        $this->used_time = time();
        
        return $this->save();
    }
    
    public static array $notes = [
        'status' => [
            1 => '未使用',
            2 => '已使用', 
            3 => '已过期',
            4 => '已作废'
        ],
        'card_type' => [
            1 => '普通卡',
            2 => '大客户卡',
            3 => '活动卡'
        ],
        'recharge_type' => [
            1 => '日卡',
            2 => '周卡',
            3 => '月卡'
        ],
        'settle_status' => [
            0 => '挂账',
            1 => '已结'
        ]
    ];
}