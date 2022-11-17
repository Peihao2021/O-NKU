/*
Navicat MySQL Data Transfer

Source Server         : localhost_3306
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : eyoucms_develop

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2018-09-13 14:30:27
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for #@__weapp_spider_visit
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_spider_visit`;
CREATE TABLE `#@__weapp_spider_visit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spider` int(10) DEFAULT '0' COMMENT '蜘蛛分类',
  `useragent` varchar(500) DEFAULT '' COMMENT '蜘蛛标识',
  `url` varchar(500) DEFAULT '' COMMENT '抓取的URL',
  `ip` varchar(20) DEFAULT '' COMMENT '蜘蛛ip',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `spider` (`spider`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for #@__weapp_spider_tongji
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_spider_tongji`;
CREATE TABLE `#@__weapp_spider_tongji` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spider` int(10) NOT NULL DEFAULT '0' COMMENT '蜘蛛分类',
  `total` int(10) NOT NULL DEFAULT '0' COMMENT '蜘蛛统计',
  `month` int(10) NOT NULL DEFAULT '0' COMMENT '最近30天',
  `week` int(10) NOT NULL DEFAULT '0' COMMENT '当周',
  `pre_week` int(10) NOT NULL DEFAULT '0' COMMENT '上一周',
  `day` int(10) NOT NULL DEFAULT '0' COMMENT '当天',
  `pre_day` int(10) NOT NULL DEFAULT '0' COMMENT '昨天',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `spider` (`spider`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
