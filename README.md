# 商霖会议室预约系统MRBS

一款基于PHP开发的会议室预约系统，具有网页端管理后台与会议室平板展示端。

![](https://scrutinizer-ci.com/g/synaric-y/mrbs-server/badges/build.png?b=main)
![](https://scrutinizer-ci.com/g/synaric-y/mrbs-server/badges/code-intelligence.svg?b=main)

![demo.png](doc/img/2.png?t=1723515608897)
![demo2.png](doc/img/3.png?t=1723515608897)

展示端项目详见[mrbs-app](https://github.com/synaric-y/mrbs-app)。

# 特性

- 区域/会议/房间管理
- 平板大屏展示会议室状态与预约信息
- 与Microsoft Exchange同步Calendar，可扩展的第三方Calendar支持
- 多语言支持，中文/英文/韩文...
- 生成报表HTML/CSV/iCalendar

# 安装

## 环境与依赖

**环境**

- PHP 7.2以上，并支持MySQL或PostgreSQL
- MySQL 5.5.3以上或PostgreSQL 8.2以上
- Apache或其他Web服务器

**依赖**

以下依赖具有安装先后关系，请按顺序安装。

- php-iconv
- php-soap
- [php-ews](https://github.com/Garethp/php-ews)(composer安装)

## 安装步骤

1.安装composer依赖：

```
composer install
```

2.导入初始化SQL

MySQL或PostgreSQL创建数据库名为```mrbs```，并导入数据。

导入SQL文件为```./sql/tables.*.sql```。

3.配置项目

需要在项目```/web```目录下创建配置文件```config.inc.php```，配置文件的模板可以从```./web/config.inc.php-sample```复制。

必要的配置项：

```php
// 设置默认时区，支持的时区参考：
// https://www.php.net/manual/zh/timezones.php
$timezone = "Asia/Shanghai";

// 设置所用数据库mysql/pgsql
$dbsys = "mysql";

// 设置数据库host/database/password/表前缀
$db_host = "your_host";
$db_database = "mrbs";
$db_login = "your_user";
$db_password = 'your_password';
$db_tbl_prefix = "mrbs_";
```

更多信息请参考[安装](doc/INSTALL)。

更多的配置项，请参考```systemdefaults.inc.php```。

> 注意：在熟悉项目前，请勿修改systemdefaults.inc.php，其可以作为默认配置的备份防止无法回退。
> 如需修改配置，config.inc.php内的同名配置项的读取优先级是最高的，因此将需要修改的配置从
> systemdefaults.inc.php复制到config.inc.php再修改即可。

## 部署

将项目整体```web```、```image```目录打包到Web服务器。

（可选）启动第三方Calendar定时同步任务：

```
nohup php /path-to-your-project/web/serverapi/sync_tpcs.php > /dev/null 2>&1 &
```

# 其他

## 支持单位

![BCCGloballogo.jpg](doc/img/1.jpg?t=1723515608897)

## TODO

- 企业微信会议室API支持
- 平板临时预约会议
- 更多平板端模板
- Area级多数据源管理
- Room级可预约时间的管理
- 更多第三方Calendar服务的支持
- 平板端多语言混合展示
- APP/小程序端
