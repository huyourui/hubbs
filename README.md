# HuBBS - 一款基于MIT协议的开源论坛

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-%3E%3D5.7-orange.svg)

## 📖 项目简介

HuBBS 是一款轻量级、现代化的开源论坛系统，采用原生 PHP + MySQL 开发，无需任何框架依赖，部署简单，性能优异。

**作者**: 古雨月田  
**QQ**: 281900864  
**官网**: https://huyourui.com  
**演示**: https://bbs.huyourui.com  
**仓库**: https://gitee.com/youruihu/hubbs

---

## ✨ 功能特性

### 核心功能
- 📝 **帖子发布** - 支持富文本编辑、图片上传、附件上传
- 💬 **评论系统** - 支持多级回复、@提及用户
- 🏷️ **分类管理** - 支持多级分类、权限控制
- 👤 **用户系统** - 注册、登录、个人中心、头像上传
- 🔔 **消息通知** - 系统通知、回复通知、点赞通知
- ⭐ **收藏功能** - 收藏喜欢的帖子
- 👍 **点赞功能** - 为帖子和评论点赞

### 管理功能
- 📊 **后台管理** - 完整的管理后台
- 👥 **用户管理** - 用户列表、角色切换
- 📂 **分类管理** - 分类增删改查、排序
- ⚙️ **系统设置** - 站点名称、副标题、注册开关等
- 🔗 **友情链接** - 友情链接管理
- 📢 **公告管理** - 站点公告发布
- 📊 **帖子管理** - 帖子列表、IP地址显示、地区解析
- 🎯 **等级管理** - 用户等级系统
- 💰 **积分管理** - 积分规则配置
- 🎫 **邀请码管理** - 邀请注册功能

### 特色功能
- 🎨 **响应式设计** - 完美适配PC和移动端
- 🔒 **隐藏内容** - 支持 [hide][/hide] 标签隐藏内容
- 📷 **图片上传** - 支持拖拽上传、自动生成缩略图
- 📎 **附件上传** - 支持多种文件格式上传
- ⌨️ **快捷键** - 支持 Ctrl+Enter 快速发布帖子和评论
- 🌙 **暗色模式** - 支持明暗主题切换
- 📱 **积分系统** - 用户积分、等级系统
- 🔐 **安全防护** - XSS防护、CSRF防护、SQL注入防护
- 🌍 **IP地区解析** - 发帖IP地址解析为地理位置
- 📋 **下拉菜单操作** - 后台管理操作项下拉菜单样式

---

## 🛠️ 技术栈

- **后端**: PHP 7.4+ (原生开发，无框架)
- **数据库**: MySQL 5.7+
- **前端**: Bootstrap 5.3
- **图标**: Bootstrap Icons
- **编辑器**: 自研轻量级富文本编辑器

---

## 📋 环境要求

| 环境 | 要求 |
|------|------|
| PHP | >= 7.4 |
| MySQL | >= 5.7 |
| 扩展 | PDO, GD, MBString, JSON, cURL |

---

## 🚀 安装说明

### 1. 下载程序

```bash
git clone https://gitee.com/youruihu/hubbs.git
# 或直接下载压缩包
```

### 2. 上传文件

将程序文件上传到网站根目录

### 3. 设置目录权限

确保以下目录可写：
```
uploads/
uploads/images/
uploads/images/thumbs/
uploads/attachments/
cache/
```

### 4. 创建数据库

在 MySQL 中创建一个新数据库

### 5. 运行安装程序

访问 `http://your-domain.com/install/` 按照提示完成安装：
1. 环境检测
2. 数据库配置
3. 创建管理员账号
4. 安装完成

### 6. 删除安装目录（可选）

安装完成后，可以删除 `install/` 目录以增强安全性

---

## 📁 目录结构

