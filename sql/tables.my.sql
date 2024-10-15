#
# MySQL MRBS table creation script
#
# Notes:
# (1) If you have decided to change the prefix of your tables from 'mrbs_'
#     to something else using $db_tbl_prefix then you must edit each
#     'CREATE TABLE', 'INSERT INTO' and 'REFERENCES' line below to replace
#     'mrbs_' with your new table prefix.  A global replace of 'mrbs_' is
#     sufficient.
#
# (2) If you add new fields then you should also change the global variable
#     $standard_fields.   Note that if you are just adding custom fields for
#     a single site then this is not necessary.
DROP TABLE IF EXISTS mrbs_participants;
DROP TABLE IF EXISTS mrbs_entry;
DROP TABLE IF EXISTS mrbs_repeat;
DROP TABLE IF EXISTS mrbs_room;
DROP TABLE IF EXISTS mrbs_area;
DROP TABLE IF EXISTS mrbs_variables;
DROP TABLE IF EXISTS mrbs_zoneinfo;
DROP TABLE IF EXISTS mrbs_sessions;
DROP TABLE IF EXISTS mrbs_users;



CREATE TABLE mrbs_area
(
  # tinyints and smallints in mrbs_area are assumed to represent booleans
  id                          int NOT NULL auto_increment,
  disabled                    tinyint DEFAULT 0 NOT NULL COMMENT 'disabled flag, "0" means area is not disabled, "1" means area is disabled',
  area_name                   varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'this should be unique',
  sort_key                    varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  timezone                    varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'time zone of this area',
  area_admin_email            text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  resolution                  int COMMENT 'minimum time interval',
  default_duration            int COMMENT 'default entry duration',
  default_duration_all_day    tinyint DEFAULT 0 NOT NULL COMMENT '"0" means by default, entry will last default_duration, and "1" means by default, entry will last all day.',
  morningstarts               int COMMENT 'the earliest booking hour of the day',
  morningstarts_minutes       int COMMENT 'the earliest booking minutes of the day, should be used with morningstarts',
  eveningends                 int COMMENT 'the latest booking hour of the day',
  eveningends_minutes         int COMMENT 'the latest booking minutes of the day, should be used with eveningends',
  private_enabled             tinyint,
  private_default             tinyint,
  private_mandatory           tinyint,
  private_override            varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  min_create_ahead_enabled    tinyint,
  min_create_ahead_secs       int,
  max_create_ahead_enabled    tinyint,
  max_create_ahead_secs       int,
  min_delete_ahead_enabled    tinyint,
  min_delete_ahead_secs       int,
  max_delete_ahead_enabled    tinyint,
  max_delete_ahead_secs       int,
  max_per_day_enabled         tinyint DEFAULT 0 NOT NULL,
  max_per_day                 int DEFAULT 0 NOT NULL,
  max_per_week_enabled        tinyint DEFAULT 0 NOT NULL,
  max_per_week                int DEFAULT 0 NOT NULL,
  max_per_month_enabled       tinyint DEFAULT 0 NOT NULL,
  max_per_month               int DEFAULT 0 NOT NULL,
  max_per_year_enabled        tinyint DEFAULT 0 NOT NULL,
  max_per_year                int DEFAULT 0 NOT NULL,
  max_per_future_enabled      tinyint DEFAULT 0 NOT NULL,
  max_per_future              int DEFAULT 0 NOT NULL,
  max_secs_per_day_enabled    tinyint DEFAULT 0 NOT NULL,
  max_secs_per_day            int DEFAULT 0 NOT NULL,
  max_secs_per_week_enabled   tinyint DEFAULT 0 NOT NULL,
  max_secs_per_week           int DEFAULT 0 NOT NULL,
  max_secs_per_month_enabled  tinyint DEFAULT 0 NOT NULL,
  max_secs_per_month          int DEFAULT 0 NOT NULL,
  max_secs_per_year_enabled   tinyint DEFAULT 0 NOT NULL,
  max_secs_per_year           int DEFAULT 0 NOT NULL,
  max_secs_per_future_enabled tinyint DEFAULT 0 NOT NULL,
  max_secs_per_future         int DEFAULT 0 NOT NULL,
  max_duration_enabled        tinyint DEFAULT 0 NOT NULL,
  max_duration_secs           int DEFAULT 0 NOT NULL,
  max_duration_periods        int DEFAULT 0 NOT NULL,
  approval_enabled            tinyint,
  reminders_enabled           tinyint,
  enable_periods              tinyint,
  periods                     text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  confirmation_enabled        tinyint,
  confirmed_default           tinyint,
  times_along_top             tinyint NOT NULL DEFAULT 0,
  default_type                char DEFAULT 'E' NOT NULL COMMENT '"E" means by default, entry is external, "I" means by default, entry is internal',
  parent_id                   int DEFAULT -1 COMMENT 'superior area id',
  PRIMARY KEY (id),
  UNIQUE KEY uq_area_name (area_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='table of area';



CREATE TABLE mrbs_room
(
  id                  int NOT NULL auto_increment,
  disabled            tinyint DEFAULT 0 NOT NULL,
  area_id             int DEFAULT 0 NOT NULL,
  room_name           varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  sort_key            varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  description         varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  capacity            int DEFAULT 0 NOT NULL,
  room_admin_email    text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  invalid_types       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'JSON encoded',
  custom_html         text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  exchange_username   varchar(80) NULL DEFAULT '' COMMENT 'exchange username',
  exchange_password   varchar(255) NULL DEFAULT '' COMMENT 'exchange password',
  exchange_sync_state varchar(1024) NULL DEFAULT '' COMMENT '',
  icon                varchar(255) NULL DEFAULT '' COMMENT 'room icon',
  wxwork_sync_state   text NULL DEFAULT '' COMMENT '',
  show_book           tinyint NULL DEFAULT 1 COMMENT '"0" means do not show booker in tablet, "1" means show booker in tablet',
  show_meeting_name   tinyint NULL DEFAULT 1 COMMENT '"0" means do not show meeting name in tablet, "1" means show meeting name int tablet',
  temporary_meeting   tinyint NULL DEFAULT 1 COMMENT '"0" means this room cannot book fast meetings, "1" means this room can book fast meeeting',

  PRIMARY KEY (id),
  FOREIGN KEY (area_id)
    REFERENCES mrbs_area(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  UNIQUE KEY uq_room_name (area_id, room_name),
  KEY idxSortKey (sort_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='table of room';




CREATE TABLE mrbs_repeat
(
  id             int NOT NULL auto_increment,
  start_time     int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  end_time       int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  rep_type       int DEFAULT 0 NOT NULL COMMENT '"1" means dayily, "2" means weekly, "3" means monthly, "4" means yearly',
  end_date       int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  rep_opt        varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL COMMENT 'example: 0001001 means Wednesday and Saturday',
  room_id        int DEFAULT 1 NOT NULL COMMENT 'entry will be in this room',
  timestamp      timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  create_by      varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL COMMENT 'who created the entry',
  modified_by    varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  name           varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL COMMENT 'entry name',
  type           char DEFAULT 'E' NOT NULL COMMENT '"E" means the entry is external, "I" means the entry is internal',
  description    text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  rep_interval   smallint DEFAULT 1 NOT NULL COMMENT 'repeat interval',
  month_absolute smallint DEFAULT NULL COMMENT 'when to repeat monthly entry(absolute month)',
  month_relative varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'when to repeat monthly entry(relative month)',
  status         tinyint unsigned NOT NULL DEFAULT 0,
  reminded       int,
  info_time      int,
  info_user      varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  info_text      text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  ical_uid       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  ical_sequence  smallint DEFAULT 0 NOT NULL,
  book_by        varchar(80) NULL DEFAULT '' COMMENT 'booker',
  exchange_id    varchar(511) NULL DEFAULT '' COMMENT '',
  exchange_key   varchar(511) NULL DEFAULT '' COMMENT '',
  create_source  varchar(20) NULL DEFAULT 'system' COMMENT 'system/exchange/wxwork',

  PRIMARY KEY (id),
  FOREIGN KEY (room_id)
    REFERENCES mrbs_room(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='table of repeat entry';



CREATE TABLE mrbs_entry
(
  id                          int NOT NULL auto_increment,
  start_time                  int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  end_time                    int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  entry_type                  int DEFAULT 0 NOT NULL COMMENT '"0" means single entry, "1" means one entry of a repeat entry, "2" means a modified entry, "99" means fast meeting',
  repeat_id                   int DEFAULT NULL COMMENT 'if the entry belongs to a repeat entry, this is the id of the repeat entry',
  room_id                     int DEFAULT 1 NOT NULL COMMENT 'the entry will be in the room',
  timestamp                   timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  create_by                   varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL COMMENT 'who created the entry',
  modified_by                 varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  name                        varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL COMMENT 'entry name',
  type                        char DEFAULT 'E' NOT NULL COMMENT '"E" means the entry is external, "I" means the entry is internal',
  description                 text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  status                      tinyint unsigned NOT NULL DEFAULT 0,
  reminded                    int,
  info_time                   int,
  info_user                   varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  info_text                   text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  ical_uid                    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  ical_sequence               smallint DEFAULT 0 NOT NULL,
  ical_recur_id               varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  allow_registration          tinyint DEFAULT 0 NOT NULL,
  registrant_limit            int DEFAULT 0 NOT NULL,
  registrant_limit_enabled    tinyint DEFAULT 1 NOT NULL,
  registration_opens          int DEFAULT 1209600 NOT NULL COMMENT 'Seconds before the start time', -- 2 weeks
  registration_opens_enabled  tinyint DEFAULT 0 NOT NULL,
  registration_closes         int DEFAULT 0 NOT NULL COMMENT 'Seconds before the start_time',
  registration_closes_enabled tinyint DEFAULT 0 NOT NULL,
  book_by                     varchar(80) NULL DEFAULT '' COMMENT 'booker',
  exchange_id                 varchar(511) NULL DEFAULT '' COMMENT '',
  exchange_key                varchar(511) NULL DEFAULT '' COMMENT '',
  create_source               varchar(20) NULL DEFAULT 'system' COMMENT 'system/exchange/wxwork',

  PRIMARY KEY (id),
  FOREIGN KEY (room_id)
    REFERENCES mrbs_room(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  FOREIGN KEY (repeat_id)
    REFERENCES mrbs_repeat(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  KEY idxStartTime (start_time),
  KEY idxEndTime   (end_time),
  KEY idxRoomStartEnd (room_id, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='table of entry';



CREATE TABLE mrbs_participants
(
  id          int NOT NULL auto_increment,
  entry_id    int NOT NULL,
  username    varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  create_by   varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  registered  int,

  PRIMARY KEY (id),
  UNIQUE KEY uq_entryid_username (entry_id, username),
  FOREIGN KEY (entry_id)
    REFERENCES mrbs_entry(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE mrbs_variables
(
  id               int NOT NULL auto_increment,
  variable_name    varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  variable_content text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,

  PRIMARY KEY (id),
  UNIQUE KEY uq_variable_name (variable_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE mrbs_zoneinfo
(
  id                 int NOT NULL auto_increment,
  timezone           varchar(127) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  outlook_compatible tinyint unsigned NOT NULL DEFAULT 0,
  vtimezone          text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  last_updated       int NOT NULL DEFAULT 0,

  /* Note that there is a limit on the length of keys which imposes a constraint
     on the size of VARCHAR that can be keyed */
  PRIMARY KEY (id),
  UNIQUE KEY uq_timezone (timezone, outlook_compatible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE mrbs_sessions
(
  id      varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'session id',
  access  int unsigned DEFAULT NULL COMMENT 'Unix timestamp' COMMENT 'the last time to access to the session',
  data    text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'session data',

  /* Note that there is a limit on the length of keys which imposes a constraint
     on the size of VARCHAR that can be keyed */
  PRIMARY KEY (id),
  KEY idxAccess (access)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='table of session';


CREATE TABLE mrbs_users
(
  id                int NOT NULL auto_increment,
  third_id          varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'third group_id',
  third_parent_id   text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'third parent_id',
  sync_state        int(11) NULL DEFAULT NULL COMMENT '0:no sync;1:sync',
  sync_version      varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'sync version code',
  last_sync_time    int(13) NULL DEFAULT NULL COMMENT 'last sync timestamp',
  level             smallint DEFAULT 0 NOT NULL COMMENT '"1" means ordinary user, "2" means administrator',  /* play safe and give no rights */
  name              varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'username, should be unique',
  display_name      varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'the name used to be displayed',
  password_hash     varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  email             varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  timestamp         timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login        int DEFAULT '0' NOT NULL,
  reset_key_hash    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  reset_key_expiry  int DEFAULT 0 NOT NULL,
  disabled          tinyint NOT NULL DEFAULT '0',
  source            varchar(50) NOT NULL DEFAULT NULL COMMENT 'system/ad/wxwork',

  PRIMARY KEY (id),
  UNIQUE KEY uq_name (name)
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic COMMENT='table of system user';

INSERT INTO mrbs_users (level, name, display_name, email) values (1, 'exchange', 'exchange', '');

INSERT INTO mrbs_users (level, name, display_name, email) values (2, 'admin', 'admin', '');

INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ( 'db_version', '82');
INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ( 'local_db_version', '1');







### 2.0开始

DROP TABLE IF EXISTS mrbs_system_variable;
DROP TABLE IF EXISTS mrbs_device;
DROP TABLE IF EXISTS mrbs_area_group;
DROP TABLE IF EXISTS mrbs_room_group;
DROP TABLE IF EXISTS mrbs_user_group;
DROP TABLE IF EXISTS mrbs_g2g_map;
DROP TABLE IF EXISTS mrbs_u2g_map;


CREATE TABLE mrbs_system_variable(
  id                     int NOT NULL auto_increment,
  use_wxwork             tinyint NOT NULL DEFAULT 0 COMMENT 'whether use wxwork',
  use_exchange           tinyint NOT NULL DEFAULT 0 COMMENT 'whether use exchange',
  exchange_server        varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_exchange=1, this is exchange server ip or domain',
  corpid                 varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, get from wxwork',
  secret                 varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, get from wxwork',
  agentid                varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, get from wxwork',
  default_password_hash  varchar(255) NULL DEFAULT '$2y$10$SeZPxKE78o0MFMIdNxpD/uMS.fBudoEGfUBkujLAmImaLva1T4Zm6' COMMENT 'when create by third-party, this will be the default password',
  call_back_domain       varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, set in wxwork',
  redis_host             varchar(255) NULL DEFAULT 'localhost' COMMENT 'redis host',
  redis_port             int NULL DEFAULT 6379 COMMENT 'redis port',
  redis_password         varchar(255) NULL DEFAULT '' COMMENT 'redis password',
  AD_server              varchar(255) NULL DEFAULT '' COMMENT 'AD server address',
  AD_port                int NULL COMMENT 'AD port',
  AD_base_dn             varchar(255) NULL DEFAULT '' COMMENT '',
  AD_username            varchar(255) NULL DEFAULT '' COMMENT 'AD service username',
  AD_password            varchar(255) NULL DEFAULT '' COMMENT 'AD service password',
  AD_timely_sync         tinyint NULL DEFAULT 0 COMMENT '"1" means timely synchronize the AD server, "0" means do not synchronize the AD server timely',
  AD_interval_date       int NULL COMMENT 'when synchronized the AD server',
  exchange_sync_type     varchar(30) NULL DEFAULT '' COMMENT 'one-way/two-way',
  exchange_sync_interval int NULL COMMENT 'synchronizing interval',
  logo_dir               varchar(255) NULL DEFAULT '' COMMENT 'where is the logo of web service',
  app_logo_dir           varchar(255) NULL DEFAULT '' COMMENT 'where is the logo of app',
  time_type              int NULL DEFAULT 24 COMMENT '24 hour clock/12 hour clock',
  now_version            varchar(100) NULL DEFAULT '' COMMENT 'the version of the tablet service',
  show_book              tinyint NULL DEFAULT 1 COMMENT 'by default, "1" means tablet will show the booker, "0" means tablet won''t show the booker',
  show_meeting_name      tinyint NULL DEFAULT 1 COMMENT 'by default, "1" means tablet will show the name of the meeting, "0" means tablet won''t show the name of meeting',
  temporary_meeting      tinyint NULL DEFAULT 1 COMMENT 'by default, "1" means meeting can be booked from tablet, "0" means meeting cannot be booked from tablet',
  resolution             int NULL DEFAULT 1800 COMMENT 'default minimum time interval',
  company_name           varchar(255) NULL DEFAULT '' COMMENT 'company name',
  init_status            int NOT NULL DEFAULT 0 COMMENT '0/1/2/3',
  server_address         varchar(255) NULL DEFAULT '' COMMENT 'backend server address',
  theme_type             int NOT NULL DEFAULT 1 COMMENT 'theme type',

  PRIMARY KEY (id)
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic COMMENT='system variables';
INSERT INTO mrbs_system_variable(init_status) VALUES (0);

CREATE TABLE mrbs_device(
  id            int NOT NULL auto_increment,
  device_id     varchar(255) NOT NULL DEFAULT '' COMMENT 'device id',
  version       varchar(100) NULL DEFAULT '' COMMENT 'device app version',
  description   varchar(255) NULL DEFAULT '' COMMENT '',
  resolution    varchar(100) NULL DEFAULT '' COMMENT 'tablet resolution',
  battery_level int NULL COMMENT 'remaining power',
  status        int NULL DEFAULT 1 COMMENT '"1" means online, "0" means offline',
  is_set        tinyint NULL DEFAULT 0 COMMENT '"1" means tablet bind a room, "0" means tablet do not bind a room',
  set_time      int NULL COMMENT 'when tablet bind the room',
  room_id       int NULL COMMENT 'the room tablet has bind',
  is_charging   tinyint NULL DEFAULT 0 COMMENT '"0" means tablet is not charging, "1" means tablet is charging',
  PRIMARY KEY(id)
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic COMMENT='table of device';




CREATE TABLE mrbs_area_group(
  id int NOT NULL auto_increment,
  area_id int NOT NULL,
  group_id int NOT NULL,
  PRIMARY KEY (id)
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic COMMENT='table of the relation between area and group';


CREATE TABLE mrbs_room_group(
  id int NOT NULL auto_increment,
  room_id int NOT NULL,
  group_id int NOT NULL,
  PRIMARY KEY (id)
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic COMMENT='table of the relation between room and group';

### 用户组表

CREATE TABLE `mrbs_user_group`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(511) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'group name',
  `third_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'third group_id',
  `third_parent_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'third parent_id',
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'system/ad',
  `sync_state` int(11) NULL DEFAULT NULL COMMENT '0:no sync;1:sync',
  `last_sync_time` int(13) NULL DEFAULT NULL COMMENT 'last sync timestamp',
  `sync_version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'sync version code',
  `disabled` tinyint NULL DEFAULT 0,
  `user_count` int(11) NULL DEFAULT 0 COMMENT 'users in this group',
                                  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic;

### 组-组关系表

CREATE TABLE `mrbs_g2g_map`  (
   `id` bigint NOT NULL AUTO_INCREMENT,
   `group_id` int(11) NULL DEFAULT NULL,
   `parent_id` int(11) NULL DEFAULT -1,
   `deep` int(11) NULL DEFAULT 1,
   `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'system/ad',
   PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic COMMENT='table of relation between groups';

### 人-组关系表

CREATE TABLE `mrbs_u2g_map`  (
   `id` bigint NOT NULL AUTO_INCREMENT,
   `user_id` int(11) NULL DEFAULT NULL,
   `parent_id` int(11) NULL DEFAULT -1,
   `deep` int(11) NULL DEFAULT 1,
   `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'system/ad',
   PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic COMMENT='table of relation between user and group';

CREATE TABLE `mrbs_version` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NULL DEFAULT '',
  `publish_time` int(11) NULL COMMENT 'when the version was published',
  `update_time` int(11) DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'when the version was updated',
  `is_delete` tinyint NULL DEFAULT 0 COMMENT 'whether the files of the version has been deleted',
  PRIMARY KEY (id),
  UNIQUE KEY (version)
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic COMMENT='table of all versions';





