-- 同城预约上门服务系统数据库
-- 创建时间: 2026-05-24

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 管理员表
-- ----------------------------
DROP TABLE IF EXISTS `tc_admin`;
CREATE TABLE `tc_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `realname` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `role_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '角色ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 1正常 0禁用',
  `last_login_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 插入默认管理员
INSERT INTO `tc_admin` VALUES (1, 'admin', MD5('123456'), '超级管理员', '', 1, 1, 0, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ----------------------------
-- 管理角色表
-- ----------------------------
DROP TABLE IF EXISTS `tc_admin_role`;
CREATE TABLE `tc_admin_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名称',
  `auth` text COMMENT '权限节点',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理角色表';

INSERT INTO `tc_admin_role` VALUES (1, '超级管理员', 'all', 1, UNIX_TIMESTAMP());

-- ----------------------------
-- 城市表
-- ----------------------------
DROP TABLE IF EXISTS `tc_city`;
CREATE TABLE `tc_city` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '城市名称',
  `pinyin` varchar(100) NOT NULL DEFAULT '' COMMENT '拼音',
  `letter` char(1) NOT NULL DEFAULT '' COMMENT '首字母',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 1开启 0关闭',
  `is_hot` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否热门',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `letter` (`letter`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='城市表';

-- ----------------------------
-- 会员等级表
-- ----------------------------
DROP TABLE IF EXISTS `tc_member_level`;
CREATE TABLE `tc_member_level` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '等级名称',
  `level` int(11) NOT NULL DEFAULT 0 COMMENT '等级值',
  `min_consume` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '最低消费',
  `discount` decimal(5,2) NOT NULL DEFAULT 100.00 COMMENT '折扣%',
  `privileges` text COMMENT '等级权益 JSON',
  `background` varchar(255) NOT NULL DEFAULT '' COMMENT '等级背景图',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '等级图标',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员等级表';

INSERT INTO `tc_member_level` VALUES 
(1, '普通会员', 1, 0.00, 100.00, '[]', '', '', UNIX_TIMESTAMP()),
(2, '银卡会员', 2, 500.00, 98.00, '[]', '', '', UNIX_TIMESTAMP()),
(3, '金卡会员', 3, 2000.00, 95.00, '[]', '', '', UNIX_TIMESTAMP()),
(4, '钻石会员', 4, 5000.00, 90.00, '[]', '', '', UNIX_TIMESTAMP());

-- ----------------------------
-- 用户表
-- ----------------------------
DROP TABLE IF EXISTS `tc_user`;
CREATE TABLE `tc_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `openid` varchar(100) NOT NULL DEFAULT '' COMMENT '微信openid',
  `unionid` varchar(100) NOT NULL DEFAULT '' COMMENT '微信unionid',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `gender` tinyint(1) NOT NULL DEFAULT 0 COMMENT '性别 0未知 1男 2女',
  `city_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '当前城市ID',
  `level_id` int(11) unsigned NOT NULL DEFAULT 1 COMMENT '会员等级ID',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额',
  `total_consume` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计消费',
  `total_order` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '累计订单数',
  `coupon_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '优惠券数量',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 1正常 0禁用',
  `last_login_time` int(11) unsigned NOT NULL DEFAULT 0,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `openid` (`openid`),
  KEY `mobile` (`mobile`),
  KEY `city_id` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ----------------------------
-- 余额流水表
-- ----------------------------
DROP TABLE IF EXISTS `tc_balance_log`;
CREATE TABLE `tc_balance_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1充值 2消费 3退款 4赠送',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `before_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `after_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `order_no` varchar(50) NOT NULL DEFAULT '' COMMENT '关联订单号',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='余额流水表';

