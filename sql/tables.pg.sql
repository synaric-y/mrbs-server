--
-- MRBS table creation script - for PostgreSQL 7.3 and above
--
-- Notes:
-- (1) MySQL inserts the current date/time into any timestamp field which is not
--     specified on insert. To get the same effect, use PostgreSQL default
--     value current_timestamp.
--
-- (2) If you have decided to change the prefix of your tables from 'mrbs_'
--     to something else using $db_tbl_prefix then you must edit each
--     'CREATE TABLE', 'create index', 'INSERT INTO' and 'REFERENCES' line below
--     to replace 'mrbs_' with your new table prefix.  A global replace of
--     'mrbs_' will be sufficient.
--
-- (3) If you add new (standard) fields then you should also change the global variable
--     $standard_fields.    Note that if you are just adding custom fields for
--     a single site then this is not necessary.


CREATE TABLE mrbs_area
(
  -- smallints in mrbs_area are assumed to represent booleans
  id                          serial primary key,
  disabled                    smallint DEFAULT 0 NOT NULL,
  area_name                   varchar(30),
  sort_key                    varchar(30) DEFAULT '' NOT NULL,
  timezone                    varchar(50),
  area_admin_email            text,
  resolution                  int,
  default_duration            int,
  default_duration_all_day    smallint DEFAULT 0 NOT NULL,
  morningstarts               int,
  morningstarts_minutes       int,
  eveningends                 int,
  eveningends_minutes         int,
  private_enabled             smallint,
  private_default             smallint,
  private_mandatory           smallint,
  private_override            varchar(32),
  min_create_ahead_enabled    smallint,
  min_create_ahead_secs       int,
  max_create_ahead_enabled    smallint,
  max_create_ahead_secs       int,
  min_delete_ahead_enabled    smallint,
  min_delete_ahead_secs       int,
  max_delete_ahead_enabled    smallint,
  max_delete_ahead_secs       int,
  max_per_day_enabled         smallint DEFAULT 0 NOT NULL,
  max_per_day                 int DEFAULT 0 NOT NULL,
  max_per_week_enabled        smallint DEFAULT 0 NOT NULL,
  max_per_week                int DEFAULT 0 NOT NULL,
  max_per_month_enabled       smallint DEFAULT 0 NOT NULL,
  max_per_month               int DEFAULT 0 NOT NULL,
  max_per_year_enabled        smallint DEFAULT 0 NOT NULL,
  max_per_year                int DEFAULT 0 NOT NULL,
  max_per_future_enabled      smallint DEFAULT 0 NOT NULL,
  max_per_future              int DEFAULT 0 NOT NULL,
  max_secs_per_day_enabled    smallint DEFAULT 0 NOT NULL,
  max_secs_per_day            int DEFAULT 0 NOT NULL,
  max_secs_per_week_enabled   smallint DEFAULT 0 NOT NULL,
  max_secs_per_week           int DEFAULT 0 NOT NULL,
  max_secs_per_month_enabled  smallint DEFAULT 0 NOT NULL,
  max_secs_per_month          int DEFAULT 0 NOT NULL,
  max_secs_per_year_enabled   smallint DEFAULT 0 NOT NULL,
  max_secs_per_year           int DEFAULT 0 NOT NULL,
  max_secs_per_future_enabled smallint DEFAULT 0 NOT NULL,
  max_secs_per_future         int DEFAULT 0 NOT NULL,
  max_duration_enabled        smallint DEFAULT 0 NOT NULL,
  max_duration_secs           int DEFAULT 0 NOT NULL,
  max_duration_periods        int DEFAULT 0 NOT NULL,
  custom_html                 text,
  approval_enabled            smallint,
  reminders_enabled           smallint,
  enable_periods              smallint,
  periods                     text DEFAULT NULL,
  confirmation_enabled        smallint,
  confirmed_default           smallint,
  times_along_top             smallint DEFAULT 0 NOT NULL,
  default_type                char DEFAULT 'E' NOT NULL,

  CONSTRAINT mrbs_uq_area_name UNIQUE (area_name)
);

