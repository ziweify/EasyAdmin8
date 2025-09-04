-- 确保用户表有必要的字段
ALTER TABLE `ea8_gdds_user` 
ADD COLUMN IF NOT EXISTS `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
ADD COLUMN IF NOT EXISTS `last_login_time` int(11) DEFAULT NULL COMMENT '最后登录时间',
ADD COLUMN IF NOT EXISTS `api_public_key` text DEFAULT NULL COMMENT 'API公钥',
ADD COLUMN IF NOT EXISTS `api_private_key` text DEFAULT NULL COMMENT 'API私钥',
ADD COLUMN IF NOT EXISTS `api_token` varchar(255) DEFAULT NULL COMMENT 'API Token';

-- 创建一个测试用户（如果不存在）
INSERT IGNORE INTO `ea8_gdds_user` (`id`, `username`, `password`, `status`, `create_time`, `update_time`) 
VALUES (1, 'testuser', 'testpass', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());