DROP TABLE IF EXISTS `#@__weapp_picture_cleaning`;
CREATE TABLE `#@__weapp_picture_cleaning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_of_ontents` varchar(50) DEFAULT '' COMMENT '移动的目录文件夹名称',
  `url` varchar(255) DEFAULT '' COMMENT '文件地址',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：0=正常，1=清理',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;