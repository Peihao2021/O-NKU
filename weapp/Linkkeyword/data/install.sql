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
-- Table structure for #@__weapp_linkkeyword
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_linkkeyword`;
CREATE TABLE `#@__weapp_linkkeyword` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '关键词',
  `url` varchar(200) NOT NULL DEFAULT '' COMMENT '链接地址',
  `target` tinyint(1) NOT NULL DEFAULT '0' COMMENT '新窗口',
  `sort_order` int(10) NOT NULL DEFAULT '0' COMMENT '排序号',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1=启用，0=禁用)',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;