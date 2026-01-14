PRAGMA foreign_keys = ON;
BEGIN TRANSACTION;

/* activity_log */
DROP TABLE IF EXISTS activity_log;
CREATE TABLE activity_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  log_name TEXT,
  description TEXT NOT NULL,
  subject_type TEXT,
  event TEXT,
  subject_id TEXT,
  causer_type TEXT,
  causer_id TEXT,
  properties TEXT,
  batch_uuid TEXT,
  created_at DATETIME,
  updated_at DATETIME
);
CREATE INDEX activity_log_subject_idx ON activity_log(subject_type, subject_id);
CREATE INDEX activity_log_causer_idx  ON activity_log(causer_type, causer_id);
CREATE INDEX activity_log_log_name_index ON activity_log(log_name);

/* api_usage_daily */
DROP TABLE IF EXISTS api_usage_daily;
CREATE TABLE api_usage_daily (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  organization_id TEXT NOT NULL,
  token_id TEXT NOT NULL,
  method TEXT,
  endpoint TEXT NOT NULL,
  date DATE NOT NULL,
  count INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME,
  updated_at DATETIME,
  UNIQUE (organization_id, token_id, method, endpoint, date)
);

/* api_usage_hourly */
DROP TABLE IF EXISTS api_usage_hourly;
CREATE TABLE api_usage_hourly (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  organization_id TEXT NOT NULL,
  token_id TEXT NOT NULL,
  method TEXT,
  endpoint TEXT NOT NULL,
  hour DATETIME NOT NULL,
  count INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME,
  updated_at DATETIME,
  UNIQUE (organization_id, token_id, method, endpoint, hour)
);

/* environments (ref: projects, self-ref base_id) */
DROP TABLE IF EXISTS environments;
CREATE TABLE environments (
  id TEXT PRIMARY KEY,
  base_id TEXT,
  is_restricted INTEGER NOT NULL DEFAULT 0,
  project_id TEXT NOT NULL,
  name TEXT NOT NULL,
  type TEXT NOT NULL,
  file_format TEXT NOT NULL DEFAULT 'alphabetical',
  notifications TEXT,
  kek_salt TEXT,
  created_at DATETIME,
  updated_at DATETIME,
  deleted_at DATETIME,
  FOREIGN KEY (base_id) REFERENCES environments(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
CREATE INDEX environments_project_id_foreign ON environments(project_id);
CREATE INDEX environments_type_index ON environments(type);
CREATE INDEX environments_base_id_foreign ON environments(base_id);

/* inquiries */
DROP TABLE IF EXISTS inquiries;
CREATE TABLE inquiries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  case_id TEXT,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  inquiry TEXT NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME,
  updated_at DATETIME,
  UNIQUE (case_id)
);

/* migrations (Laravel default) */
DROP TABLE IF EXISTS migrations;
CREATE TABLE migrations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  migration TEXT NOT NULL,
  batch INTEGER NOT NULL
);

/* organization_invites */
DROP TABLE IF EXISTS organization_invites;
CREATE TABLE organization_invites (
  id TEXT PRIMARY KEY,
  status TEXT NOT NULL,
  organization_id TEXT,
  user_id TEXT,
  email TEXT NOT NULL,
  role TEXT,
  sent_at DATETIME,
  created_at DATETIME,
  updated_at DATETIME,
  deleted_at DATETIME,
  UNIQUE (email, deleted_at)
);
CREATE INDEX organization_invites_status_index ON organization_invites(status);

