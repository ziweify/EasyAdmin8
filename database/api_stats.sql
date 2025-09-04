-- API调用统计表
CREATE TABLE `ea8_api_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `date` date NOT NULL COMMENT '日期',
  `api_method` varchar(100) NOT NULL COMMENT 'API方法名',
  `total_calls` int(11) DEFAULT '0' COMMENT '总调用次数',
  `success_calls` int(11) DEFAULT '0' COMMENT '成功调用次数',
  `failed_calls` int(11) DEFAULT '0' COMMENT '失败调用次数',
  `avg_response_time` decimal(10,2) DEFAULT '0.00' COMMENT '平均响应时间(毫秒)',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_date_method` (`user_id`,`date`,`api_method`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API调用统计表';

-- API调用详细日志表
CREATE TABLE `ea8_api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `api_method` varchar(100) NOT NULL COMMENT 'API方法名',
  `request_url` varchar(500) NOT NULL COMMENT '请求URL',
  `request_method` varchar(10) NOT NULL COMMENT '请求方式(GET/POST)',
  `request_params` text COMMENT '请求参数',
  `response_code` int(3) NOT NULL COMMENT '响应状态码',
  `response_data` text COMMENT '响应数据',
  `response_time` decimal(10,2) NOT NULL COMMENT '响应时间(毫秒)',
  `ip_address` varchar(45) NOT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) COMMENT 'User Agent',
  `api_token` varchar(100) COMMENT 'API Token',
  `error_message` varchar(500) COMMENT '错误信息',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_api_method` (`api_method`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_response_code` (`response_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API调用详细日志表';

-- 系统日志表(如果不存在)
CREATE TABLE IF NOT EXISTS `ea8_system_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名',
  `action` varchar(50) NOT NULL COMMENT '操作动作',
  `module` varchar(50) NOT NULL COMMENT '模块名',
  `controller` varchar(50) NOT NULL COMMENT '控制器名',
  `method` varchar(50) NOT NULL COMMENT '方法名',
  `ip` varchar(45) NOT NULL COMMENT 'IP地址',
  `description` varchar(500) NOT NULL COMMENT '操作描述',
  `user_agent` varchar(500) COMMENT 'User Agent',
  `request_data` text COMMENT '请求数据',
  `response_data` text COMMENT '响应数据',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统操作日志表';