CREATE TABLE mrbs_room
(
  id                serial primary key,
  disabled          smallint DEFAULT 0 NOT NULL,
  area_id           int DEFAULT 0 NOT NULL
                      REFERENCES mrbs_area(id)
                      ON UPDATE CASCADE
                      ON DELETE RESTRICT,
  room_name         varchar(25) NOT NULL,
  sort_key          varchar(25) NOT NULL,
  description       varchar(60),
  capacity          int DEFAULT 0 NOT NULL,
  room_admin_email  text,
  invalid_types     varchar(255) DEFAULT NULL,
  custom_html       text,

  CONSTRAINT mrbs_uq_room_name UNIQUE (area_id, room_name)
);
comment on column mrbs_room.invalid_types is 'JSON encoded';
create index mrbs_idxSortKey on mrbs_room(sort_key);

CREATE TABLE mrbs_repeat
(
  id              serial primary key,
  start_time      int DEFAULT 0 NOT NULL,
  end_time        int DEFAULT 0 NOT NULL,
  rep_type        int DEFAULT 0 NOT NULL,
  end_date        int DEFAULT 0 NOT NULL,
  rep_opt         varchar(32) DEFAULT '' NOT NULL,
  room_id         int DEFAULT 1 NOT NULL
                    REFERENCES mrbs_room(id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT,
  timestamp       timestamptz DEFAULT current_timestamp,
  create_by       varchar(80) DEFAULT '' NOT NULL,
  modified_by     varchar(80) DEFAULT '' NOT NULL,
  name            varchar(80) DEFAULT '' NOT NULL,
  type            char DEFAULT 'E' NOT NULL,
  description     text,
  rep_interval    smallint DEFAULT 1 NOT NULL,
  month_absolute  smallint DEFAULT NULL,
  month_relative  varchar(4) DEFAULT NULL,
  status          smallint DEFAULT 0 NOT NULL,
  reminded        int,
  info_time       int,
  info_user       varchar(80),
  info_text       text,
  ical_uid        varchar(255) DEFAULT '' NOT NULL,
  ical_sequence   smallint DEFAULT 0 NOT NULL
);
comment on column mrbs_repeat.start_time is 'Unix timestamp';
comment on column mrbs_repeat.end_time is 'Unix timestamp';
comment on column mrbs_repeat.end_date is 'Unix timestamp';

CREATE TABLE mrbs_entry
(
  id                          serial primary key,
  start_time                  int DEFAULT 0 NOT NULL,
  end_time                    int DEFAULT 0 NOT NULL,
  entry_type                  int DEFAULT 0 NOT NULL,
  repeat_id                   int DEFAULT NULL
                                REFERENCES mrbs_repeat(id)
                                ON UPDATE CASCADE
                                ON DELETE CASCADE,
  room_id                     int DEFAULT 1 NOT NULL
                                REFERENCES mrbs_room(id)
                                ON UPDATE CASCADE
                                ON DELETE RESTRICT,
  timestamp                   timestamptz DEFAULT current_timestamp,
  create_by                   varchar(80) DEFAULT '' NOT NULL,
  modified_by                 varchar(80) DEFAULT '' NOT NULL,
  name                        varchar(80) DEFAULT '' NOT NULL,
  type                        char DEFAULT 'E' NOT NULL,
  description                 text,
  status                      smallint DEFAULT 0 NOT NULL,
  reminded                    int,
  info_time                   int,
  info_user                   varchar(80),
  info_text                   text,
  ical_uid                    varchar(255) DEFAULT '' NOT NULL,
  ical_sequence               smallint DEFAULT 0 NOT NULL,
  ical_recur_id               varchar(16) DEFAULT NULL,
  allow_registration          smallint DEFAULT 0 NOT NULL,
  registrant_limit            int DEFAULT 0 NOT NULL,
  registrant_limit_enabled    smallint DEFAULT 1 NOT NULL,
  registration_opens          int DEFAULT 1209600 NOT NULL, -- 2 weeks
  registration_opens_enabled  smallint DEFAULT 0 NOT NULL,
  registration_closes         int DEFAULT 0 NOT NULL,
  registration_closes_enabled smallint DEFAULT 0 NOT NULL
);
comment on column mrbs_entry.start_time is 'Unix timestamp';
comment on column mrbs_entry.end_time is 'Unix timestamp';
comment on column mrbs_entry.registration_opens is 'Seconds before the start time';
comment on column mrbs_entry.registration_closes is 'Seconds before the start time';
create index mrbs_idxStartTime on mrbs_entry(start_time);
create index mrbs_idxEndTime on mrbs_entry(end_time);
create index mrbs_idxRoomStartEnd on mrbs_entry(room_id, start_time, end_time);

CREATE TABLE mrbs_participants
(
  id          serial primary key,
  entry_id    int NOT NULL
                REFERENCES mrbs_entry(id)
                ON UPDATE CASCADE
                ON DELETE CASCADE,
  username    varchar(191),
  create_by   varchar(255),
  registered  int,

  CONSTRAINT mrbs_uq_entryid_username UNIQUE (entry_id, username)
);

CREATE TABLE mrbs_variables
(
  id               serial primary key,
  variable_name    varchar(80),
  variable_content text,

  CONSTRAINT mrbs_uq_variable_name UNIQUE (variable_name)
);

CREATE TABLE mrbs_zoneinfo
(
  id                 serial primary key,
  timezone           varchar(127) DEFAULT '' NOT NULL,
  outlook_compatible smallint NOT NULL DEFAULT 0,
  vtimezone          text,
  last_updated       int NOT NULL DEFAULT 0,

  CONSTRAINT mrbs_uq_timezone UNIQUE (timezone, outlook_compatible)
);

CREATE TABLE mrbs_sessions
(
  id      varchar(191) NOT NULL primary key,
  access  int DEFAULT NULL,
  data    text DEFAULT NULL
);
comment on column mrbs_sessions.access is 'Unix timestamp';
create index mrbs_idxAccess on mrbs_sessions(access);

CREATE TABLE mrbs_users
(
  id            serial primary key,
  level         smallint DEFAULT 0 NOT NULL,  /* play safe and give no rights */
  name          varchar(30),
  display_name  varchar(191),
  password_hash varchar(255),
  email         varchar(75),
  timestamp     timestamptz DEFAULT current_timestamp,
  last_login    int DEFAULT 0 NOT NULL,
  reset_key_hash varchar(255),
  reset_key_expiry int DEFAULT 0 NOT NULL,

  CONSTRAINT mrbs_uq_name UNIQUE (name)
);

CREATE OR REPLACE FUNCTION update_timestamp_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.timestamp = NOW();
  RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_mrbs_entry_timestamp BEFORE UPDATE ON mrbs_entry FOR EACH ROW EXECUTE PROCEDURE update_timestamp_column();
CREATE TRIGGER update_mrbs_repeat_timestamp BEFORE UPDATE ON mrbs_repeat FOR EACH ROW EXECUTE PROCEDURE update_timestamp_column();
CREATE TRIGGER update_mrbs_users_timestamp BEFORE UPDATE ON mrbs_users FOR EACH ROW EXECUTE PROCEDURE update_timestamp_column();

INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ('db_version', '82');
INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ('local_db_version', '1');

ALTER TABLE mrbs_room ADD COLUMN  `icon` varchar(255)  NULL DEFAULT ''   COMMENT 'room icon';
ALTER TABLE mrbs_room ADD COLUMN  `exchange_username` varchar(80)  NULL DEFAULT ''   COMMENT 'exchange username';
ALTER TABLE mrbs_room ADD COLUMN  `exchange_password` varchar(255)  NULL DEFAULT ''   COMMENT 'exchange password';
ALTER TABLE mrbs_room ADD COLUMN  `exchange_sync_state` varchar(255)  NULL DEFAULT ''   COMMENT '';
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


