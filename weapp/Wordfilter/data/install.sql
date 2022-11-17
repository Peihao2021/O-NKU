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


-- ----------------------------
-- Table structure for #@__weapp_wordfilter
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_wordfilter`;
CREATE TABLE `#@__weapp_wordfilter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) DEFAULT '' COMMENT '敏感词',
  `status` tinyint(1) DEFAULT '1' COMMENT '启用(1=显示，0=屏蔽)',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='敏感词过滤表';
