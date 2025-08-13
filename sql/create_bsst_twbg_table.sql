-- 创建 bsst_twbg 表
CREATE TABLE `ea8_bsst_twbg` (
  `issueid` varchar(32) NOT NULL COMMENT '开奖期号',
  `open_data` varchar(255) DEFAULT NULL COMMENT '开奖数据,字符串格式',
  `p1` varchar(2) DEFAULT NULL COMMENT 'P1',
  `p2` varchar(2) DEFAULT NULL COMMENT 'P2',
  `p3` varchar(2) DEFAULT NULL COMMENT 'P3',
  `p4` varchar(2) DEFAULT NULL COMMENT 'P4',
  `p5` varchar(2) DEFAULT NULL COMMENT 'P5',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(1:未使用,2:已使用)',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `open_time` int(11) DEFAULT '0' COMMENT '开奖时间, 标准开奖时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`issueid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='bsst开奖数据表'; 