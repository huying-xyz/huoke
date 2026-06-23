# 获客大师 - 免费开源引流系统

> 基于 ThinkPHP8 的外链跳转与私域引流平台，面向抖音、快手等社交媒体场景，免费开源。

## 项目简介

**获客大师**是一套完整的免费开源私域流量运营工具，帮助个人开发者和中小企业在抖音、快手、小红书等平台实现跨平台流量引导。核心提供**外链卡片**、**短链**、**活码**三大跳转能力，并集成抖音私信 IM 管理、微信公众号管理、二级分销代理、会员套餐付费等完整商业功能，开箱即用。

## 技术栈

| 层级 | 技术 |
|------|------|
| 后端框架 | ThinkPHP 8 + ThinkORM |
| PHP 版本 | >= 8.3 |
| 前端 | uni-app (Vue 3) + Vite |
| 数据库 | MySQL |
| 文件存储 | 阿里云 OSS |
| 支付 | 微信支付 + 支付宝 |
| 微信 SDK | EasyWeChat 5.0 / wechat-developer |
| 二维码 | chillerlan/php-qrcode |

## 目录结构

```
├── app/                    # 应用核心代码
│   ├── controller/         # 控制器
│   │   ├── admin/          # 后台管理接口
│   │   ├── DouyinIm.php    # 抖音私信IM
│   │   ├── Link.php        # 外链卡片
│   │   ├── ShortUrl.php    # 短链管理
│   │   ├── Qrcode.php      # 活码管理
│   │   ├── User.php        # 用户系统
│   │   ├── Order.php       # 订单支付
│   │   ├── Wechat.php      # 微信公众号
│   │   └── ...
│   └── middleware/          # 中间件（Auth / 跨域）
├── config/                 # 配置文件
├── public/                 # 网站入口 & 前端资源
├── route/                  # 路由定义
├── view/link/              # 卡片落地页模板
├── urls.json               # 跳转 URL 配置
└── .env                    # 环境变量
```

## 核心功能

### 外链卡片（Link）
- 生成 6 位数字码，支持 `c/{code}` 和泛域名 `{code}.domain.com` 两种访问方式
- 支持直接跳转、抖音卡片、快手卡片等多种渠道类型
- 自定义标题、描述、图标、落地页模板
- 自动记录访问日志（设备/系统/浏览器/IP）

### 短链（ShortUrl）
- 自定义短链、抖音短链、小红书短链等多平台短链生成
- 通过外部 OPEN API 调用各平台短链服务
- 302 重定向跳转，记录访问日志

### 活码（Qrcode）
- 创建各渠道类型二维码
- 远程生成并上传至 OSS

### 抖音私信 IM
- 管理抖音 Cookie 账号
- 私信会话列表与消息历史
- 消息撤回、卡片快捷回复

### 用户系统
- 手机号 + 密码注册登录
- 微信小程序扫码登录
- **二级分销代理**：邀请码机制，一二级佣金分成

### 会员套餐（Plan）
- 不同等级套餐限制卡片/短链/活码数量
- 试用期机制、到期自动降级/清理

### 订单与支付
- 微信支付 + 支付宝购买套餐 / 充值积分
- 优惠券系统（固定金额 / 百分比折扣 / 兑换码）

### 微信公众号
- 关注回复、关键词回复、自定义菜单
- 模板消息通知

### 定时任务（Cron）
- 咪咕 Cookie 自动刷新
- 过期数据自动清理（日志、订单、过期会员数据、OSS 文件）

## 安装部署

### 环境要求

- PHP >= 8.3
- MySQL >= 5.7
- Composer
- Nginx / Apache

### 安装步骤

```bash
# 1. 克隆项目
git clone <repository-url>
cd huoke

# 2. 安装依赖
composer install

# 3. 配置环境变量
cp .env.example .env
# 编辑 .env 文件，配置数据库、OSS、微信支付等参数

# 4. 导入数据库
# 将 public/install/mysql.sql 导入 MySQL 数据库

# 5. 配置 Web 服务器
# 将 public/ 目录设为网站根目录
# Nginx 示例：
#   root /path/to/huoke/public;
#   location / {
#       try_files $uri $uri/ /index.html;
#   }

# 6. 设置目录权限
chmod -R 755 runtime/
```

## API 路由

| 层级 | 前缀 | 认证方式 | 说明 |
|------|------|----------|------|
| 公开 | `/api/` | 无需认证 | 登录、注册、支付回调 |
| 用户 | `/api/` | Bearer Token | 业务操作 |
| 管理 | `/api/admin/` | Admin Token | 管理员专属 |

## 环境变量

| 变量 | 说明 |
|------|------|
| `DATABASE_*` | 数据库连接配置 |
| `OSS_*` | 阿里云 OSS 配置 |
| `WECHAT_*` | 微信公众号配置 |
| `WXPAY_*` | 微信支付配置 |
| `ALIPAY_*` | 支付宝配置 |
| `SITE_TRY` | 试用期天数 |
| `ADMIN_DOMAIN` | 管理后台域名 |

## 开源协议

本项目基于 [MIT License](LICENSE) 开源，可自由使用、修改、商用。

## 作者

HuYing ([https://huying.xyz](https://huying.xyz))
