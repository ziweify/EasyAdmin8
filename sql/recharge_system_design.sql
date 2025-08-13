-- =============================================
-- 充值卡系统数据库设计
-- 设计原则：数据完整性、业务扩展性、性能优化
-- =============================================

-- 1. 充值卡主表
CREATE TABLE `ea8_recharge_card` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `card_no` varchar(32) NOT NULL COMMENT '充值卡号（唯一）',
  `card_type` tinyint(1) DEFAULT '1' COMMENT '卡类型：1普通卡，2VIP卡，3活动卡',
  `amount` decimal(10,2) NOT NULL COMMENT '充值金额',
  `original_amount` decimal(10,2) NOT NULL COMMENT '原始金额（用于活动卡）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1未使用，2已使用，3已过期，4已作废',
  `batch_no` varchar(32) DEFAULT NULL COMMENT '批次号（用于批量管理）',
  `expire_time` int(11) DEFAULT NULL COMMENT '过期时间戳',
  `used_user_id` bigint(20) DEFAULT NULL COMMENT '使用用户ID',
  `used_time` int(11) DEFAULT NULL COMMENT '使用时间戳',
  `used_ip` varchar(45) DEFAULT NULL COMMENT '使用IP地址',
  `used_device` varchar(255) DEFAULT NULL COMMENT '使用设备信息',
  `creator_id` bigint(20) DEFAULT NULL COMMENT '创建人ID',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_card_no` (`card_no`),
  KEY `idx_status` (`status`),
  KEY `idx_batch_no` (`batch_no`),
  KEY `idx_used_user_id` (`used_user_id`),
  KEY `idx_expire_time` (`expire_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值卡主表';