-- ----------------------------
-- 充值套餐表
-- ----------------------------
DROP TABLE IF EXISTS `tc_recharge_package`;
CREATE TABLE `tc_recharge_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '充值金额',
  `give_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '赠送金额',
  `give_level_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '赠送会员等级',
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值套餐表';

-- ----------------------------
-- 用户地址表
-- ----------------------------
DROP TABLE IF EXISTS `tc_user_address`;
CREATE TABLE `tc_user_address` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `city_id` int(11) unsigned NOT NULL DEFAULT 0,
  `city_name` varchar(50) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `house_number` varchar(100) NOT NULL DEFAULT '' COMMENT '门牌号',
  `longitude` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `latitude` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `city_id` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户地址表';

-- ----------------------------
-- 商家表
-- ----------------------------
DROP TABLE IF EXISTS `tc_merchant`;
CREATE TABLE `tc_merchant` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '绑定用户ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '商家名称',
  `logo` varchar(255) NOT NULL DEFAULT '' COMMENT '商家logo',
  `contact` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `city_id` int(11) unsigned NOT NULL DEFAULT 0,
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `longitude` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `latitude` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `business_hours` varchar(100) NOT NULL DEFAULT '' COMMENT '营业时间',
  `description` text COMMENT '商家介绍',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '可提现余额',
  `total_income` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计收入',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待审核 1正常 2禁用',
  `type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '1自营 2加盟',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商家表';

