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
ALTER TABLE mrbs_room ADD COLUMN  `wxwork_mr_id` varchar(32)  NULL DEFAULT ''   COMMENT '';

ALTER TABLE mrbs_area ADD COLUMN  `use_exchange` tinyint(4)  NULL DEFAULT 0   COMMENT '';
ALTER TABLE mrbs_area ADD COLUMN  `use_wxwork` tinyint(4)  NULL DEFAULT 0   COMMENT '';
ALTER TABLE mrbs_area ADD COLUMN  `exchange_server` varchar(255)  NULL DEFAULT ''   COMMENT 'exchange server';

ALTER TABLE mrbs_entry ADD COLUMN  `book_by` varchar(80)  NULL DEFAULT ''   COMMENT 'booker';
ALTER TABLE mrbs_entry ADD COLUMN  `exchange_id` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_entry ADD COLUMN  `exchange_key` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_entry ADD COLUMN  `wxwork_bid` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_entry ADD COLUMN  `wxwork_sid` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_entry ADD COLUMN  `create_source` varchar(20)  NULL DEFAULT 'system'   COMMENT 'system/exchange/wxwork';


ALTER TABLE mrbs_repeat ADD COLUMN  `book_by` varchar(80)  NULL DEFAULT ''   COMMENT 'booker';
ALTER TABLE mrbs_repeat ADD COLUMN  `exchange_id` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_repeat ADD COLUMN  `exchange_key` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_repeat ADD COLUMN  `wxwork_bid` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_repeat ADD COLUMN  `wxwork_sid` varchar(511)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_repeat ADD COLUMN  `create_source` varchar(20)  NULL DEFAULT 'system'   COMMENT 'system/exchange/wxwork';


ALTER TABLE mrbs_area ADD COLUMN  `wxwork_corpid` varchar(255)  NULL DEFAULT ''   COMMENT 'wxwork corpid';
ALTER TABLE mrbs_area ADD COLUMN  `wxwork_secret` varchar(255)  NULL DEFAULT ''   COMMENT 'wxwork secret';
ALTER TABLE mrbs_room ADD COLUMN  `wxwork_sync_state` varchar(255)  NULL DEFAULT ''   COMMENT '';
ALTER TABLE mrbs_room ADD COLUMN  `battery_level` int NULL COMMENT '';

CREATE TABLE mrbs_system_variable(
  id                     int NOT NULL auto_increment,
  use_wxwork             tinyint NOT NULL DEFAULT 0 COMMENT 'whether use wxwork',
  use_exchange           tinyint NOT NULL DEFAULT 0 COMMENT 'whether use exchange',
  exchange_server        varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_exchange=1, this is exchange server ip or domain',
  corpid                 varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, get from wxwork',
  secret                 varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, get from wxwork',
  agentid                varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, get from wxwork',
  default_password_hash  varchar(255) NULL DEFAULT '$2y$10$KBS1/8LXU4eNKgKyosKXWeX1TVOMUhpXwrFgSE9PoOPxs6Dh23i0G' COMMENT 'when create by third-party, this will be the default password',
  call_back_domain       varchar(255) NULL DEFAULT '' COMMENT 'only be used when use_wxwork=1, set in wxwork',
  mysql_host             varchar(255) NULL DEFAULT 'localhost' COMMENT 'mysql host',
  mysql_port             int NULL DEFAULT 3306 COMMENT 'mysql port',
  redis_host             varchar(255) NULL DEFAULT 'localhost' COMMENT 'redis host',
  redis_port             int NULL DEFAULT 6379 COMMENT 'redis port',

  PRIMARY KEY (id)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE mrbs_system_variable ADD COLUMN `mysql_user` varchar(255) NULL DEFAULT 'mrbs';
ALTER TABLE mrbs_system_variable ADD COLUMN `mysql_password` varchar(255) NULL DEFAULT '$2y$10$KBS1/8LXU4eNKgKyosKXWeX1TVOMUhpXwrFgSE9PoOPxs6Dh23i0G';
ALTER TABLE mrbs_system_variable ADD COLUMN `redis_password` varchar(255) NULL DEFAULT '';

INSERT INTO mrbs_users (level, name, display_name, email) values (1, 'exchange', 'exchange', '');

INSERT INTO mrbs_users (level, name, display_name, email) values (2, 'admin', 'admin', '');

