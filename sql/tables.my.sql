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

CREATE TABLE mrbs_area
(
  # tinyints and smallints in mrbs_area are assumed to represent booleans
  id                          int NOT NULL auto_increment,
  disabled                    tinyint DEFAULT 0 NOT NULL,
  area_name                   varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  sort_key                    varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  timezone                    varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  area_admin_email            text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  resolution                  int,
  default_duration            int,
  default_duration_all_day    tinyint DEFAULT 0 NOT NULL,
  morningstarts               int,
  morningstarts_minutes       int,
  eveningends                 int,
  eveningends_minutes         int,
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
  custom_html                 text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  approval_enabled            tinyint,
  reminders_enabled           tinyint,
  enable_periods              tinyint,
  periods                     text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  confirmation_enabled        tinyint,
  confirmed_default           tinyint,
  times_along_top             tinyint NOT NULL DEFAULT 0,
  default_type                char DEFAULT 'E' NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_area_name (area_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mrbs_room
(
  id               int NOT NULL auto_increment,
  disabled         tinyint DEFAULT 0 NOT NULL,
  area_id          int DEFAULT 0 NOT NULL,
  room_name        varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  sort_key         varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  description      varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  capacity         int DEFAULT 0 NOT NULL,
  room_admin_email text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  invalid_types    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'JSON encoded',
  custom_html      text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,

  PRIMARY KEY (id),
  FOREIGN KEY (area_id)
    REFERENCES mrbs_area(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  UNIQUE KEY uq_room_name (area_id, room_name),
  KEY idxSortKey (sort_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mrbs_repeat
(
  id             int NOT NULL auto_increment,
  start_time     int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  end_time       int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  rep_type       int DEFAULT 0 NOT NULL,
  end_date       int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  rep_opt        varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  room_id        int DEFAULT 1 NOT NULL,
  timestamp      timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  create_by      varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  modified_by    varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  name           varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  type           char DEFAULT 'E' NOT NULL,
  description    text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  rep_interval   smallint DEFAULT 1 NOT NULL,
  month_absolute smallint DEFAULT NULL,
  month_relative varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  status         tinyint unsigned NOT NULL DEFAULT 0,
  reminded       int,
  info_time      int,
  info_user      varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  info_text      text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  ical_uid       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  ical_sequence  smallint DEFAULT 0 NOT NULL,

  PRIMARY KEY (id),
  FOREIGN KEY (room_id)
    REFERENCES mrbs_room(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mrbs_entry
(
  id                          int NOT NULL auto_increment,
  start_time                  int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  end_time                    int DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  entry_type                  int DEFAULT 0 NOT NULL,
  repeat_id                   int DEFAULT NULL,
  room_id                     int DEFAULT 1 NOT NULL,
  timestamp                   timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  create_by                   varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  modified_by                 varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  name                        varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  type                        char DEFAULT 'E' NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  id      varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  access  int unsigned DEFAULT NULL COMMENT 'Unix timestamp',
  data    text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,

  /* Note that there is a limit on the length of keys which imposes a constraint
     on the size of VARCHAR that can be keyed */
  PRIMARY KEY (id),
  KEY idxAccess (access)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mrbs_users
(
  id                int NOT NULL auto_increment,
  level             smallint DEFAULT 0 NOT NULL,  /* play safe and give no rights */
  name              varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  display_name      varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  password_hash     varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  email             varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  timestamp         timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login        int DEFAULT '0' NOT NULL,
  reset_key_hash    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  reset_key_expiry  int DEFAULT 0 NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ( 'db_version', '82');
INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ( 'local_db_version', '1');

ALTER TABLE mrbs_room ADD COLUMN  `icon` varchar(255)  NULL DEFAULT ''   COMMENT 'room icon';
ALTER TABLE mrbs_room ADD COLUMN  `exchange_username` varchar(80)  NULL DEFAULT ''   COMMENT 'exchange username';
ALTER TABLE mrbs_room ADD COLUMN  `exchange_password` varchar(255)  NULL DEFAULT ''   COMMENT 'exchange password';
ALTER TABLE mrbs_room ADD COLUMN  `exchange_sync_state` varchar(511)  NULL DEFAULT ''   COMMENT '';

ALTER TABLE mrbs_entry ADD COLUMN  `book_by` varchar(80)  NULL DEFAULT ''   COMMENT 'booker';
ALTER TABLE mrbs_entry ADD COLUMN  `exchange_id` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_entry ADD COLUMN  `exchange_key` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_entry ADD COLUMN  `create_source` varchar(20)  NULL DEFAULT 'system'   COMMENT 'system/exchange/wxwork';


ALTER TABLE mrbs_repeat ADD COLUMN  `book_by` varchar(80)  NULL DEFAULT ''   COMMENT 'booker';
ALTER TABLE mrbs_repeat ADD COLUMN  `exchange_id` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_repeat ADD COLUMN  `exchange_key` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_repeat ADD COLUMN  `wxwork_bid` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_repeat ADD COLUMN  `wxwork_sid` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_repeat ADD COLUMN  `create_source` varchar(20)  NULL DEFAULT 'system'   COMMENT 'system/exchange/wxwork';


ALTER TABLE mrbs_area ADD COLUMN  `wxwork_corpid` varchar(255)  NULL DEFAULT ''   COMMENT 'wxwork corpid';
ALTER TABLE mrbs_area ADD COLUMN  `wxwork_secret` varchar(255)  NULL DEFAULT ''   COMMENT 'wxwork secret';
ALTER TABLE mrbs_room ADD COLUMN  `wxwork_sync_state` text  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_room ADD COLUMN  `battery_level` int NULL COMMENT '';


### 2.0开始

CREATE TABLE mrbs_system_variable(
  id                     int NOT NULL auto_increment,
  use_wxwork             tinyint NOT NULL DEFAULT 0 COMMENT 'whether use wxwork',
  use_exchange           tinyint NOT NULL DEFAULT 0 COMMENT 'whether use exchange',
  corpid                 varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, get from wxwork',
  secret                 varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, get from wxwork',
  agentid                varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, get from wxwork',
  default_password_hash  varchar(255) NULL DEFAULT '$2y$10$SeZPxKE78o0MFMIdNxpD/uMS.fBudoEGfUBkujLAmImaLva1T4Zm6' COMMENT 'when create by third-party, this will be the default password',
  call_back_domain       varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, set in wxwork',
  redis_host             varchar(255) NULL DEFAULT 'localhost' COMMENT 'redis host',
  redis_port             int NULL DEFAULT 6379 COMMENT 'redis port',

  PRIMARY KEY (id)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mrbs_device(
  id            int NOT NULL auto_increment,
  device_id     varchar(255) NOT NULL DEFAULT '' COMMENT 'device id',
  version       varchar(100) NULL DEFAULT '' COMMENT 'device app version',
  description   varchar(255) NULL DEFAULT '' COMMENT '',
  resolution    varchar(100) NULL DEFAULT '' COMMENT '',
  battery_level int NULL COMMENT '',
  status        int NULL DEFAULT 1 COMMENT '',
  is_set        tinyint NULL DEFAULT 0 COMMENT '',
  set_time      int NULL COMMENT '',
  room_id       int NULL COMMENT '',
  PRIMARY KEY(id)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE mrbs_system_variable ADD COLUMN `redis_password` varchar(255) NULL DEFAULT '';

INSERT INTO mrbs_users (level, name, display_name, email) values (1, 'exchange', 'exchange', '');
INSERT INTO mrbs_users (level, name, display_name, email) values (2, 'admin', 'admin', '');

ALTER TABLE mrbs_users ADD COLUMN  `disabled` tinyint  NOT NULL DEFAULT '0';
ALTER TABLE mrbs_users ADD COLUMN `source` varchar(50) NOT NULL DEFAULT NULL COMMENT 'system/ad';
ALTER TABLE mrbs_system_variable ADD COLUMN `AD_server` varchar(255) NULL DEFAULT '' COMMENT 'AD server address';
ALTER TABLE mrbs_system_variable ADD COLUMN `AD_port` int NULL COMMENT 'AD port';
ALTER TABLE mrbs_system_variable ADD COLUMN `AD_base_dn` varchar(255) NULL DEFAULT '' COMMENT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `AD_username` varchar(255) NULL DEFAULT '' COMMENT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `AD_password` varchar(255) NULL DEFAULT '' COMMENT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `AD_timely_sync` tinyint NULL DEFAULT 0 COMMENT 'decide if synchronize timely';
ALTER TABLE mrbs_system_variable ADD COLUMN `AD_interval_type` tinyint NULL COMMENT 'only use when AD_timely_sync is 1';
ALTER TABLE mrbs_system_variable ADD COLUMN `AD_interval_time` int NULL COMMENT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `AD_interval_date` int NULL COMMENT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `Exchange_server` varchar(255) NULL DEFAULT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `Exchange_sync_type` varchar(30) NULL DEFAULT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `Exchange_sync_interval` int NULL COMMENT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `logo_dir` varchar(255) NULL DEFAULT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `app_logo_dir` varchar(255) NULL DEFAULT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `time_type` int NULL DEFAULT 24;
ALTER TABLE mrbs_system_variable ADD COLUMN `now_version` varchar(100) NULL DEFAULT '';
ALTER TABLE mrbs_system_variable ADD COLUMN `show_book` tinyint NULL DEFAULT 1;
ALTER TABLE mrbs_system_variable ADD COLUMN `show_meeting_name` tinyint NULL DEFAULT 1;
ALTER TABLE mrbs_system_variable ADD COLUMN `temporary_meeting` tinyint NULL DEFAULT 1;
ALTER TABLE mrbs_system_variable ADD COLUMN `fast_meeting_type` tinyint NULL DEFAULT 1;
ALTER TABLE mrbs_system_variable ADD COLUMN `resolution` int NULL DEFAULT 1800;
ALTER TABLE mrbs_system_variable ADD COLUMN `company_name` varchar(255) NULL DEFAULT '';
ALTER TABLE mrbs_area ADD COLUMN `parent_id` int NULL DEFAULT 0;
ALTER TABLE mrbs_room ADD COLUMN `show_book` tinyint NULL DEFAULT 1;
ALTER TABLE mrbs_room ADD COLUMN `show_meeting_name` tinyint NULL DEFAULT 1;
ALTER TABLE mrbs_room ADD COLUMN `temporary_meeting` tinyint NULL DEFAULT 1;
ALTER TABLE mrbs_room ADD COLUMN `device_id` int NULL DEFAULT 1;

CREATE TABLE mrbs_area_group(
  id int NOT NULL auto_increment,
  area_id int NOT NULL,
  group_id int NOT NULL,
  PRIMARY KEY (id)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mrbs_room_group(
  id int NOT NULL auto_increment,
  room_id int NOT NULL,
  group_id int NOT NULL,
  PRIMARY KEY (id)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

### user group
CREATE TABLE `mrbs_user_group`  (
  `id` int NOT NULL AUTO_INCREMENT,
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

### group-group relationship
CREATE TABLE `mrbs_g2g_map`  (
   `id` int NOT NULL AUTO_INCREMENT,
   `group_id` int(11) NULL DEFAULT NULL,
   `parent_id` int(11) NULL DEFAULT -1,
   `deep` int(11) NULL DEFAULT 1,
   `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'system/ad',
   PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic;

### user-group relationship
CREATE TABLE `mrbs_u2g_map`  (
   `id` int NOT NULL AUTO_INCREMENT,
   `user_id` int(11) NULL DEFAULT NULL,
   `parent_id` int(11) NULL DEFAULT -1,
   `deep` int(11) NULL DEFAULT 1,
   `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'system/ad',
   PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic;




ALTER TABLE mrbs_users ADD COLUMN `third_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'third group_id';
ALTER TABLE mrbs_users ADD COLUMN `third_parent_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'third parent_id';
ALTER TABLE mrbs_users ADD COLUMN `sync_state` int(11) NULL DEFAULT NULL COMMENT '0:no sync;1:sync';
ALTER TABLE mrbs_users ADD COLUMN `last_sync_time` int(13) NULL DEFAULT NULL COMMENT 'last sync timestamp';

ALTER TABLE `mrbs_system_variable` ADD COLUMN `init_status` int NOT NULL DEFAULT 0;
ALTER TABLE `mrbs_system_variable` ADD COLUMN `server_address` varchar(255) NULL DEFAULT '';
ALTER TABLE `mrbs_system_variable` ADD COLUMN `theme_type` int NOT NULL DEFAULT 1;