```
hubbs/
├── index.php               # 入口文件
├── config.php              # 配置文件（安装后生成）
├── functions.php           # 核心函数库
├── core/                   # 核心文件
│   ├── bootstrap.php       # 引导文件
│   └── database.sql        # 数据库结构
├── pages/                  # 页面入口
│   ├── home.php            # 首页
│   ├── login.php           # 登录
│   ├── logout.php          # 退出
│   ├── register.php        # 注册
│   ├── create.php          # 发帖
│   ├── edit.php            # 编辑
│   ├── post.php            # 帖子详情
│   ├── profile.php         # 个人中心
│   ├── notifications.php   # 通知
│   ├── download.php        # 下载
│   └── admin.php           # 后台
├── api/                    # API接口
│   └── upload.php          # 文件上传接口
├── install/                # 安装程序
│   └── index.php           # 安装入口
├── public/                 # 公共资源
│   └── assets/
│       ├── css/            # 样式文件
│       └── js/             # JavaScript文件
├── uploads/                # 上传文件
│   ├── images/             # 图片
│   └── attachments/        # 附件
├── views/                  # 视图模板
│   └── default/
│       ├── layouts/        # 布局模板
│       │   ├── header.php  # 头部
│       │   └── footer.php  # 底部
│       ├── admin.php       # 后台页面
│       ├── create.php      # 发帖页面
│       ├── edit.php        # 编辑页面
│       ├── index.php       # 首页
│       ├── login.php       # 登录页面
│       ├── post.php        # 帖子详情
│       ├── profile.php     # 个人中心
│       └── register.php    # 注册页面
├── cache/                  # 缓存目录
├── install.lock            # 安装锁定文件
├── README.md               # 项目说明
├── LICENSE                 # 开源协议
└── .gitignore              # Git忽略规则
```

---

## ⚙️ 配置说明

### 系统设置

登录管理员账号后，访问后台 `/pages/admin.php` 进行系统设置：

| 设置项 | 说明 |
|--------|------|
| 网站标题 | 显示在浏览器标题栏 |
| 网站副标题 | 显示在网站名称下方 |
| 允许注册 | 是否开放用户注册 |
| 帖子最大字数 | 帖子内容的最大字符数 |
| 评论最大字数 | 评论内容的最大字符数 |
| 启用Markdown | 是否启用Markdown解析 |

### 重装系统

如需重新安装：
1. 删除 `install.lock` 文件
2. 访问 `/install/` 重新安装
3. 原有数据将被覆盖

---

## 🔒 安全建议

1. **删除安装目录** - 安装完成后删除 `install/` 目录
2. **设置目录权限** - 敏感目录设置为不可写
3. **使用HTTPS** - 建议启用SSL证书
4. **定期备份** - 定期备份数据库和上传文件
5. **更新密码** - 定期更换管理员密码

---

## 📝 更新日志

### v1.0.0 (2024-03-28)

**新增功能**
- 初始版本发布
- 完整的论坛核心功能
- 用户注册登录系统
- 帖子发布评论系统
- 后台管理系统
- 图片附件上传
- 消息通知系统
- 收藏点赞功能
- 积分等级系统
- Ctrl+Enter 快捷发布帖子和评论
- IP地址解析显示地区信息
- 后台帖子管理下拉菜单操作
- 邀请码注册功能
- 用户等级系统
- 积分规则管理

**目录优化**
- 重构目录结构，按功能分类
- 新增 `core/` 目录存放核心文件
- 新增 `pages/` 目录存放页面入口
- 优化代码组织结构

**已知问题**
- 暂无

---

## 🤝 参与贡献

欢迎提交 Issue 和 Pull Request！

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 提交 Pull Request

---

## 📄 开源协议

本项目基于 [MIT License](LICENSE) 开源协议发布。

---

## 💬 联系方式

- **作者**: 古雨月田
- **QQ**: 281900864
- **官网**: https://huyourui.com
- **论坛**: https://bbs.huyourui.com
- **Gitee**: https://gitee.com/youruihu/hubbs

---

## 🙏 鸣谢

感谢以下开源项目：
- [Bootstrap](https://getbootstrap.com/) - 前端框架
- [Bootstrap Icons](https://icons.getbootstrap.com/) - 图标库
- [ip-api.com](http://ip-api.com/) - IP地址解析API

---

<p align="center">
  Made with ❤️ by 古雨月田
</p>
