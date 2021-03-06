#--2014/5/18 

#增加产品的投递开始和结束时间
ALTER TABLE `products` ADD COLUMN start_time timestamp null COMMENT '投递开始时间';
ALTER TABLE `products` ADD COLUMN end_time timestamp null COMMENT '投递结束时间';

#增加用户订阅产品分类表
CREATE TABLE `users_products_category_subscribe` (
  `id` bigint(20) NOT NULL COMMENT '自增的主键',
  `product_category_id` int(10) unsigned NOT NULL COMMENT '产品分类id',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `subscribe_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '用户订阅分类的时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户订阅产品分类列表';

#增加产品匹配的用户类型
ALTER TABLE `products`
ADD COLUMN `user_type`  int NOT NULL DEFAULT 0 COMMENT '匹配的用户类型 0-普通用户 1-高级用户 3-新用户' ;

ALTER TABLE `products`
MODIFY COLUMN `user_type`  set('3','1','2','0') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '匹配的用户类型 0-普通用户 1-高级用户 3-新用户 2-年度用户';



#self package order 
CREATE TABLE `user_order_self_package` (
  `ordernmb` bigint(20) NOT NULL,
  `pay_bank` varchar(20)  NOT NULL,
  `userid` int(10) NOT NULL,
  `price` float NOT NULL,
  `state` tinyint(1) NOT NULL,
  `addtime` datetime NOT NULL,
  `paytime` datetime DEFAULT NULL,
  `trade_no` varchar(30) DEFAULT NULL,
  `address_id` int(11) NOT NULL,
  `sendword` varchar(200) DEFAULT NULL,
  `inventory_out_id` mediumint(9) NOT NULL DEFAULT '0' COMMENT '出库单ID',
  `inventory_out_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '出库单出库状态[0-未完成,1-完成]',
  PRIMARY KEY (`ordernmb`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#buyed products add pay delivery fee status
ALTER TABLE user_order_send_productdetail ADD COLUMN status tinyint(3) NULL COMMENT '0未付费，1未付邮费，2已付邮费, 3无邮费';

#邮费标准
CREATE TABLE `postage_standard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `express_company_id` int(11) DEFAULT NULL,
  `areaId` int(11) DEFAULT NULL,
  `ori_first_heavy` bigint(20) DEFAULT NULL COMMENT '首重(1kg)价格(分)',
  `ori_continued_heavy` bigint(20) DEFAULT NULL COMMENT '续重(1kg)价格(分)',
  `first_heavy` bigint(20) DEFAULT NULL COMMENT '网站首重(1kg)价格(分)',
  `continued_heavy` bigint(20) DEFAULT NULL COMMENT '网站续重(1kg)价格(分)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_company` (`express_company_id`,`areaId`)
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8;
