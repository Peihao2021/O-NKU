-- ----------------------------------------
-- EyouCms MySQL Data Transfer 
-- 
-- Server         : 127.0.0.1_3306
-- Server Version : 5.6.49-log
-- Host           : 127.0.0.1:3306
-- Database       : sia7_com
-- 
-- Part : #1
-- Version : #v1.4.8
-- Date : 2020-09-14 09:41:16
-- -----------------------------------------

SET FOREIGN_KEY_CHECKS = 0;


-- -----------------------------
-- Table structure for `#@__weapp_pushall`
-- -----------------------------
DROP TABLE IF EXISTS `#@__weapp_pushall`;
CREATE TABLE `#@__weapp_pushall` (
  `aid` int(100) NOT NULL COMMENT '文章ID',
  `baidupushzt` int(1) NOT NULL DEFAULT '0' COMMENT '百度推送状态',
  `shenmapushzt` int(1) NOT NULL DEFAULT '0' COMMENT '神马推送状态',  
  `sogoupushzt` int(1) NOT NULL DEFAULT '0' COMMENT '搜狗推送状态',
  `toutiaopushzt` int(1) NOT NULL DEFAULT '0' COMMENT '头条推送状态',
  PRIMARY KEY (`aid`),
  UNIQUE KEY `aid` (`aid`)
) 
ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#@__weapp_pushalltag`;
CREATE TABLE `#@__weapp_pushalltag` (
  `aid` int(100) NOT NULL COMMENT '文章ID',
  `baidupushzt` int(1) NOT NULL DEFAULT '0' COMMENT '百度推送状态',
  `shenmapushzt` int(1) NOT NULL DEFAULT '0' COMMENT '神马推送状态',  
  `sogoupushzt` int(1) NOT NULL DEFAULT '0' COMMENT '搜狗推送状态',
  `toutiaopushzt` int(1) NOT NULL DEFAULT '0' COMMENT '头条推送状态',
  PRIMARY KEY (`aid`),
  UNIQUE KEY `aid` (`aid`)
) 
ENGINE=MyISAM DEFAULT CHARSET=utf8;
