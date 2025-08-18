-- 更新 bsst_recharge_card 表，添加新字段
ALTER TABLE `ea8_bsst_recharge_card` 
ADD COLUMN `recharge_type` tinyint(1) DEFAULT '1' COMMENT '充值类型(1:日卡,2:周卡,3:月卡)' AFTER `card_type`,
ADD COLUMN `settle_status` tinyint(1) DEFAULT '0' COMMENT '结算状态(0:挂账,1:已结)' AFTER `recharge_type`,
ADD COLUMN `count` int(11) DEFAULT '1' COMMENT '使用次数' AFTER `settle_status`;

-- 添加索引
ALTER TABLE `ea8_bsst_recharge_card` 
ADD INDEX `idx_recharge_type` (`recharge_type`),
ADD INDEX `idx_settle_status` (`settle_status`),
ADD INDEX `idx_count` (`count`); 