SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for api_commission
-- ----------------------------
DROP TABLE IF EXISTS `api_commission`;
CREATE TABLE `api_commission`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `agent_id` int(10) UNSIGNED NOT NULL,
  `level` tinyint(1) UNSIGNED NOT NULL,
  `order_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `order_total` decimal(10, 2) UNSIGNED NOT NULL,
  `ratio` decimal(5, 2) UNSIGNED NOT NULL,
  `amount` decimal(10, 2) UNSIGNED NOT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `remark` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_commission
-- ----------------------------

-- ----------------------------
-- Table structure for api_coupon
-- ----------------------------
DROP TABLE IF EXISTS `api_coupon`;
CREATE TABLE `api_coupon`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `from_user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('points','vip','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `plan_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `discount_type` enum('fixed','percent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `discount_value` decimal(10, 2) NOT NULL,
  `min_purchase` decimal(10, 2) NOT NULL DEFAULT 0.00,
  `start_time` date NOT NULL,
  `end_time` date NOT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `usage_time` int(10) UNSIGNED NULL DEFAULT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL,
  `code` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `coupon_code`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_coupon
-- ----------------------------

-- ----------------------------
-- Table structure for api_douyinim_account
-- ----------------------------
DROP TABLE IF EXISTS `api_douyinim_account`;
CREATE TABLE `api_douyinim_account`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `cookie` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `remark` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` timestamp NULL DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_estonian_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_douyinim_account
-- ----------------------------

-- ----------------------------
-- Table structure for api_land
-- ----------------------------
DROP TABLE IF EXISTS `api_land`;
CREATE TABLE `api_land`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `type` char(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `config` json NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_land
-- ----------------------------
INSERT INTO `api_land` VALUES (1, 0, '默认模板', 'default', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-land/default.png', '{\"logo\": {\"url\": \"https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/image/686a815c97eb5.svg\", \"width\": 54, \"height\": 54, \"borderRadius\": 8}, \"title\": {\"text\": \"如无法自动跳转，请点击按钮添加\", \"color\": \"#333333\", \"fontSize\": 16}, \"button\": {\"text\": \"前往微信添加好友\", \"color\": \"#ffffff\", \"fontSize\": 18, \"borderRadius\": 12, \"animationType\": \"pulse\", \"backgroundColor\": \"#07c160\", \"enableAnimation\": false}}');
INSERT INTO `api_land` VALUES (2, 0, '默认模版 2️⃣', 'default2', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-land/default2.png', '{\"logo\": {\"url\": \"https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/image/6a2baf5821a5d.svg\", \"width\": 80, \"height\": 80, \"borderRadius\": 100, \"animationType\": \"bounce\", \"circleAnimation\": \"ripple\"}, \"button\": {\"text\": \"点击前往微信\", \"color\": \"#ffffff\", \"fontSize\": 20, \"borderRadius\": 30, \"animationType\": \"pulse\", \"backgroundColor\": \"#07c160\", \"enableAnimation\": true}, \"notice\": {\"icon\": \"checkbox-filled\", \"text\": \"本链接已通过 SSL 安全加密，请放心访问\", \"color\": \"#07c160\", \"fontSize\": 12, \"borderRadius\": 0, \"backgroundColor\": \"#e0ffee\"}}');
INSERT INTO `api_land` VALUES (3, 0, '微信模板', 'wxid', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-land/wxid.png', '{\"auth\": {\"url\": \"https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/image/686a8300d6a33.svg\", \"width\": 20, \"height\": 20, \"borderRadius\": 0}, \"desc\": {\"text\": \"您可将获客链接配置在任何场景，客户无需扫码，点击链接 1 步添加好友。\", \"color\": \"#777777\", \"fontSize\": 14}, \"logo\": {\"url\": \"https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/image/688dbde8d2a55.png\", \"width\": 60, \"height\": 60, \"borderRadius\": 12}, \"name\": {\"text\": \"获客大师\", \"color\": \"#333333\", \"fontSize\": 18}, \"button\": {\"text\": \"一键复制微信号前往微信添加\", \"color\": \"#ffffff\", \"fontSize\": 17, \"borderRadius\": 8, \"animationType\": \"pulse\", \"backgroundColor\": \"#07c160\", \"enableAnimation\": false}, \"account\": {\"text\": \"jumpwx_cn\", \"type\": \"微信号\", \"color\": \"#777777\", \"fontSize\": 14}}');
INSERT INTO `api_land` VALUES (4, 0, '问答模板', 'dialog', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-land/dialog.png', '{\"button\": {\"text\": \"点击了解详情\", \"color\": \"#ffffff\", \"fontSize\": 18, \"borderRadius\": 8, \"animationType\": \"jump\", \"backgroundColor\": \"#07c160\", \"enableAnimation\": true}, \"images\": {\"myAvatar\": \"https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/image/687f59aeb7732.svg\", \"bannerUrl\": \"https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/image/687f8c0bc92d6.png\", \"clientAvatar\": \"https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/image/687f59ab5f1d7.svg\"}, \"messages\": {\"endMessage\": \"好的，请点击下方按钮添加微信详细了解！\", \"rejectMessage\": \"抱歉，不适合您。\", \"welcomeMessage\": \"您好，欢迎咨询！\"}, \"questions\": [{\"id\": \"1\", \"options\": [{\"text\": \"是的\", \"reply\": \"2\", \"action\": \"reply\", \"textColor\": \"#ffffff\", \"backgroundColor\": \"#45bf08\"}, {\"text\": \"不是\", \"reply\": \"1\", \"action\": \"reject\", \"textColor\": \"#ffffff\", \"backgroundColor\": \"#999999\"}], \"question\": \"您想咨询的是抖音卡片跳转微信引流加粉吗？\"}, {\"id\": \"2\", \"options\": [{\"text\": \"愿意付费\", \"reply\": \"1\", \"action\": \"end\", \"textColor\": \"#ffffff\", \"backgroundColor\": \"#5bc726\"}, {\"text\": \"只想白嫖\", \"reply\": \"1\", \"action\": \"reject\", \"textColor\": \"#ffffff\", \"backgroundColor\": \"#969696\"}], \"question\": \"您愿意为此付费吗？\"}]}');

-- ----------------------------
-- Table structure for api_link
-- ----------------------------
DROP TABLE IF EXISTS `api_link`;
CREATE TABLE `api_link`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `land_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `channel_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `end_time` date NOT NULL,
  `remark` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `page_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `page_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `page_icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `www_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `code` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `openlink` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `openlink_expires` int(10) UNSIGNED NULL DEFAULT NULL,
  `txqr_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_link
-- ----------------------------

-- ----------------------------
-- Table structure for api_link_channel
-- ----------------------------
DROP TABLE IF EXISTS `api_link_channel`;
CREATE TABLE `api_link_channel`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(1) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_link_channel
-- ----------------------------
INSERT INTO `api_link_channel` VALUES (1, 'wxa', '微信小程序', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/wxa.png', 1, 0);
INSERT INTO `api_link_channel` VALUES (2, 'wxmp', '公众号文章', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/wxmp.png', 2, 0);
INSERT INTO `api_link_channel` VALUES (3, 'wxwork', '企业微信', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/wxwork.png', 3, 0);
INSERT INTO `api_link_channel` VALUES (4, 'kdocs', '金山文档', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/kdocs.png', 4, 0);
INSERT INTO `api_link_channel` VALUES (5, 'cli', '草料-自动', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/cli.png', 5, 0);
INSERT INTO `api_link_channel` VALUES (6, 'jdjz', '京造-自动', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/jdjz.png', 6, 0);
INSERT INTO `api_link_channel` VALUES (8, 'zhaopin', '智联-自动', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/zhaopin.png', 8, 0);
INSERT INTO `api_link_channel` VALUES (9, 'zto', '中通-自动', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/zto.png', 9, 0);
INSERT INTO `api_link_channel` VALUES (10, 'migu', '咪咕-自动', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/migu.png', 10, 0);
INSERT INTO `api_link_channel` VALUES (11, 'txdocs', '腾讯文档', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/txdocs.png', 11, 0);
INSERT INTO `api_link_channel` VALUES (12, 'xiaopeng', '小鹏汽车', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/xiaopeng.png', 12, 0);
INSERT INTO `api_link_channel` VALUES (13, 'ownwxa', '自建小程序', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/wxa.png', 13, 0);
INSERT INTO `api_link_channel` VALUES (14, 'website', '网页直跳', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/website.png', 14, 0);
INSERT INTO `api_link_channel` VALUES (15, 'securewebsite', '网页防红', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/securewebsite.png', 15, 0);
INSERT INTO `api_link_channel` VALUES (16, 'qq', 'QQ好友', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/qq.png', 16, 0);
INSERT INTO `api_link_channel` VALUES (17, 'qqqun', 'QQ群聊', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/qqqun.png', 17, 0);
INSERT INTO `api_link_channel` VALUES (18, 'txwj', '腾讯问卷', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/txwj.png', 18, 0);
INSERT INTO `api_link_channel` VALUES (19, 'sto', '申通-自动', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/link-channel/sto.png', 19, 0);

-- ----------------------------
-- Table structure for api_login_scene
-- ----------------------------
DROP TABLE IF EXISTS `api_login_scene`;
CREATE TABLE `api_login_scene`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `scene_id` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=等待扫码 1=扫码成功 2=绑定注册 3=登录成功',
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `openid` varchar(28) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `qrcode_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `invite_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `scene_id`(`scene_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_login_scene
-- ----------------------------

-- ----------------------------
-- Table structure for api_logs
-- ----------------------------
DROP TABLE IF EXISTS `api_logs`;
CREATE TABLE `api_logs`  (
  `code` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `device` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `os` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `browser` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_logs
-- ----------------------------

-- ----------------------------
-- Table structure for api_material
-- ----------------------------
DROP TABLE IF EXISTS `api_material`;
CREATE TABLE `api_material`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_material
-- ----------------------------

-- ----------------------------
-- Table structure for api_menu
-- ----------------------------
DROP TABLE IF EXISTS `api_menu`;
CREATE TABLE `api_menu`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sort` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `create_time` int(10) UNSIGNED NOT NULL,
  `update_time` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `parent_id`(`parent_id`) USING BTREE,
  INDEX `sort`(`sort`) USING BTREE,
  INDEX `is_show`(`is_show`) USING BTREE,
  INDEX `is_admin`(`is_admin`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 32 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_estonian_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_menu
-- ----------------------------
INSERT INTO `api_menu` VALUES (1, 0, '用户中心', 'person', '/pages/user/index', 1, 1, 0, 1767196800, 1778563830);
INSERT INTO `api_menu` VALUES (2, 0, '外链卡片', 'redo', '/pages/link/index', 2, 1, 0, 1767196800, 1778563874);
INSERT INTO `api_menu` VALUES (3, 0, '聚合私信', 'chatboxes', '', 3, 1, 0, 1778562184, 1778562391);
INSERT INTO `api_menu` VALUES (4, 0, '短链平台', 'link', '', 4, 1, 0, 1778562408, 1778564376);
INSERT INTO `api_menu` VALUES (5, 0, '快码平台', 'scan', '', 5, 1, 0, 1778562705, 1778564384);
INSERT INTO `api_menu` VALUES (6, 0, '素材管理', 'folder-add', '/pages/material/index', 6, 1, 0, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (7, 0, '推广大使', 'staff', '/pages/user/agent', 7, 1, 0, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (8, 0, '联系客服', 'chat', 'qrcode:https://20261688.oss-cn-hangzhou.aliyuncs.com/huoke/kefu.png', 8, 1, 0, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (9, 0, '业务管理', 'star', '', 9, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (10, 0, '系统管理', 'gear', '', 10, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (11, 0, '公众号管理', 'weixin', '', 11, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (12, 3, '账号管理', '', '/pages/douyinim/account', 1, 1, 0, 1778562206, 1778562275);
INSERT INTO `api_menu` VALUES (13, 3, '聚合私信', '', '/pages/douyinim/chat', 2, 1, 0, 1778562297, 1778562297);
INSERT INTO `api_menu` VALUES (14, 4, '抖音短链', '', '/pages/shorturl/index?channel_type=douyin', 1, 1, 0, 1767196800, 1778562523);
INSERT INTO `api_menu` VALUES (15, 4, '扣子短链', '', '/pages/shorturl/index?channel_type=coze', 2, 1, 0, 1767196800, 1778562535);
INSERT INTO `api_menu` VALUES (16, 4, '红薯短链', '', '/pages/shorturl/index?channel_type=xhs', 3, 1, 0, 1767196800, 1778562540);
INSERT INTO `api_menu` VALUES (17, 4, '西瓜短链', '', '/pages/shorturl/index?channel_type=ixigua', 4, 1, 0, 1767196800, 1778562543);
INSERT INTO `api_menu` VALUES (18, 4, '微博短链', '', '/pages/shorturl/index?channel_type=weibo', 5, 1, 0, 1767196800, 1778562549);
INSERT INTO `api_menu` VALUES (19, 4, '活码短链', '', '/pages/shorturl/index?channel_type=short', 6, 1, 0, 1767196800, 1778562552);
INSERT INTO `api_menu` VALUES (20, 5, '抖音圆码', '', '/pages/qrcode/index?channel_type=douyin', 1, 1, 0, 1767196800, 1778564279);
INSERT INTO `api_menu` VALUES (21, 9, '外链管理', '', '/pages/admin/link/index', 1, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (22, 9, '短链管理', '', '/pages/admin/shorturl/index', 2, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (23, 9, '快码管理', '', '/pages/admin/qrcode/index', 3, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (24, 9, '素材管理', '', '/pages/admin/material/index', 4, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (25, 10, '用户管理', '', '/pages/admin/user/index', 1, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (26, 10, '发券管理', '', '/pages/admin/coupon/create', 2, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (27, 10, '安全链接', '', '/pages/admin/site/urls', 3, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (28, 10, '菜单管理', '', '/pages/admin/menu/index', 4, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (29, 10, '系统更新', '', '/pages/admin/site/update', 5, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (30, 11, '自动回复', '', '/pages/admin/site/autoreply', 1, 1, 1, 1767196800, 1767196800);
INSERT INTO `api_menu` VALUES (31, 11, '自定义菜单', '', '/pages/admin/site/selfmenu', 2, 1, 1, 1767196800, 1767196808);

-- ----------------------------
-- Table structure for api_order
-- ----------------------------
DROP TABLE IF EXISTS `api_order`;
CREATE TABLE `api_order`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `coupon_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1会员订单 2积分订单',
  `plan_id` int(10) UNSIGNED NOT NULL,
  `num` int(10) UNSIGNED NOT NULL,
  `price` decimal(10, 2) UNSIGNED NOT NULL,
  `total` decimal(10, 2) UNSIGNED NOT NULL,
  `pay_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0未支付 1已支付 2已关闭',
  `transaction_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `pay_time` int(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_no`(`order_no`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_order
-- ----------------------------

-- ----------------------------
-- Table structure for api_plan
-- ----------------------------
DROP TABLE IF EXISTS `api_plan`;
CREATE TABLE `api_plan`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(10, 0) UNSIGNED NOT NULL,
  `link` int(10) UNSIGNED NOT NULL,
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_plan
-- ----------------------------
INSERT INTO `api_plan` VALUES (1, '个人会员', 88, 10, '适合小规模推广使用');
INSERT INTO `api_plan` VALUES (2, '企业会员', 188, 50, '适合中规模推广使用');
INSERT INTO `api_plan` VALUES (3, '旗舰会员', 288, 500, '适合大规模推广使用');
INSERT INTO `api_plan` VALUES (4, '商业版本', 1888, 88888888, '私有化永久免费更新');

-- ----------------------------
-- Table structure for api_points
-- ----------------------------
DROP TABLE IF EXISTS `api_points`;
CREATE TABLE `api_points`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `points_change` int(10) NOT NULL,
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1充值 2消耗',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `before_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `after_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `create_time` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_points
-- ----------------------------

-- ----------------------------
-- Table structure for api_qrcode
-- ----------------------------
DROP TABLE IF EXISTS `api_qrcode`;
CREATE TABLE `api_qrcode`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `channel_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `remark` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `icon_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `qrcode_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `www_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `code` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_qrcode
-- ----------------------------

-- ----------------------------
-- Table structure for api_qrcode_channel
-- ----------------------------
DROP TABLE IF EXISTS `api_qrcode_channel`;
CREATE TABLE `api_qrcode_channel`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(1) UNSIGNED NOT NULL,
  `points` smallint(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_qrcode_channel
-- ----------------------------
INSERT INTO `api_qrcode_channel` VALUES (1, 'douyin', '抖音圆码', NULL, 1, 0, 10);
INSERT INTO `api_qrcode_channel` VALUES (2, 'ai', 'AI艺术码', NULL, 2, 0, 10);

-- ----------------------------
-- Table structure for api_shorturl
-- ----------------------------
DROP TABLE IF EXISTS `api_shorturl`;
CREATE TABLE `api_shorturl`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `channel_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `remark` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `short_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `www_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `code` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_shorturl
-- ----------------------------

-- ----------------------------
-- Table structure for api_shorturl_channel
-- ----------------------------
DROP TABLE IF EXISTS `api_shorturl_channel`;
CREATE TABLE `api_shorturl_channel`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(1) UNSIGNED NOT NULL,
  `points` smallint(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_shorturl_channel
-- ----------------------------
INSERT INTO `api_shorturl_channel` VALUES (1, 'douyin', '抖音短链', NULL, 1, 0, 5);
INSERT INTO `api_shorturl_channel` VALUES (2, 'ixigua', '西瓜短链', NULL, 2, 0, 5);
INSERT INTO `api_shorturl_channel` VALUES (3, 'coze', '扣子短链', NULL, 3, 0, 5);
INSERT INTO `api_shorturl_channel` VALUES (4, 'short', '活码短链', NULL, 4, 0, 5);
INSERT INTO `api_shorturl_channel` VALUES (5, 'xhs', '红薯短链', NULL, 5, 0, 5);
INSERT INTO `api_shorturl_channel` VALUES (6, 'weibo', '微博短链', NULL, 6, 0, 5);

-- ----------------------------
-- Table structure for api_user
-- ----------------------------
DROP TABLE IF EXISTS `api_user`;
CREATE TABLE `api_user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` tinyint(1) UNSIGNED NOT NULL,
  `end_time` date NOT NULL,
  `username` varchar(28) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `reg_time` int(10) UNSIGNED NOT NULL,
  `reg_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `openid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `invite_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `first_commission` decimal(5, 2) NULL DEFAULT NULL,
  `second_commission` decimal(5, 2) NULL DEFAULT NULL,
  `commission_balance` decimal(10, 2) NOT NULL DEFAULT 0.00,
  `points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `feature_fee` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE,
  UNIQUE INDEX `openid`(`openid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_user
-- ----------------------------
INSERT INTO `api_user` VALUES (1, 1, '2030-01-01', '18888888888', '$2y$12$nTFHZUQDk1Em8xQD7ML0UOykOpM/y..MqKDseSgDJLWnKSsCZpHxa', 'https://20261688.oss-cn-hangzhou.aliyuncs.com/upload/avatar/1_1769784375.png', 1753939952, '127.0.0.1', '', 0, NULL, NULL, 0.00, 0, NULL);

-- ----------------------------
-- Table structure for api_wechat_autoreply
-- ----------------------------
DROP TABLE IF EXISTS `api_wechat_autoreply`;
CREATE TABLE `api_wechat_autoreply`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'subscribe=关注, keyword=关键词, default=默认, event_key=菜单点击',
  `keyword` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `type_keyword_unique`(`type`, `keyword`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_wechat_autoreply
-- ----------------------------
INSERT INTO `api_wechat_autoreply` VALUES (1, 'default', '', '{\"reply_type\":\"text\",\"content\":\"请点击下方“联系客服”，我们将一对一为您解答。\"}');
INSERT INTO `api_wechat_autoreply` VALUES (2, 'subscribe', '', '{\"reply_type\":\"text\",\"content\":\"欢迎关注【获客大师】\\n如您有任何问题，请点击下方“联系客服”。\"}');
INSERT INTO `api_wechat_autoreply` VALUES (3, 'event_key', 'KEFU', '{\"reply_type\":\"image\",\"content\":\"22JtoFDh6lABOvFiUUhFkVb2fQgBVqLRDOxRL9Y6EYZ0Tc3xHDuXkKDPwBsZalvG\"}');
INSERT INTO `api_wechat_autoreply` VALUES (4, 'keyword', '官网', '{\"reply_type\":\"news\",\"content\":{\"title\":\"获客大师官网\",\"description\":\"点击进入\",\"url\":\"https://huoke.xin\",\"image\":\"https://20261688.oss-cn-hangzhou.aliyuncs.com/huoke/avatar.png\"}}');
INSERT INTO `api_wechat_autoreply` VALUES (5, 'event_key', 'KEFUCARD', '{\"reply_type\":\"news\",\"content\":{\"title\":\"在线客服\",\"description\":\"点击这里\",\"url\":\"https://huoke.xin\",\"image\":\"https://20261688.oss-cn-hangzhou.aliyuncs.com/huoke/avatar.png\"}}');

SET FOREIGN_KEY_CHECKS = 1;