/* organization_permission_overrides (ref: users) */
DROP TABLE IF EXISTS organization_permission_overrides;
CREATE TABLE organization_permission_overrides (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL,
  target_type TEXT NOT NULL,
  target_id TEXT NOT NULL,
  permission TEXT NOT NULL,
  created_at DATETIME,
  updated_at DATETIME,
  deleted_at DATETIME,
  UNIQUE (user_id, target_id, target_type, permission, deleted_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX organization_permission_overrides_target_type_target_id_index
  ON organization_permission_overrides(target_type, target_id);
CREATE INDEX organization_permission_overrides_deleted_at_index
  ON organization_permission_overrides(deleted_at);

/* organization_user (ref: organizations, users) */
DROP TABLE IF EXISTS organization_user;
CREATE TABLE organization_user (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  organization_id TEXT NOT NULL,
  user_id TEXT NOT NULL,
  role TEXT,
  created_at DATETIME,
  updated_at DATETIME,
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX organization_user_organization_id_foreign ON organization_user(organization_id);
CREATE INDEX organization_user_user_id_foreign ON organization_user(user_id);

/* organizations (ref: users) */
DROP TABLE IF EXISTS organizations;
CREATE TABLE organizations (
  id TEXT PRIMARY KEY,
  stripe_id TEXT,
  billing_policy TEXT NOT NULL DEFAULT 'respect_subscription',
  plan_override TEXT,
  pm_type TEXT,
  pm_last_four TEXT,
  trial_ends_at DATETIME,
  slug TEXT,
  name TEXT NOT NULL,
  owner_id TEXT,
  notifications TEXT,
  slack_webhook_url TEXT,
  slack_enabled INTEGER NOT NULL DEFAULT 0,
  features TEXT,
  limits TEXT,
  created_at DATETIME,
  updated_at DATETIME,
  deleted_at DATETIME,
  FOREIGN KEY (owner_id) REFERENCES users(id)
);
CREATE INDEX organizations_owner_id_foreign ON organizations(owner_id);
CREATE INDEX organizations_stripe_id_index ON organizations(stripe_id);

/* password_reset_tokens */
DROP TABLE IF EXISTS password_reset_tokens;
CREATE TABLE password_reset_tokens (
  email TEXT PRIMARY KEY,
  token TEXT NOT NULL,
  created_at DATETIME
);

/* personal_access_tokens */
DROP TABLE IF EXISTS personal_access_tokens;
CREATE TABLE personal_access_tokens (
  id TEXT PRIMARY KEY,
  tokenable_type TEXT NOT NULL,
  tokenable_id TEXT NOT NULL,
  name TEXT NOT NULL,
  token TEXT NOT NULL,
  abilities TEXT,
  token_suffix TEXT,
  last_used_at DATETIME,
  expires_at DATETIME,
  created_at DATETIME,
  updated_at DATETIME,
  UNIQUE (token)
);
CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index
  ON personal_access_tokens(tokenable_type, tokenable_id);
CREATE INDEX personal_access_tokens_expires_at_index ON personal_access_tokens(expires_at);

/* posts */
DROP TABLE IF EXISTS posts;
CREATE TABLE posts (
  id TEXT PRIMARY KEY,
  title TEXT NOT NULL,
  slug TEXT NOT NULL,
  category TEXT NOT NULL,
  description TEXT,
  content TEXT,
  hero TEXT,
  social TEXT,
  meta_title TEXT,
  meta_description TEXT,
  meta_keywords TEXT,
  posted_at DATETIME,
  status TEXT NOT NULL,
  is_featured INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME,
  updated_at DATETIME,
  deleted_at DATETIME,
  UNIQUE (slug)
);
CREATE INDEX posts_status_posted_at_index ON posts(status, posted_at);

/* projects (ref: organizations) */
DROP TABLE IF EXISTS projects;
CREATE TABLE projects (
  id TEXT PRIMARY KEY,
  is_restricted INTEGER NOT NULL DEFAULT 0,
  name TEXT NOT NULL,
  description TEXT,
  organization_id TEXT NOT NULL,
  notifications TEXT,
  created_at DATETIME,
  updated_at DATETIME,
  deleted_at DATETIME,
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);
CREATE INDEX projects_organization_id_foreign ON projects(organization_id);

/* subscription_items */
DROP TABLE IF EXISTS subscription_items;
CREATE TABLE subscription_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  subscription_id INTEGER NOT NULL,
  stripe_id TEXT NOT NULL,
  stripe_product TEXT NOT NULL,
  stripe_price TEXT NOT NULL,
  quantity INTEGER,
  created_at DATETIME,
  updated_at DATETIME,
  UNIQUE (stripe_id)
);
CREATE INDEX subscription_items_subscription_id_stripe_price_index
  ON subscription_items(subscription_id, stripe_price);

/* subscriptions (ref: organizations) */
DROP TABLE IF EXISTS subscriptions;
CREATE TABLE subscriptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  organization_id TEXT NOT NULL,
  type TEXT NOT NULL,
  stripe_id TEXT NOT NULL,
  stripe_status TEXT NOT NULL,
  stripe_price TEXT,
  quantity INTEGER,
  trial_ends_at DATETIME,
  ends_at DATETIME,
  created_at DATETIME,
  updated_at DATETIME,
  UNIQUE (stripe_id),
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE INDEX subscriptions_organization_id_stripe_status_index
  ON subscriptions(organization_id, stripe_status);

/* users */
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  email_verified_at DATETIME,
  password TEXT NOT NULL,
  two_factor_secret TEXT,
  two_factor_recovery_codes TEXT,
  two_factor_confirmed_at DATETIME,
  remember_token TEXT,
  timezone TEXT NOT NULL DEFAULT 'UTC',
  created_at DATETIME,
  updated_at DATETIME,
  deleted_at DATETIME,
  UNIQUE (email)
);

/* seed the migrations table (optional) */
INSERT INTO migrations (id, migration, batch) VALUES
  (1,'0001_01_01_000000_create_users_table',1),
  (2,'2025_05_14_133934_create_teams_table',1),
  (3,'2025_05_14_144906_create_team_users_table',1),
  (4,'2025_05_14_162637_create_projects_table',1),
  (5,'2025_05_14_164406_create_environments_table',1),
  (7,'2025_05_14_174856_create_personal_access_tokens_table',1),
  (8,'2025_05_21_185944_create_team_invites_table',1),
  (9,'2025_05_22_000000_add_type_to_environments_table',1),
  (10,'2025_05_27_162149_add_two_factor_columns_to_users_table',1),
  (11,'2025_05_31_131339_add_is_restricted_col_to_projects_table',1),
  (12,'2025_06_01_000000_add_limits_to_teams_table',1),
  (13,'2025_06_01_000001_add_features_to_teams_table',1),
  (14,'2025_06_02_130933_create_team_permission_overrides_table',1),
  (15,'2025_06_03_145536_add_is_restricted_column_to_environments_table',1),
  (16,'2025_06_06_131617_create_activity_log_table',1),
  (17,'2025_06_06_131618_add_event_column_to_activity_log_table',1),
  (18,'2025_06_06_131619_add_batch_uuid_column_to_activity_log_table',1),
  (19,'2025_06_06_172812_update_environment_variable_tables',1),
  (20,'2025_06_08_000000_create_posts_table',1),
  (21,'2025_06_13_132902_create_subscriptions_table',1),
  (22,'2025_06_13_132903_create_subscription_items_table',1),
  (23,'2025_06_17_131804_create_environment_variable_rules_table',1),
  (24,'2025_06_18_000000_add_file_format_column_to_environments_table',1),
  (27,'2025_08_01_000000_add_notifications_columns',1),
  (28,'2025_08_02_000000_add_team_slack_settings',1),
  (29,'2025_08_04_152911_add_forking_migrations',1),
  (30,'2025_08_05_000001_add_inheritance_columns_to_environment_variable_rules_table',1),
  (32,'2025_08_09_000000_add_kek_salt_to_environments_table',1),
  (33,'2025_08_21_014108_add_timezone_column_to_users_table',1),
  (35,'2025_08_22_000001_add_is_featured_to_posts_table',1),
  (36,'2025_08_27_175910_update_usage_tables',1),
  (37,'2025_08_27_180000_create_inquiries_table',1),
  (38,'2025_09_01_000000_add_billing_policy_and_plan_override_to_organizations_table',1),
  (40,'2025_09_16_000000_add_soft_deletes_and_indexes_to_tables',1),
  (41,'2025_09_17_193919_remove_unused_permissions_table',1),
  (42,'2025_12_21_000000_add_case_id_to_inquiries_table',1);

COMMIT;
