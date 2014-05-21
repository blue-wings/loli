#--2014/5/18 

#增加产品的投递开始和结束时间
ALTER TABLE `products` ADD COLUMN start_time timestamp null;
ALTER TABLE `products` ADD COLUMN end_time timestamp null;

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
ADD COLUMN `user_type`  int NOT NULL DEFAULT 0 COMMENT '匹配的用户类型 0-普通用户 1-高级用户 3-新用户' AFTER `end_time`;