-- ----------------------------
-- 门店表
-- ----------------------------
DROP TABLE IF EXISTS `tc_store`;
CREATE TABLE `tc_store` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` int(11) unsigned NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '门店名称',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '门店图片',
  `contact` varchar(50) NOT NULL DEFAULT '',
  `mobile` varchar(20) NOT NULL DEFAULT '',
  `city_id` int(11) unsigned NOT NULL DEFAULT 0,
  `address` varchar(255) NOT NULL DEFAULT '',
  `longitude` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `latitude` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `business_hours` varchar(100) NOT NULL DEFAULT '',
  `description` text,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `city_id` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门店表';

-- ----------------------------
-- 服务人员表
-- ----------------------------
DROP TABLE IF EXISTS `tc_technician`;
CREATE TABLE `tc_technician` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `merchant_id` int(11) unsigned NOT NULL DEFAULT 0,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `city_id` int(11) unsigned NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `gender` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1男 2女',
  `age` int(11) NOT NULL DEFAULT 0,
  `idcard` varchar(30) NOT NULL DEFAULT '' COMMENT '身份证号',
  `work_years` int(11) NOT NULL DEFAULT 0 COMMENT '工作年限',
  `skills` varchar(255) NOT NULL DEFAULT '' COMMENT '擅长技能',
  `description` text COMMENT '个人简介',
  `rating` decimal(3,2) NOT NULL DEFAULT 5.00 COMMENT '评分',
  `order_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '服务订单数',
  `good_rate` decimal(5,2) NOT NULL DEFAULT 100.00 COMMENT '好评率%',
  `is_online` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否在线',
  `longitude` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `latitude` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_income` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待审核 1正常 2禁用',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1自营 2商家',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `city_id` (`city_id`),
  KEY `is_online` (`is_online`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务人员表';

-- ----------------------------
-- 服务人员档期表
-- ----------------------------
DROP TABLE IF EXISTS `tc_technician_schedule`;
CREATE TABLE `tc_technician_schedule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `technician_id` int(11) unsigned NOT NULL DEFAULT 0,
  `date` date NOT NULL COMMENT '日期',
  `times` text COMMENT '可预约时间段 JSON',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `technician_id` (`technician_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务人员档期表';

-- ----------------------------
-- 服务分类表
-- ----------------------------
DROP TABLE IF EXISTS `tc_service_category`;
CREATE TABLE `tc_service_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图标',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图片',
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务分类表';

-- ----------------------------
-- 城市服务分类关联表
-- ----------------------------
DROP TABLE IF EXISTS `tc_city_category`;
CREATE TABLE `tc_city_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(11) unsigned NOT NULL DEFAULT 0,
  `category_id` int(11) unsigned NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 0,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `city_category` (`city_id`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='城市服务分类关联表';

-- ----------------------------
-- 服务项目表
-- ----------------------------
DROP TABLE IF EXISTS `tc_service`;
CREATE TABLE `tc_service` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned NOT NULL DEFAULT 0,
  `merchant_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '0为自营',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '服务名称',
  `images` text COMMENT '图片列表 JSON',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '起价',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT '服务时长(分钟)',
  `description` text COMMENT '服务说明',
  `notice` text COMMENT '服务须知',
  `sales` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '销量',
  `rating` decimal(3,2) NOT NULL DEFAULT 5.00 COMMENT '评分',
  `review_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '评价数',
  `is_hot` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否热门',
  `is_recommend` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否推荐',
  `service_type` tinyint(1) NOT NULL DEFAULT 3 COMMENT '1上门 2到店 3均可',
  `dispatch_type` tinyint(1) NOT NULL DEFAULT 3 COMMENT '1派单 2抢单 3均可',
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `is_hot` (`is_hot`),
  KEY `is_recommend` (`is_recommend`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务项目表';

-- ----------------------------
-- 服务规格表
-- ----------------------------
DROP TABLE IF EXISTS `tc_service_spec`;
CREATE TABLE `tc_service_spec` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int(11) unsigned NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规格名称',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
  `original_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT '服务时长',
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务规格表';

-- ----------------------------
-- 城市服务项目关联表
-- ----------------------------
DROP TABLE IF EXISTS `tc_city_service`;
CREATE TABLE `tc_city_service` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(11) unsigned NOT NULL DEFAULT 0,
  `service_id` int(11) unsigned NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 0,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `city_service` (`city_id`, `service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='城市服务项目关联表';

-- ----------------------------
-- 服务人员服务项目关联表
-- ----------------------------
DROP TABLE IF EXISTS `tc_technician_service`;
CREATE TABLE `tc_technician_service` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `technician_id` int(11) unsigned NOT NULL DEFAULT 0,
  `service_id` int(11) unsigned NOT NULL DEFAULT 0,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `technician_service` (`technician_id`, `service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务人员服务项目关联表';

-- ----------------------------
-- 订单表
-- ----------------------------
DROP TABLE IF EXISTS `tc_order`;
CREATE TABLE `tc_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `city_id` int(11) unsigned NOT NULL DEFAULT 0,
  `service_id` int(11) unsigned NOT NULL DEFAULT 0,
  `spec_id` int(11) unsigned NOT NULL DEFAULT 0,
  `merchant_id` int(11) unsigned NOT NULL DEFAULT 0,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `technician_id` int(11) unsigned NOT NULL DEFAULT 0,
  `service_name` varchar(255) NOT NULL DEFAULT '',
  `spec_name` varchar(100) NOT NULL DEFAULT '',
  `service_image` varchar(255) NOT NULL DEFAULT '',
  `service_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1上门 2到店',
  `dispatch_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1派单 2抢单',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '服务价格',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT '服务时长',
  `service_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '预约服务时间',
  `address_id` int(11) unsigned NOT NULL DEFAULT 0,
  `address_info` text COMMENT '地址信息 JSON',
  `contact_name` varchar(50) NOT NULL DEFAULT '',
  `contact_mobile` varchar(20) NOT NULL DEFAULT '',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '订单备注',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单总额',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
  `coupon_id` int(11) unsigned NOT NULL DEFAULT 0,
  `coupon_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额支付',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
  `pay_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未支付 1微信 2余额 3组合支付',
  `pay_time` int(11) unsigned NOT NULL DEFAULT 0,
  `transaction_id` varchar(100) NOT NULL DEFAULT '' COMMENT '微信支付单号',
  `status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '0待付款 1待派单 2待接单 3待服务 4服务中 5待支付差额 6已完成 7已取消 8已退款',
  `cancel_reason` varchar(255) NOT NULL DEFAULT '',
  `is_appeal` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否投诉',
  `appeal_content` text COMMENT '投诉内容',
  `is_review` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否评价',
  `addon_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '加项金额',
  `diff_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '补差金额',
  `technician_income` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '技师收入',
  `merchant_income` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '商家收入',
  `platform_income` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '平台收入',
  `start_service_time` int(11) unsigned NOT NULL DEFAULT 0,
  `end_service_time` int(11) unsigned NOT NULL DEFAULT 0,
  `finish_time` int(11) unsigned NOT NULL DEFAULT 0,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `technician_id` (`technician_id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `city_id` (`city_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- ----------------------------
-- 订单加项表
-- ----------------------------
DROP TABLE IF EXISTS `tc_order_addon`;
CREATE TABLE `tc_order_addon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL DEFAULT 0,
  `service_id` int(11) unsigned NOT NULL DEFAULT 0,
  `spec_id` int(11) unsigned NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待支付 1已支付',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单加项表';

-- ----------------------------
-- 派单抢单记录表
-- ----------------------------
DROP TABLE IF EXISTS `tc_order_dispatch`;
CREATE TABLE `tc_order_dispatch` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL DEFAULT 0,
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1派单 2抢单',
  `technician_id` int(11) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待处理 1已接受 2已拒绝 3已取消',
  `dispatch_time` int(11) unsigned NOT NULL DEFAULT 0,
  `handle_time` int(11) unsigned NOT NULL DEFAULT 0,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `technician_id` (`technician_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='派单抢单记录表';

-- ----------------------------
-- 优惠券表
-- ----------------------------
DROP TABLE IF EXISTS `tc_coupon`;
CREATE TABLE `tc_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '优惠券名称',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1满减 2折扣',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '满减金额',
  `discount` decimal(5,2) NOT NULL DEFAULT 100.00 COMMENT '折扣%',
  `min_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '最低使用金额',
  `total_count` int(11) NOT NULL DEFAULT 0 COMMENT '发放总量 0不限',
  `used_count` int(11) unsigned NOT NULL DEFAULT 0,
  `receive_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1直接领取 2口令领取 3活动赠送',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '兑换口令',
  `valid_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1固定日期 2领取后N天',
  `start_time` int(11) unsigned NOT NULL DEFAULT 0,
  `end_time` int(11) unsigned NOT NULL DEFAULT 0,
  `valid_days` int(11) NOT NULL DEFAULT 0,
  `category_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '可用分类ID',
  `service_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '可用服务ID',
  `city_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '可用城市ID',
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='优惠券表';

-- ----------------------------
-- 用户优惠券表
-- ----------------------------
DROP TABLE IF EXISTS `tc_user_coupon`;
CREATE TABLE `tc_user_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `coupon_id` int(11) unsigned NOT NULL DEFAULT 0,
  `order_id` int(11) unsigned NOT NULL DEFAULT 0,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(5,2) NOT NULL DEFAULT 100.00,
  `min_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `name` varchar(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未使用 1已使用 2已过期',
  `start_time` int(11) unsigned NOT NULL DEFAULT 0,
  `end_time` int(11) unsigned NOT NULL DEFAULT 0,
  `use_time` int(11) unsigned NOT NULL DEFAULT 0,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户优惠券表';

-- ----------------------------
-- 套餐卡表
-- ----------------------------
DROP TABLE IF EXISTS `tc_package_card`;
CREATE TABLE `tc_package_card` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `image` varchar(255) NOT NULL DEFAULT '',
  `service_ids` text COMMENT '服务项目ID JSON',
  `total_count` int(11) NOT NULL DEFAULT 0 COMMENT '总次数',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '售价',
  `original_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
  `valid_days` int(11) NOT NULL DEFAULT 0 COMMENT '有效期 0永久',
  `description` text,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='套餐卡表';

-- ----------------------------
-- 用户套餐卡表
-- ----------------------------
DROP TABLE IF EXISTS `tc_user_package`;
CREATE TABLE `tc_user_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `package_id` int(11) unsigned NOT NULL DEFAULT 0,
  `order_no` varchar(50) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `image` varchar(255) NOT NULL DEFAULT '',
  `total_count` int(11) NOT NULL DEFAULT 0,
  `used_count` int(11) unsigned NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `start_time` int(11) unsigned NOT NULL DEFAULT 0,
  `end_time` int(11) unsigned NOT NULL DEFAULT 0,
  `is_refund` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否申请退款',
  `refund_reason` varchar(255) NOT NULL DEFAULT '',
  `refund_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未退款 1退款中 2已退款 3拒绝',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1正常 2已用完 3已过期 4已退款',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `package_id` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户套餐卡表';

-- ----------------------------
-- 评价表
-- ----------------------------
DROP TABLE IF EXISTS `tc_review`;
CREATE TABLE `tc_review` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `service_id` int(11) unsigned NOT NULL DEFAULT 0,
  `technician_id` int(11) unsigned NOT NULL DEFAULT 0,
  `merchant_id` int(11) unsigned NOT NULL DEFAULT 0,
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1好评 2中评 3差评',
  `rating` tinyint(1) NOT NULL DEFAULT 5 COMMENT '评分1-5',
  `content` text COMMENT '评价内容',
  `images` text COMMENT '评价图片 JSON',
  `technician_rating` tinyint(1) NOT NULL DEFAULT 5 COMMENT '技师评分',
  `service_rating` tinyint(1) NOT NULL DEFAULT 5 COMMENT '服务评分',
  `environment_rating` tinyint(1) NOT NULL DEFAULT 5 COMMENT '环境评分',
  `reply_content` text COMMENT '回复内容',
  `reply_time` int(11) unsigned NOT NULL DEFAULT 0,
  `is_show` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `service_id` (`service_id`),
  KEY `technician_id` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价表';

-- ----------------------------
-- 轮播图表
-- ----------------------------
DROP TABLE IF EXISTS `tc_banner`;
CREATE TABLE `tc_banner` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(11) NOT NULL DEFAULT 0 COMMENT '0全部城市',
  `title` varchar(100) NOT NULL DEFAULT '',
  `image` varchar(255) NOT NULL DEFAULT '',
  `link_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0无 1服务详情 2分类 3网页',
  `link_id` int(11) unsigned NOT NULL DEFAULT 0,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='轮播图表';

-- ----------------------------
-- 公告表
-- ----------------------------
DROP TABLE IF EXISTS `tc_notice`;
CREATE TABLE `tc_notice` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` text,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公告表';

-- ----------------------------
-- 弹窗表
-- ----------------------------
DROP TABLE IF EXISTS `tc_popup`;
CREATE TABLE `tc_popup` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(100) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1图片 2文字',
  `image` varchar(255) NOT NULL DEFAULT '',
  `content` text,
  `link_type` tinyint(1) NOT NULL DEFAULT 0,
  `link_id` int(11) unsigned NOT NULL DEFAULT 0,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `start_time` int(11) unsigned NOT NULL DEFAULT 0,
  `end_time` int(11) unsigned NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='弹窗表';

-- ----------------------------
-- 帮助中心表
-- ----------------------------
DROP TABLE IF EXISTS `tc_help`;
CREATE TABLE `tc_help` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` text,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帮助中心表';

-- ----------------------------
-- 操作日志表
-- ----------------------------
DROP TABLE IF EXISTS `tc_admin_log`;
CREATE TABLE `tc_admin_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) unsigned NOT NULL DEFAULT 0,
  `admin_name` varchar(50) NOT NULL DEFAULT '',
  `module` varchar(50) NOT NULL DEFAULT '',
  `action` varchar(50) NOT NULL DEFAULT '',
  `content` text,
  `ip` varchar(50) NOT NULL DEFAULT '',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

SET FOREIGN_KEY_CHECKS = 1;