-- 2. 充值卡批次表
CREATE TABLE `ea8_recharge_batch` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `batch_no` varchar(32) NOT NULL COMMENT '批次号（唯一）',
  `batch_name` varchar(100) NOT NULL COMMENT '批次名称',
  `card_type` tinyint(1) DEFAULT '1' COMMENT '卡类型',
  `amount` decimal(10,2) NOT NULL COMMENT '单卡金额',
  `quantity` int(11) NOT NULL COMMENT '生成数量',
  `used_quantity` int(11) DEFAULT '0' COMMENT '已使用数量',
  `expire_days` int(11) DEFAULT '365' COMMENT '有效期天数',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1正常，2已停用',
  `creator_id` bigint(20) DEFAULT NULL COMMENT '创建人ID',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_batch_no` (`batch_no`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值卡批次表';

-- 3. 用户余额表
CREATE TABLE `ea8_user_balance` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint(20) NOT NULL COMMENT '用户ID',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '当前余额',
  `frozen_balance` decimal(10,2) DEFAULT '0.00' COMMENT '冻结余额',
  `total_recharge` decimal(10,2) DEFAULT '0.00' COMMENT '累计充值金额',
  `total_consume` decimal(10,2) DEFAULT '0.00' COMMENT '累计消费金额',
  `last_recharge_time` int(11) DEFAULT NULL COMMENT '最后充值时间',
  `last_consume_time` int(11) DEFAULT NULL COMMENT '最后消费时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`),
  KEY `idx_balance` (`balance`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户余额表';

-- 4. 充值记录表
CREATE TABLE `ea8_recharge_record` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `record_no` varchar(32) NOT NULL COMMENT '记录号（唯一）',
  `user_id` bigint(20) NOT NULL COMMENT '用户ID',
  `card_id` bigint(20) DEFAULT NULL COMMENT '充值卡ID（卡充值）',
  `recharge_type` tinyint(1) NOT NULL COMMENT '充值类型：1卡充值，2在线支付，3管理员充值，4活动赠送',
  `amount` decimal(10,2) NOT NULL COMMENT '充值金额',
  `before_balance` decimal(10,2) NOT NULL COMMENT '充值前余额',
  `after_balance` decimal(10,2) NOT NULL COMMENT '充值后余额',
  `payment_method` varchar(50) DEFAULT NULL COMMENT '支付方式',
  `payment_no` varchar(100) DEFAULT NULL COMMENT '支付单号',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1成功，2失败，3处理中',
  `operator_id` bigint(20) DEFAULT NULL COMMENT '操作人ID',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_record_no` (`record_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_card_id` (`card_id`),
  KEY `idx_recharge_type` (`recharge_type`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值记录表';

-- 5. 消费记录表
CREATE TABLE `ea8_consume_record` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `record_no` varchar(32) NOT NULL COMMENT '记录号（唯一）',
  `user_id` bigint(20) NOT NULL COMMENT '用户ID',
  `consume_type` tinyint(1) NOT NULL COMMENT '消费类型：1购买商品，2服务费用，3系统扣除',
  `amount` decimal(10,2) NOT NULL COMMENT '消费金额',
  `before_balance` decimal(10,2) NOT NULL COMMENT '消费前余额',
  `after_balance` decimal(10,2) NOT NULL COMMENT '消费后余额',
  `business_id` varchar(100) DEFAULT NULL COMMENT '业务ID（订单号等）',
  `business_type` varchar(50) DEFAULT NULL COMMENT '业务类型',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1成功，2失败，3退款',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_record_no` (`record_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_consume_type` (`consume_type`),
  KEY `idx_business_id` (`business_id`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='消费记录表';

-- 6. 充值卡活动表
CREATE TABLE `ea8_recharge_activity` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `activity_name` varchar(100) NOT NULL COMMENT '活动名称',
  `activity_type` tinyint(1) DEFAULT '1' COMMENT '活动类型：1充值送余额，2充值送积分，3充值送商品',
  `start_time` int(11) NOT NULL COMMENT '开始时间',
  `end_time` int(11) NOT NULL COMMENT '结束时间',
  `min_amount` decimal(10,2) DEFAULT '0.00' COMMENT '最低充值金额',
  `max_amount` decimal(10,2) DEFAULT NULL COMMENT '最高充值金额',
  `bonus_type` tinyint(1) DEFAULT '1' COMMENT '奖励类型：1固定金额，2百分比',
  `bonus_value` decimal(10,2) NOT NULL COMMENT '奖励值',
  `max_bonus` decimal(10,2) DEFAULT NULL COMMENT '最大奖励金额',
  `user_limit` int(11) DEFAULT '1' COMMENT '用户参与次数限制',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用，2禁用',
  `creator_id` bigint(20) DEFAULT NULL COMMENT '创建人ID',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_start_time` (`start_time`),
  KEY `idx_end_time` (`end_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值活动表';

-- 7. 用户活动参与记录表
CREATE TABLE `ea8_user_activity_record` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint(20) NOT NULL COMMENT '用户ID',
  `activity_id` bigint(20) NOT NULL COMMENT '活动ID',
  `recharge_record_id` bigint(20) NOT NULL COMMENT '充值记录ID',
  `bonus_amount` decimal(10,2) NOT NULL COMMENT '奖励金额',
  `bonus_type` varchar(50) DEFAULT NULL COMMENT '奖励类型',
  `bonus_data` text DEFAULT NULL COMMENT '奖励数据（JSON格式）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1已发放，2发放失败',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_recharge_record_id` (`recharge_record_id`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户活动参与记录表';

-- 8. 系统配置表
CREATE TABLE `ea8_recharge_config` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text DEFAULT NULL COMMENT '配置值',
  `config_type` varchar(50) DEFAULT 'string' COMMENT '配置类型',
  `description` varchar(255) DEFAULT NULL COMMENT '配置描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用，2禁用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值系统配置表';

-- =============================================
-- 初始化数据
-- =============================================

-- 插入系统配置
INSERT INTO `ea8_recharge_config` (`config_key`, `config_value`, `config_type`, `description`, `status`, `create_time`) VALUES
('min_recharge_amount', '1.00', 'decimal', '最小充值金额', 1, UNIX_TIMESTAMP()),
('max_recharge_amount', '10000.00', 'decimal', '最大充值金额', 1, UNIX_TIMESTAMP()),
('default_card_expire_days', '365', 'integer', '默认充值卡有效期（天）', 1, UNIX_TIMESTAMP()),
('enable_recharge_card', '1', 'boolean', '是否启用充值卡功能', 1, UNIX_TIMESTAMP()),
('enable_online_payment', '1', 'boolean', '是否启用在线支付', 1, UNIX_TIMESTAMP()),
('recharge_notification', '1', 'boolean', '是否启用充值通知', 1, UNIX_TIMESTAMP());

-- =============================================
-- 索引优化建议
-- =============================================

-- 复合索引（根据查询需求添加）
-- ALTER TABLE `ea8_recharge_card` ADD INDEX `idx_status_expire` (`status`, `expire_time`);
-- ALTER TABLE `ea8_recharge_record` ADD INDEX `idx_user_time` (`user_id`, `create_time`);
-- ALTER TABLE `ea8_consume_record` ADD INDEX `idx_user_time` (`user_id`, `create_time`);

-- =============================================
-- 分区表建议（大数据量时）
-- =============================================

-- 充值记录表按月分区（示例）
-- ALTER TABLE `ea8_recharge_record` PARTITION BY RANGE (YEAR(FROM_UNIXTIME(create_time)) * 100 + MONTH(FROM_UNIXTIME(create_time))) (
--     PARTITION p202401 VALUES LESS THAN (202402),
--     PARTITION p202402 VALUES LESS THAN (202403),
--     PARTITION p202403 VALUES LESS THAN (202404)
-- ); 