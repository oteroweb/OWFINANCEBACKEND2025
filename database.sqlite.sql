BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "account_folders" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"user_id"	integer NOT NULL,
	"parent_id"	integer,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("parent_id") REFERENCES "account_folders"("id") on delete cascade,
	FOREIGN KEY("user_id") REFERENCES "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "account_types" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"description"	varchar NOT NULL,
	"icon"	varchar NOT NULL,
	"active"	integer NOT NULL DEFAULT '1',
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "account_user" (
	"id"	integer NOT NULL,
	"user_id"	integer NOT NULL,
	"account_id"	integer NOT NULL,
	"is_owner"	integer NOT NULL DEFAULT ('1'),
	"folder_id"	integer,
	"sort_order"	integer NOT NULL DEFAULT '0',
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("account_id") REFERENCES "accounts"("id") on delete cascade on update no action,
	FOREIGN KEY("folder_id") REFERENCES "account_folders"("id") on delete set null,
	FOREIGN KEY("user_id") REFERENCES "users"("id") on delete cascade on update no action
);
CREATE TABLE IF NOT EXISTS "accounts" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"currency_id"	integer NOT NULL,
	"initial"	numeric NOT NULL,
	"balance"	numeric NOT NULL DEFAULT '0',
	"account_type_id"	integer NOT NULL,
	"active"	integer NOT NULL DEFAULT '1',
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	"balance_cached"	numeric NOT NULL DEFAULT '0',
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("account_type_id") REFERENCES "account_types"("id"),
	FOREIGN KEY("currency_id") REFERENCES "currencies"("id")
);
CREATE TABLE IF NOT EXISTS "accounts_taxes" (
	"id"	integer NOT NULL,
	"account_id"	integer NOT NULL,
	"tax_id"	integer NOT NULL,
	"amount"	numeric,
	"percent"	numeric,
	"active"	integer NOT NULL DEFAULT '1',
	"deleted_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("account_id") REFERENCES "accounts"("id") on delete cascade,
	FOREIGN KEY("tax_id") REFERENCES "taxes"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "cache" (
	"key"	varchar NOT NULL,
	"value"	text NOT NULL,
	"expiration"	integer NOT NULL,
	PRIMARY KEY("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks" (
	"key"	varchar NOT NULL,
	"owner"	varchar NOT NULL,
	"expiration"	integer NOT NULL,
	PRIMARY KEY("key")
);
CREATE TABLE IF NOT EXISTS "categories" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"active"	integer NOT NULL DEFAULT ('1'),
	"date"	date,
	"parent_id"	integer,
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	"user_id"	integer,
	"icon"	varchar,
	"transaction_type_id"	integer,
	"include_in_balance"	tinyint(1) NOT NULL DEFAULT '1',
	"type"	varchar NOT NULL DEFAULT 'category' CHECK("type" IN ('folder', 'category')),
	"sort_order"	integer NOT NULL DEFAULT '0',
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("parent_id") REFERENCES "categories"("id") on delete set null on update no action,
	FOREIGN KEY("transaction_type_id") REFERENCES "transaction_types"("id") on delete set null,
	FOREIGN KEY("user_id") REFERENCES "users"("id") on delete cascade on update no action
);
CREATE TABLE IF NOT EXISTS "category_templates" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"slug"	varchar NOT NULL,
	"parent_slug"	varchar,
	"sort_order"	integer NOT NULL DEFAULT '0',
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "clients" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"email"	varchar NOT NULL,
	"phone"	varchar,
	"active"	tinyint(1) NOT NULL DEFAULT '1',
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "currencies" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"symbol"	varchar NOT NULL,
	"align"	varchar NOT NULL DEFAULT 'left',
	"code"	varchar NOT NULL,
	"active"	integer NOT NULL DEFAULT '1',
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "failed_jobs" (
	"id"	integer NOT NULL,
	"uuid"	varchar NOT NULL,
	"connection"	text NOT NULL,
	"queue"	text NOT NULL,
	"payload"	text NOT NULL,
	"exception"	text NOT NULL,
	"failed_at"	datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "item_categories" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"active"	integer NOT NULL DEFAULT ('1'),
	"date"	date,
	"deleted_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	"parent_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("parent_id") REFERENCES "item_categories"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "item_taxes" (
	"id"	integer NOT NULL,
	"item_transaction_id"	integer NOT NULL,
	"tax_id"	integer NOT NULL,
	"amount"	numeric NOT NULL,
	"percent"	numeric,
	"active"	integer NOT NULL DEFAULT ('1'),
	"deleted_at"	datetime,
	"date"	date,
	"created_at"	datetime,
	"updated_at"	datetime,
	"item_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("item_id") REFERENCES "items"("id") on delete set null,
	FOREIGN KEY("item_transaction_id") REFERENCES "item_transactions"("id") on delete cascade on update no action,
	FOREIGN KEY("tax_id") REFERENCES "taxes"("id") on delete cascade on update no action
);
CREATE TABLE IF NOT EXISTS "item_transactions" (
	"id"	integer NOT NULL,
	"transaction_id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"amount"	numeric NOT NULL,
	"tax_id"	integer,
	"rate_id"	integer,
	"description"	varchar,
	"jar_id"	integer,
	"active"	integer NOT NULL DEFAULT ('1'),
	"deleted_at"	datetime,
	"date"	datetime NOT NULL,
	"category_id"	integer,
	"user_id"	integer,
	"custom_name"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	"item_id"	integer,
	"quantity"	integer NOT NULL DEFAULT ('1'),
	"item_category_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("category_id") REFERENCES "categories"("id") on delete set null on update no action,
	FOREIGN KEY("item_category_id") REFERENCES "item_categories"("id") on delete set null,
	FOREIGN KEY("item_id") REFERENCES "items"("id") on delete set null on update no action,
	FOREIGN KEY("jar_id") REFERENCES "jars"("id") on delete set null on update no action,
	FOREIGN KEY("rate_id") REFERENCES "rates"("id") on delete set null on update no action,
	FOREIGN KEY("tax_id") REFERENCES "taxes"("id") on delete set null on update no action,
	FOREIGN KEY("transaction_id") REFERENCES "transactions"("id") on delete cascade on update no action,
	FOREIGN KEY("user_id") REFERENCES "users"("id") on delete set null on update no action
);
CREATE TABLE IF NOT EXISTS "items" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"last_price"	numeric,
	"tax_id"	integer,
	"active"	integer NOT NULL DEFAULT '1',
	"date"	date,
	"deleted_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	"custom_name"	varchar,
	"item_category_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("item_category_id") REFERENCES "item_categories"("id") on delete set null,
	FOREIGN KEY("tax_id") REFERENCES "taxes"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "jar_base_category" (
	"id"	integer NOT NULL,
	"jar_id"	integer NOT NULL,
	"category_id"	integer NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	"active"	integer NOT NULL DEFAULT '1',
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("category_id") REFERENCES "categories"("id") on delete cascade,
	FOREIGN KEY("jar_id") REFERENCES "jars"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "jar_category" (
	"id"	integer NOT NULL,
	"jar_id"	integer NOT NULL,
	"category_id"	integer NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	"active"	integer NOT NULL DEFAULT '1',
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("category_id") REFERENCES "categories"("id") on delete cascade,
	FOREIGN KEY("jar_id") REFERENCES "jars"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "jar_template_jar_base_categories" (
	"id"	integer NOT NULL,
	"jar_template_jar_id"	integer NOT NULL,
	"category_id"	integer NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("category_id") REFERENCES "categories"("id") on delete cascade,
	FOREIGN KEY("jar_template_jar_id") REFERENCES "jar_template_jars"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "jar_template_jar_categories" (
	"id"	integer NOT NULL,
	"jar_template_jar_id"	integer NOT NULL,
	"category_id"	integer NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("category_id") REFERENCES "categories"("id") on delete cascade,
	FOREIGN KEY("jar_template_jar_id") REFERENCES "jar_template_jars"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "jar_template_jars" (
	"id"	integer NOT NULL,
	"jar_template_id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"type"	varchar NOT NULL CHECK("type" IN ('fixed', 'percent')),
	"percent"	numeric,
	"fixed_amount"	numeric,
	"base_scope"	varchar NOT NULL DEFAULT 'all_income' CHECK("base_scope" IN ('all_income', 'categories')),
	"color"	varchar,
	"sort_order"	integer NOT NULL DEFAULT '0',
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("jar_template_id") REFERENCES "jar_templates"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "jar_templates" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"slug"	varchar NOT NULL,
	"description"	varchar,
	"active"	integer NOT NULL DEFAULT '1',
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "jar_tpljar_base_cat_tpl" (
	"id"	integer NOT NULL,
	"jar_template_jar_id"	integer NOT NULL,
	"category_template_id"	integer NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("category_template_id") REFERENCES "category_templates"("id") on delete cascade,
	FOREIGN KEY("jar_template_jar_id") REFERENCES "jar_template_jars"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "jar_tpljar_cat_tpl" (
	"id"	integer NOT NULL,
	"jar_template_jar_id"	integer NOT NULL,
	"category_template_id"	integer NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("category_template_id") REFERENCES "category_templates"("id") on delete cascade,
	FOREIGN KEY("jar_template_jar_id") REFERENCES "jar_template_jars"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "jars" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"percent"	numeric,
	"type"	varchar,
	"active"	integer NOT NULL DEFAULT ('1'),
	"deleted_at"	datetime,
	"date"	date,
	"created_at"	datetime,
	"updated_at"	datetime,
	"user_id"	integer,
	"fixed_amount"	numeric,
	"base_scope"	varchar NOT NULL DEFAULT ('all_income'),
	"sort_order"	integer NOT NULL DEFAULT ('0'),
	"color"	varchar,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("user_id") REFERENCES "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "job_batches" (
	"id"	varchar NOT NULL,
	"name"	varchar NOT NULL,
	"total_jobs"	integer NOT NULL,
	"pending_jobs"	integer NOT NULL,
	"failed_jobs"	integer NOT NULL,
	"failed_job_ids"	text NOT NULL,
	"options"	text,
	"cancelled_at"	integer,
	"created_at"	integer NOT NULL,
	"finished_at"	integer,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "jobs" (
	"id"	integer NOT NULL,
	"queue"	varchar NOT NULL,
	"payload"	text NOT NULL,
	"attempts"	integer NOT NULL,
	"reserved_at"	integer,
	"available_at"	integer NOT NULL,
	"created_at"	integer NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "migrations" (
	"id"	integer NOT NULL,
	"migration"	varchar NOT NULL,
	"batch"	integer NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens" (
	"email"	varchar NOT NULL,
	"token"	varchar NOT NULL,
	"created_at"	datetime,
	PRIMARY KEY("email")
);
CREATE TABLE IF NOT EXISTS "payment_transaction_taxes" (
	"id"	integer NOT NULL,
	"payment_transaction_id"	integer NOT NULL,
	"tax_id"	integer NOT NULL,
	"amount"	numeric NOT NULL,
	"percent"	numeric,
	"active"	integer NOT NULL DEFAULT '1',
	"deleted_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("payment_transaction_id") REFERENCES "payment_transactions"("id") on delete cascade,
	FOREIGN KEY("tax_id") REFERENCES "taxes"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "payment_transactions" (
	"id"	integer NOT NULL,
	"transaction_id"	integer NOT NULL,
	"account_id"	integer NOT NULL,
	"amount"	numeric NOT NULL,
	"active"	integer NOT NULL DEFAULT ('1'),
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	"user_currency_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("account_id") REFERENCES "accounts"("id") on delete cascade on update no action,
	FOREIGN KEY("transaction_id") REFERENCES "transactions"("id") on delete cascade on update no action,
	FOREIGN KEY("user_currency_id") REFERENCES "user_currencies"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens" (
	"id"	integer NOT NULL,
	"tokenable_type"	varchar NOT NULL,
	"tokenable_id"	integer NOT NULL,
	"name"	text NOT NULL,
	"token"	varchar NOT NULL,
	"abilities"	text,
	"last_used_at"	datetime,
	"expires_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "providers" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"description"	varchar,
	"address"	varchar,
	"email"	varchar,
	"phone"	varchar,
	"website"	varchar,
	"logo"	varchar,
	"country"	varchar,
	"city"	varchar,
	"postal_code"	varchar,
	"state"	varchar,
	"user_id"	integer,
	"active"	integer NOT NULL DEFAULT '1',
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("user_id") REFERENCES "users"("id")
);
CREATE TABLE IF NOT EXISTS "rates" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"date"	date NOT NULL,
	"value"	numeric NOT NULL DEFAULT '0',
	"active"	integer NOT NULL DEFAULT '1',
	"deleted_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "roles" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"slug"	varchar NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "sessions" (
	"id"	varchar NOT NULL,
	"user_id"	integer,
	"ip_address"	varchar,
	"user_agent"	text,
	"payload"	text NOT NULL,
	"last_activity"	integer NOT NULL,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "taxes" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"percent"	numeric NOT NULL,
	"active"	integer NOT NULL DEFAULT '1',
	"date"	date,
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	"applies_to"	varchar NOT NULL DEFAULT 'item' CHECK("applies_to" IN ('item', 'payment', 'both')),
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "transaction_types" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"slug"	varchar NOT NULL,
	"description"	varchar,
	"active"	integer NOT NULL DEFAULT '1',
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "transactions" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"amount"	numeric NOT NULL,
	"description"	varchar,
	"date"	datetime NOT NULL,
	"active"	integer NOT NULL DEFAULT ('1'),
	"provider_id"	integer,
	"url_file"	varchar,
	"rate_id"	integer,
	"user_id"	integer,
	"account_id"	integer,
	"amount_tax"	numeric,
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	"transaction_type_id"	integer,
	"include_in_balance"	tinyint(1) NOT NULL DEFAULT ('1'),
	"category_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("account_id") REFERENCES "accounts"("id") on delete set null on update no action,
	FOREIGN KEY("category_id") REFERENCES "categories"("id") on delete set null,
	FOREIGN KEY("provider_id") REFERENCES "providers"("id") on delete no action on update no action,
	FOREIGN KEY("rate_id") REFERENCES "rates"("id") on delete set null on update no action,
	FOREIGN KEY("transaction_type_id") REFERENCES "transaction_types"("id") on delete set null on update no action,
	FOREIGN KEY("user_id") REFERENCES "users"("id") on delete set null on update no action
);
CREATE TABLE IF NOT EXISTS "user_currencies" (
	"id"	integer NOT NULL,
	"user_id"	integer NOT NULL,
	"currency_id"	integer NOT NULL,
	"current_rate"	numeric,
	"is_current"	tinyint(1) NOT NULL DEFAULT '0',
	"created_at"	datetime,
	"updated_at"	datetime,
	"is_official"	tinyint(1) NOT NULL DEFAULT '1',
	"official_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("currency_id") REFERENCES "currencies"("id") on delete cascade,
	FOREIGN KEY("user_id") REFERENCES "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "users" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"phone"	varchar,
	"email"	varchar NOT NULL,
	"email_verified_at"	datetime,
	"password"	varchar NOT NULL,
	"balance"	numeric NOT NULL DEFAULT ('0'),
	"currency_id"	integer,
	"client_id"	integer,
	"active"	tinyint(1) NOT NULL DEFAULT ('1'),
	"deleted_at"	datetime,
	"remember_token"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	"role_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("client_id") REFERENCES "clients"("id") on delete set null on update no action,
	FOREIGN KEY("currency_id") REFERENCES "currencies"("id") on delete set null on update no action,
	FOREIGN KEY("role_id") REFERENCES "roles"("id") on delete set null
);
INSERT INTO "account_types" VALUES (1,'Cuenta Bancaria','Cuenta bancaria tradicional para ingresos y gastos.','bank',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "account_types" VALUES (2,'Tarjeta de Credito','Tarjeta de crédito para compras y pagos diferidos.','credit-card',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "account_types" VALUES (3,'Con interes','Cuenta con intereses generados sobre el saldo.','percent',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "account_types" VALUES (4,'Deuda','Registro de deudas o cuentas por pagar.','debt',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "account_types" VALUES (5,'Prestamo','Préstamos otorgados o recibidos.','loan',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "account_types" VALUES (6,'Efectivo','Dinero en efectivo disponible.','cash',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "account_types" VALUES (7,'Cashea','Cuenta para operaciones de Cashéa.','wallet',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "account_user" VALUES (1,4,11,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (2,4,12,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (3,4,13,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (4,4,14,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (5,4,15,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (6,4,16,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (7,4,17,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (8,4,18,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (9,4,19,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (10,4,20,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (11,4,21,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (12,4,22,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (13,4,23,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (14,4,24,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (15,4,25,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (16,4,26,1,NULL,0,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "account_user" VALUES (17,4,27,1,NULL,0,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "account_user" VALUES (18,4,28,1,NULL,0,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "account_user" VALUES (19,4,29,1,NULL,0,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "account_user" VALUES (20,4,30,1,NULL,0,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "account_user" VALUES (21,4,31,1,NULL,0,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "account_user" VALUES (22,4,32,1,NULL,0,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "account_user" VALUES (23,4,33,1,NULL,0,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "account_user" VALUES (24,4,34,1,NULL,0,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts" VALUES (1,'aliquid',13,413.62,3677.62,1,0,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (2,'sit',8,958.37,312.8,3,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (3,'ut',6,439.31,9724.49,1,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (4,'sit',7,216.9,8706.59,3,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (5,'dolore',13,286.89,5746.94,4,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (6,'tenetur',5,367.1,6113.18,4,0,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (7,'dolorum',11,567.97,4101.8,7,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (8,'ut',10,599.94,8971.12,6,0,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (9,'voluptas',4,867.15,9756.26,5,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (10,'excepturi',6,880.94,9119.87,7,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,0);
INSERT INTO "accounts" VALUES (11,'Fernando',1,-400,985.82,1,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,985.82);
INSERT INTO "accounts" VALUES (12,'Binance Funding Oteroxx',1,0,0,5,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,0);
INSERT INTO "accounts" VALUES (13,'JL banesco DOLARES',1,83,83,6,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,83);
INSERT INTO "accounts" VALUES (14,'Cuenta Ticktaps',1,-75.53,-75.53,7,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,-75.53);
INSERT INTO "accounts" VALUES (15,'JL Banesco',5,381.15,454206.15,1,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,454206.15);
INSERT INTO "accounts" VALUES (16,'JL Mercantil',5,2643.43,6092.99,1,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,6092.99);
INSERT INTO "accounts" VALUES (17,'Banca amiga',5,129.16,1880.59,7,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,1880.59);
INSERT INTO "accounts" VALUES (18,'paypal Joseluis Dolares',1,0,434.08,7,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,434.08);
INSERT INTO "accounts" VALUES (19,'Deuda Hermano Dolares',1,359.63,359.63,2,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,359.63);
INSERT INTO "accounts" VALUES (20,'deuda pendient bolivares',5,-1124.81,-1124.81,6,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,-1124.81);
INSERT INTO "accounts" VALUES (21,'Platzi deuda a mi hermano',1,-53.97,784.13,3,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,784.13);
INSERT INTO "accounts" VALUES (22,'Deuda Karo',1,103.99,103.99,5,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,103.99);
INSERT INTO "accounts" VALUES (23,'cashea',1,0,0,4,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,0);
INSERT INTO "accounts" VALUES (24,'Dinero Padres',1,0,0,2,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,0);
INSERT INTO "accounts" VALUES (25,'deuda Pareja',5,-3523.95,-3523.95,7,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,-3523.95);
INSERT INTO "accounts" VALUES (26,'Efectivo',1,209.43,209.43,6,1,'2025-10-06 15:01:50','2025-11-16 08:23:41',NULL,209.43);
INSERT INTO "accounts" VALUES (27,'Dolares efectivo',1,3370,4129.48,3,1,'2025-10-06 15:01:51','2025-11-16 08:23:41',NULL,4129.48);
INSERT INTO "accounts" VALUES (28,'EFECTIVO EUROS',2,185,185,3,1,'2025-10-06 15:01:51','2025-11-16 08:23:41',NULL,185);
INSERT INTO "accounts" VALUES (29,'efectivo bolivares',5,480,952.78,3,1,'2025-10-06 15:01:51','2025-11-16 08:23:41',NULL,952.78);
INSERT INTO "accounts" VALUES (30,'cuenta cartera dolares',1,66,1035.98,4,1,'2025-10-06 15:01:51','2025-11-16 08:23:41',NULL,1035.98);
INSERT INTO "accounts" VALUES (31,'Binance Ahorro',1,-538,-240,4,1,'2025-10-06 15:01:51','2025-11-16 08:23:41',NULL,-240);
INSERT INTO "accounts" VALUES (32,'DAP HERMANO',1,13,23,7,1,'2025-10-06 15:01:51','2025-11-16 08:23:41',NULL,23);
INSERT INTO "accounts" VALUES (33,'payoneer',1,28.92,28.92,6,1,'2025-10-06 15:01:51','2025-11-16 08:23:41',NULL,28.92);
INSERT INTO "accounts" VALUES (34,'Paypal Euros Jose luis',2,0,30,2,1,'2025-10-06 15:01:51','2025-11-16 08:23:41',NULL,30);
INSERT INTO "accounts_taxes" VALUES (1,1,1,644.44,74.93,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts_taxes" VALUES (2,1,1,345.81,68.07,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts_taxes" VALUES (3,1,1,302.63,99.84,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts_taxes" VALUES (4,1,1,502.91,67.24,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts_taxes" VALUES (5,1,1,957.02,16.76,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts_taxes" VALUES (6,1,1,537.18,38.6,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts_taxes" VALUES (7,1,1,142.27,4.96,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts_taxes" VALUES (8,1,1,28.21,73.93,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts_taxes" VALUES (9,1,1,707.43,83.19,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "accounts_taxes" VALUES (10,1,1,950.88,46.68,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "categories" VALUES (1,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (2,'Salario',1,NULL,1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (3,'Negocios / Freelance',1,NULL,1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (4,'Inversiones',1,NULL,1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (5,'Rentas / Alquileres',1,NULL,1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (6,'Venta de bienes / servicios',1,NULL,1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (7,'Regalos / Donaciones recibidas',1,NULL,1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (8,'Reembolsos / Devoluciones',1,NULL,1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (9,'Otros ingresos',1,NULL,1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (10,'Gastos',1,NULL,NULL,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (11,'Hogar',1,NULL,10,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (12,'Alquiler',1,NULL,11,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (13,'Hipoteca',1,NULL,11,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (14,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,11,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (15,'Comunidad / Impuestos',1,NULL,11,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (16,'Seguros',1,NULL,11,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (17,'Decoración / Mantenimiento',1,NULL,11,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (18,'Compras comodidad hogar',1,NULL,11,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (19,'Alimentación',1,NULL,10,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (20,'Supermercado',1,NULL,19,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (21,'Comida en restaurantes',1,NULL,19,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (22,'Comida calle / rápida',1,NULL,19,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (23,'Café / Snacks',1,NULL,19,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (24,'Transporte',1,NULL,10,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (25,'Transporte público',1,NULL,24,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (26,'Combustible / Gasolina',1,NULL,24,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (27,'Mantenimiento / Taller',1,NULL,24,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (28,'Seguros vehiculares',1,NULL,24,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (29,'Impuestos vehiculares',1,NULL,24,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (30,'Salud y Bienestar',1,NULL,10,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (31,'Seguro médico',1,NULL,30,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (32,'Consultas / Salud médica',1,NULL,30,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (33,'Farmacia / Medicamentos',1,NULL,30,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (34,'Suplementos',1,NULL,30,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (35,'Gimnasio',1,NULL,30,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (36,'Bienestar personal (peluquería, spa, etc.)',1,NULL,30,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (37,'Educación',1,NULL,10,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (38,'Colegaturas',1,NULL,37,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (39,'Cursos / Talleres',1,NULL,37,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (40,'Libros y materiales',1,NULL,37,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (41,'Ocio y Entretenimiento',1,NULL,10,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (42,'Cine / Música / Streaming',1,NULL,41,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (43,'Viajes',1,NULL,41,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (44,'Eventos sociales',1,NULL,41,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (45,'Compras personales (ropa, gadgets, etc.)',1,NULL,41,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (46,'Donaciones',1,NULL,10,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (47,'Familia',1,NULL,46,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (48,'Benéficas',1,NULL,46,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (49,'Finanzas',1,NULL,10,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (50,'Tarjetas de crédito (intereses, comisiones)',1,NULL,49,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (51,'Impuestos financieros',1,NULL,49,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (52,'Comisiones bancarias',1,NULL,49,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (53,'Retirada de efectivo',1,NULL,49,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (54,'Traspasos entre cuentas',1,NULL,49,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (55,'Ajustes de cuenta',1,NULL,49,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (56,'Mascotas',1,NULL,10,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (57,'Veterinario',1,NULL,56,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (58,'Comida',1,NULL,56,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (59,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (60,'Fondo de emergencia',1,NULL,59,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (61,'Ahorro para retiro',1,NULL,59,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (62,'Inversiones (bolsa, cripto, etc.)',1,NULL,59,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (63,'Compra de activos (casa, coche, etc.)',1,NULL,59,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,1,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (64,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL,2,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (65,'Salario',1,NULL,64,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (66,'Negocios / Freelance',1,NULL,64,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (67,'Inversiones',1,NULL,64,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (68,'Rentas / Alquileres',1,NULL,64,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (69,'Venta de bienes / servicios',1,NULL,64,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (70,'Regalos / Donaciones recibidas',1,NULL,64,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (71,'Reembolsos / Devoluciones',1,NULL,64,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (72,'Otros ingresos',1,NULL,64,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (73,'Gastos',1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (74,'Hogar',1,NULL,73,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (75,'Alquiler',1,NULL,74,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (76,'Hipoteca',1,NULL,74,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (77,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,74,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (78,'Comunidad / Impuestos',1,NULL,74,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (79,'Seguros',1,NULL,74,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (80,'Decoración / Mantenimiento',1,NULL,74,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (81,'Compras comodidad hogar',1,NULL,74,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (82,'Alimentación',1,NULL,73,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (83,'Supermercado',1,NULL,82,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (84,'Comida en restaurantes',1,NULL,82,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (85,'Comida calle / rápida',1,NULL,82,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (86,'Café / Snacks',1,NULL,82,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (87,'Transporte',1,NULL,73,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (88,'Transporte público',1,NULL,87,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (89,'Combustible / Gasolina',1,NULL,87,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (90,'Mantenimiento / Taller',1,NULL,87,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (91,'Seguros vehiculares',1,NULL,87,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (92,'Impuestos vehiculares',1,NULL,87,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (93,'Salud y Bienestar',1,NULL,73,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (94,'Seguro médico',1,NULL,93,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (95,'Consultas / Salud médica',1,NULL,93,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (96,'Farmacia / Medicamentos',1,NULL,93,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (97,'Suplementos',1,NULL,93,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (98,'Gimnasio',1,NULL,93,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (99,'Bienestar personal (peluquería, spa, etc.)',1,NULL,93,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (100,'Educación',1,NULL,73,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (101,'Colegaturas',1,NULL,100,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (102,'Cursos / Talleres',1,NULL,100,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (103,'Libros y materiales',1,NULL,100,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (104,'Ocio y Entretenimiento',1,NULL,73,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (105,'Cine / Música / Streaming',1,NULL,104,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (106,'Viajes',1,NULL,104,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (107,'Eventos sociales',1,NULL,104,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (108,'Compras personales (ropa, gadgets, etc.)',1,NULL,104,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (109,'Donaciones',1,NULL,73,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (110,'Familia',1,NULL,109,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (111,'Benéficas',1,NULL,109,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (112,'Finanzas',1,NULL,73,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (113,'Tarjetas de crédito (intereses, comisiones)',1,NULL,112,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (114,'Impuestos financieros',1,NULL,112,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (115,'Comisiones bancarias',1,NULL,112,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (116,'Retirada de efectivo',1,NULL,112,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (117,'Traspasos entre cuentas',1,NULL,112,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (118,'Ajustes de cuenta',1,NULL,112,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (119,'Mascotas',1,NULL,73,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (120,'Veterinario',1,NULL,119,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (121,'Comida',1,NULL,119,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (122,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (123,'Fondo de emergencia',1,NULL,122,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (124,'Ahorro para retiro',1,NULL,122,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (125,'Inversiones (bolsa, cripto, etc.)',1,NULL,122,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (126,'Compra de activos (casa, coche, etc.)',1,NULL,122,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,2,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (127,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (128,'Salario',1,NULL,127,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (129,'Negocios / Freelance',1,NULL,127,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (130,'Inversiones',1,NULL,127,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (131,'Rentas / Alquileres',1,NULL,127,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (132,'Venta de bienes / servicios',1,NULL,127,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (133,'Regalos / Donaciones recibidas',1,NULL,127,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (134,'Reembolsos / Devoluciones',1,NULL,127,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (135,'Otros ingresos',1,NULL,127,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (136,'Gastos',1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (137,'Hogar',1,NULL,136,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (138,'Alquiler',1,NULL,137,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (139,'Hipoteca',1,NULL,137,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (140,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,137,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (141,'Comunidad / Impuestos',1,NULL,137,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (142,'Seguros',1,NULL,137,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (143,'Decoración / Mantenimiento',1,NULL,137,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (144,'Compras comodidad hogar',1,NULL,137,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (145,'Alimentación',1,NULL,136,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (146,'Supermercado',1,NULL,145,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (147,'Comida en restaurantes',1,NULL,145,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (148,'Comida calle / rápida',1,NULL,145,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (149,'Café / Snacks',1,NULL,145,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (150,'Transporte',1,NULL,136,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (151,'Transporte público',1,NULL,150,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (152,'Combustible / Gasolina',1,NULL,150,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (153,'Mantenimiento / Taller',1,NULL,150,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (154,'Seguros vehiculares',1,NULL,150,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (155,'Impuestos vehiculares',1,NULL,150,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (156,'Salud y Bienestar',1,NULL,136,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (157,'Seguro médico',1,NULL,156,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (158,'Consultas / Salud médica',1,NULL,156,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (159,'Farmacia / Medicamentos',1,NULL,156,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (160,'Suplementos',1,NULL,156,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (161,'Gimnasio',1,NULL,156,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (162,'Bienestar personal (peluquería, spa, etc.)',1,NULL,156,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (163,'Educación',1,NULL,136,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (164,'Colegaturas',1,NULL,163,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (165,'Cursos / Talleres',1,NULL,163,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (166,'Libros y materiales',1,NULL,163,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (167,'Ocio y Entretenimiento',1,NULL,136,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (168,'Cine / Música / Streaming',1,NULL,167,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (169,'Viajes',1,NULL,167,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (170,'Eventos sociales',1,NULL,167,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (171,'Compras personales (ropa, gadgets, etc.)',1,NULL,167,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (172,'Donaciones',1,NULL,136,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (173,'Familia',1,NULL,172,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (174,'Benéficas',1,NULL,172,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (175,'Finanzas',1,NULL,136,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (176,'Tarjetas de crédito (intereses, comisiones)',1,NULL,175,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (177,'Impuestos financieros',1,NULL,175,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (178,'Comisiones bancarias',1,NULL,175,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (179,'Retirada de efectivo',1,NULL,175,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (180,'Traspasos entre cuentas',1,NULL,175,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (181,'Ajustes de cuenta',1,NULL,175,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (182,'Mascotas',1,NULL,136,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (183,'Veterinario',1,NULL,182,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (184,'Comida',1,NULL,182,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (185,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (186,'Fondo de emergencia',1,NULL,185,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (187,'Ahorro para retiro',1,NULL,185,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (188,'Inversiones (bolsa, cripto, etc.)',1,NULL,185,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (189,'Compra de activos (casa, coche, etc.)',1,NULL,185,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,3,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (190,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (191,'Salario',1,NULL,190,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (192,'Negocios / Freelance',1,NULL,190,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (193,'Inversiones',1,NULL,190,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (194,'Rentas / Alquileres',1,NULL,190,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (195,'Venta de bienes / servicios',1,NULL,190,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (196,'Regalos / Donaciones recibidas',1,NULL,190,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (197,'Reembolsos / Devoluciones',1,NULL,190,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (198,'Otros ingresos',1,NULL,190,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (199,'Gastos',1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (200,'Hogar',1,NULL,199,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (201,'Alquiler',1,NULL,200,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (202,'Hipoteca',1,NULL,200,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (203,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,200,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (204,'Comunidad / Impuestos',1,NULL,200,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (205,'Seguros',1,NULL,200,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (206,'Decoración / Mantenimiento',1,NULL,200,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (207,'Compras comodidad hogar',1,NULL,200,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (208,'Alimentación',1,NULL,199,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (209,'Supermercado',1,NULL,208,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (210,'Comida en restaurantes',1,NULL,208,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (211,'Comida calle / rápida',1,NULL,208,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (212,'Café / Snacks',1,NULL,208,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (213,'Transporte',1,NULL,199,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (214,'Transporte público',1,NULL,213,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (215,'Combustible / Gasolina',1,NULL,213,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (216,'Mantenimiento / Taller',1,NULL,213,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (217,'Seguros vehiculares',1,NULL,213,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (218,'Impuestos vehiculares',1,NULL,213,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (219,'Salud y Bienestar',1,NULL,199,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (220,'Seguro médico',1,NULL,219,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (221,'Consultas / Salud médica',1,NULL,219,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (222,'Farmacia / Medicamentos',1,NULL,219,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (223,'Suplementos',1,NULL,219,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (224,'Gimnasio',1,NULL,219,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (225,'Bienestar personal (peluquería, spa, etc.)',1,NULL,219,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (226,'Educación',1,NULL,199,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (227,'Colegaturas',1,NULL,226,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (228,'Cursos / Talleres',1,NULL,226,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (229,'Libros y materiales',1,NULL,226,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (230,'Ocio y Entretenimiento',1,NULL,199,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (231,'Cine / Música / Streaming',1,NULL,230,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (232,'Viajes',1,NULL,230,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (233,'Eventos sociales',1,NULL,230,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (234,'Compras personales (ropa, gadgets, etc.)',1,NULL,230,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (235,'Donaciones',1,NULL,199,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (236,'Familia',1,NULL,235,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (237,'Benéficas',1,NULL,235,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (238,'Finanzas',1,NULL,199,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (239,'Tarjetas de crédito (intereses, comisiones)',1,NULL,238,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (240,'Impuestos financieros',1,NULL,238,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (241,'Comisiones bancarias',1,NULL,238,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (242,'Retirada de efectivo',1,NULL,238,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (243,'Traspasos entre cuentas',1,NULL,238,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (244,'Ajustes de cuenta',1,NULL,238,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (245,'Mascotas',1,NULL,199,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (246,'Veterinario',1,NULL,245,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (247,'Comida',1,NULL,245,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (248,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (249,'Fondo de emergencia',1,NULL,248,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (250,'Ahorro para retiro',1,NULL,248,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (251,'Inversiones (bolsa, cripto, etc.)',1,NULL,248,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (252,'Compra de activos (casa, coche, etc.)',1,NULL,248,'2025-10-06 15:01:49','2025-10-06 15:01:49',NULL,4,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (253,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (254,'Salario',1,NULL,253,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (255,'Negocios / Freelance',1,NULL,253,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (256,'Inversiones',1,NULL,253,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (257,'Rentas / Alquileres',1,NULL,253,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (258,'Venta de bienes / servicios',1,NULL,253,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (259,'Regalos / Donaciones recibidas',1,NULL,253,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (260,'Reembolsos / Devoluciones',1,NULL,253,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (261,'Otros ingresos',1,NULL,253,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (262,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (263,'Hogar',1,NULL,262,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (264,'Alquiler',1,NULL,263,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (265,'Hipoteca',1,NULL,263,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (266,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,263,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (267,'Comunidad / Impuestos',1,NULL,263,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (268,'Seguros',1,NULL,263,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (269,'Decoración / Mantenimiento',1,NULL,263,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (270,'Compras comodidad hogar',1,NULL,263,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (271,'Alimentación',1,NULL,262,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (272,'Supermercado',1,NULL,271,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (273,'Comida en restaurantes',1,NULL,271,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (274,'Comida calle / rápida',1,NULL,271,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (275,'Café / Snacks',1,NULL,271,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (276,'Transporte',1,NULL,262,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (277,'Transporte público',1,NULL,276,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (278,'Combustible / Gasolina',1,NULL,276,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (279,'Mantenimiento / Taller',1,NULL,276,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (280,'Seguros vehiculares',1,NULL,276,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (281,'Impuestos vehiculares',1,NULL,276,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (282,'Salud y Bienestar',1,NULL,262,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (283,'Seguro médico',1,NULL,282,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (284,'Consultas / Salud médica',1,NULL,282,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (285,'Farmacia / Medicamentos',1,NULL,282,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (286,'Suplementos',1,NULL,282,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (287,'Gimnasio',1,NULL,282,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (288,'Bienestar personal (peluquería, spa, etc.)',1,NULL,282,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (289,'Educación',1,NULL,262,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (290,'Colegaturas',1,NULL,289,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (291,'Cursos / Talleres',1,NULL,289,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (292,'Libros y materiales',1,NULL,289,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (293,'Ocio y Entretenimiento',1,NULL,262,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (294,'Cine / Música / Streaming',1,NULL,293,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (295,'Viajes',1,NULL,293,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (296,'Eventos sociales',1,NULL,293,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (297,'Compras personales (ropa, gadgets, etc.)',1,NULL,293,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (298,'Donaciones',1,NULL,262,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (299,'Familia',1,NULL,298,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (300,'Benéficas',1,NULL,298,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (301,'Finanzas',1,NULL,262,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (302,'Tarjetas de crédito (intereses, comisiones)',1,NULL,301,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (303,'Impuestos financieros',1,NULL,301,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (304,'Comisiones bancarias',1,NULL,301,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (305,'Retirada de efectivo',1,NULL,301,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (306,'Traspasos entre cuentas',1,NULL,301,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (307,'Ajustes de cuenta',1,NULL,301,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (308,'Mascotas',1,NULL,262,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (309,'Veterinario',1,NULL,308,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (310,'Comida',1,NULL,308,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (311,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (312,'Fondo de emergencia',1,NULL,311,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (313,'Ahorro para retiro',1,NULL,311,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (314,'Inversiones (bolsa, cripto, etc.)',1,NULL,311,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (315,'Compra de activos (casa, coche, etc.)',1,NULL,311,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,5,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (316,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (317,'Salario',1,NULL,316,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (318,'Negocios / Freelance',1,NULL,316,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (319,'Inversiones',1,NULL,316,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (320,'Rentas / Alquileres',1,NULL,316,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (321,'Venta de bienes / servicios',1,NULL,316,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (322,'Regalos / Donaciones recibidas',1,NULL,316,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (323,'Reembolsos / Devoluciones',1,NULL,316,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (324,'Otros ingresos',1,NULL,316,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (325,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (326,'Hogar',1,NULL,325,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (327,'Alquiler',1,NULL,326,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (328,'Hipoteca',1,NULL,326,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (329,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,326,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (330,'Comunidad / Impuestos',1,NULL,326,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (331,'Seguros',1,NULL,326,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (332,'Decoración / Mantenimiento',1,NULL,326,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (333,'Compras comodidad hogar',1,NULL,326,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (334,'Alimentación',1,NULL,325,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (335,'Supermercado',1,NULL,334,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (336,'Comida en restaurantes',1,NULL,334,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (337,'Comida calle / rápida',1,NULL,334,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (338,'Café / Snacks',1,NULL,334,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (339,'Transporte',1,NULL,325,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (340,'Transporte público',1,NULL,339,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (341,'Combustible / Gasolina',1,NULL,339,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (342,'Mantenimiento / Taller',1,NULL,339,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (343,'Seguros vehiculares',1,NULL,339,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (344,'Impuestos vehiculares',1,NULL,339,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (345,'Salud y Bienestar',1,NULL,325,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (346,'Seguro médico',1,NULL,345,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (347,'Consultas / Salud médica',1,NULL,345,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (348,'Farmacia / Medicamentos',1,NULL,345,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (349,'Suplementos',1,NULL,345,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (350,'Gimnasio',1,NULL,345,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (351,'Bienestar personal (peluquería, spa, etc.)',1,NULL,345,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (352,'Educación',1,NULL,325,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (353,'Colegaturas',1,NULL,352,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (354,'Cursos / Talleres',1,NULL,352,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (355,'Libros y materiales',1,NULL,352,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (356,'Ocio y Entretenimiento',1,NULL,325,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (357,'Cine / Música / Streaming',1,NULL,356,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (358,'Viajes',1,NULL,356,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (359,'Eventos sociales',1,NULL,356,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (360,'Compras personales (ropa, gadgets, etc.)',1,NULL,356,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (361,'Donaciones',1,NULL,325,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (362,'Familia',1,NULL,361,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (363,'Benéficas',1,NULL,361,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (364,'Finanzas',1,NULL,325,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (365,'Tarjetas de crédito (intereses, comisiones)',1,NULL,364,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (366,'Impuestos financieros',1,NULL,364,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (367,'Comisiones bancarias',1,NULL,364,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (368,'Retirada de efectivo',1,NULL,364,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (369,'Traspasos entre cuentas',1,NULL,364,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (370,'Ajustes de cuenta',1,NULL,364,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (371,'Mascotas',1,NULL,325,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (372,'Veterinario',1,NULL,371,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (373,'Comida',1,NULL,371,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (374,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (375,'Fondo de emergencia',1,NULL,374,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (376,'Ahorro para retiro',1,NULL,374,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (377,'Inversiones (bolsa, cripto, etc.)',1,NULL,374,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (378,'Compra de activos (casa, coche, etc.)',1,NULL,374,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,6,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (379,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (380,'Salario',1,NULL,379,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (381,'Negocios / Freelance',1,NULL,379,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (382,'Inversiones',1,NULL,379,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (383,'Rentas / Alquileres',1,NULL,379,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (384,'Venta de bienes / servicios',1,NULL,379,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (385,'Regalos / Donaciones recibidas',1,NULL,379,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (386,'Reembolsos / Devoluciones',1,NULL,379,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (387,'Otros ingresos',1,NULL,379,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (388,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (389,'Hogar',1,NULL,388,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (390,'Alquiler',1,NULL,389,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (391,'Hipoteca',1,NULL,389,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (392,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,389,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (393,'Comunidad / Impuestos',1,NULL,389,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (394,'Seguros',1,NULL,389,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (395,'Decoración / Mantenimiento',1,NULL,389,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (396,'Compras comodidad hogar',1,NULL,389,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (397,'Alimentación',1,NULL,388,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (398,'Supermercado',1,NULL,397,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (399,'Comida en restaurantes',1,NULL,397,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (400,'Comida calle / rápida',1,NULL,397,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (401,'Café / Snacks',1,NULL,397,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (402,'Transporte',1,NULL,388,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (403,'Transporte público',1,NULL,402,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (404,'Combustible / Gasolina',1,NULL,402,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (405,'Mantenimiento / Taller',1,NULL,402,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (406,'Seguros vehiculares',1,NULL,402,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (407,'Impuestos vehiculares',1,NULL,402,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (408,'Salud y Bienestar',1,NULL,388,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (409,'Seguro médico',1,NULL,408,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (410,'Consultas / Salud médica',1,NULL,408,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (411,'Farmacia / Medicamentos',1,NULL,408,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (412,'Suplementos',1,NULL,408,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (413,'Gimnasio',1,NULL,408,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (414,'Bienestar personal (peluquería, spa, etc.)',1,NULL,408,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (415,'Educación',1,NULL,388,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (416,'Colegaturas',1,NULL,415,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (417,'Cursos / Talleres',1,NULL,415,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (418,'Libros y materiales',1,NULL,415,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (419,'Ocio y Entretenimiento',1,NULL,388,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (420,'Cine / Música / Streaming',1,NULL,419,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (421,'Viajes',1,NULL,419,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (422,'Eventos sociales',1,NULL,419,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (423,'Compras personales (ropa, gadgets, etc.)',1,NULL,419,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (424,'Donaciones',1,NULL,388,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (425,'Familia',1,NULL,424,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (426,'Benéficas',1,NULL,424,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (427,'Finanzas',1,NULL,388,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (428,'Tarjetas de crédito (intereses, comisiones)',1,NULL,427,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (429,'Impuestos financieros',1,NULL,427,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (430,'Comisiones bancarias',1,NULL,427,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (431,'Retirada de efectivo',1,NULL,427,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (432,'Traspasos entre cuentas',1,NULL,427,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (433,'Ajustes de cuenta',1,NULL,427,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (434,'Mascotas',1,NULL,388,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (435,'Veterinario',1,NULL,434,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (436,'Comida',1,NULL,434,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (437,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (438,'Fondo de emergencia',1,NULL,437,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (439,'Ahorro para retiro',1,NULL,437,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (440,'Inversiones (bolsa, cripto, etc.)',1,NULL,437,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (441,'Compra de activos (casa, coche, etc.)',1,NULL,437,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,7,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (442,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (443,'Salario',1,NULL,442,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (444,'Negocios / Freelance',1,NULL,442,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (445,'Inversiones',1,NULL,442,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (446,'Rentas / Alquileres',1,NULL,442,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (447,'Venta de bienes / servicios',1,NULL,442,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (448,'Regalos / Donaciones recibidas',1,NULL,442,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (449,'Reembolsos / Devoluciones',1,NULL,442,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (450,'Otros ingresos',1,NULL,442,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (451,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (452,'Hogar',1,NULL,451,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (453,'Alquiler',1,NULL,452,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (454,'Hipoteca',1,NULL,452,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (455,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,452,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (456,'Comunidad / Impuestos',1,NULL,452,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (457,'Seguros',1,NULL,452,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (458,'Decoración / Mantenimiento',1,NULL,452,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (459,'Compras comodidad hogar',1,NULL,452,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (460,'Alimentación',1,NULL,451,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (461,'Supermercado',1,NULL,460,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (462,'Comida en restaurantes',1,NULL,460,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (463,'Comida calle / rápida',1,NULL,460,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (464,'Café / Snacks',1,NULL,460,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (465,'Transporte',1,NULL,451,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (466,'Transporte público',1,NULL,465,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (467,'Combustible / Gasolina',1,NULL,465,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (468,'Mantenimiento / Taller',1,NULL,465,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (469,'Seguros vehiculares',1,NULL,465,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (470,'Impuestos vehiculares',1,NULL,465,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (471,'Salud y Bienestar',1,NULL,451,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (472,'Seguro médico',1,NULL,471,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (473,'Consultas / Salud médica',1,NULL,471,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (474,'Farmacia / Medicamentos',1,NULL,471,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (475,'Suplementos',1,NULL,471,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (476,'Gimnasio',1,NULL,471,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (477,'Bienestar personal (peluquería, spa, etc.)',1,NULL,471,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (478,'Educación',1,NULL,451,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (479,'Colegaturas',1,NULL,478,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (480,'Cursos / Talleres',1,NULL,478,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (481,'Libros y materiales',1,NULL,478,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (482,'Ocio y Entretenimiento',1,NULL,451,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (483,'Cine / Música / Streaming',1,NULL,482,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (484,'Viajes',1,NULL,482,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (485,'Eventos sociales',1,NULL,482,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (486,'Compras personales (ropa, gadgets, etc.)',1,NULL,482,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (487,'Donaciones',1,NULL,451,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (488,'Familia',1,NULL,487,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (489,'Benéficas',1,NULL,487,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (490,'Finanzas',1,NULL,451,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (491,'Tarjetas de crédito (intereses, comisiones)',1,NULL,490,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (492,'Impuestos financieros',1,NULL,490,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (493,'Comisiones bancarias',1,NULL,490,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (494,'Retirada de efectivo',1,NULL,490,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (495,'Traspasos entre cuentas',1,NULL,490,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (496,'Ajustes de cuenta',1,NULL,490,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (497,'Mascotas',1,NULL,451,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (498,'Veterinario',1,NULL,497,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (499,'Comida',1,NULL,497,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (500,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (501,'Fondo de emergencia',1,NULL,500,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (502,'Ahorro para retiro',1,NULL,500,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (503,'Inversiones (bolsa, cripto, etc.)',1,NULL,500,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (504,'Compra de activos (casa, coche, etc.)',1,NULL,500,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,8,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (505,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (506,'Salario',1,NULL,505,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (507,'Negocios / Freelance',1,NULL,505,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (508,'Inversiones',1,NULL,505,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (509,'Rentas / Alquileres',1,NULL,505,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (510,'Venta de bienes / servicios',1,NULL,505,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (511,'Regalos / Donaciones recibidas',1,NULL,505,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (512,'Reembolsos / Devoluciones',1,NULL,505,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (513,'Otros ingresos',1,NULL,505,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (514,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (515,'Hogar',1,NULL,514,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (516,'Alquiler',1,NULL,515,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (517,'Hipoteca',1,NULL,515,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (518,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,515,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (519,'Comunidad / Impuestos',1,NULL,515,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (520,'Seguros',1,NULL,515,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (521,'Decoración / Mantenimiento',1,NULL,515,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (522,'Compras comodidad hogar',1,NULL,515,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (523,'Alimentación',1,NULL,514,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (524,'Supermercado',1,NULL,523,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (525,'Comida en restaurantes',1,NULL,523,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (526,'Comida calle / rápida',1,NULL,523,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (527,'Café / Snacks',1,NULL,523,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (528,'Transporte',1,NULL,514,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (529,'Transporte público',1,NULL,528,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (530,'Combustible / Gasolina',1,NULL,528,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (531,'Mantenimiento / Taller',1,NULL,528,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (532,'Seguros vehiculares',1,NULL,528,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (533,'Impuestos vehiculares',1,NULL,528,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (534,'Salud y Bienestar',1,NULL,514,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (535,'Seguro médico',1,NULL,534,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (536,'Consultas / Salud médica',1,NULL,534,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (537,'Farmacia / Medicamentos',1,NULL,534,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (538,'Suplementos',1,NULL,534,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (539,'Gimnasio',1,NULL,534,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (540,'Bienestar personal (peluquería, spa, etc.)',1,NULL,534,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (541,'Educación',1,NULL,514,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (542,'Colegaturas',1,NULL,541,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (543,'Cursos / Talleres',1,NULL,541,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (544,'Libros y materiales',1,NULL,541,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (545,'Ocio y Entretenimiento',1,NULL,514,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (546,'Cine / Música / Streaming',1,NULL,545,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (547,'Viajes',1,NULL,545,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (548,'Eventos sociales',1,NULL,545,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (549,'Compras personales (ropa, gadgets, etc.)',1,NULL,545,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (550,'Donaciones',1,NULL,514,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (551,'Familia',1,NULL,550,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (552,'Benéficas',1,NULL,550,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (553,'Finanzas',1,NULL,514,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (554,'Tarjetas de crédito (intereses, comisiones)',1,NULL,553,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (555,'Impuestos financieros',1,NULL,553,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (556,'Comisiones bancarias',1,NULL,553,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (557,'Retirada de efectivo',1,NULL,553,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (558,'Traspasos entre cuentas',1,NULL,553,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (559,'Ajustes de cuenta',1,NULL,553,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (560,'Mascotas',1,NULL,514,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (561,'Veterinario',1,NULL,560,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (562,'Comida',1,NULL,560,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (563,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (564,'Fondo de emergencia',1,NULL,563,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (565,'Ahorro para retiro',1,NULL,563,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (566,'Inversiones (bolsa, cripto, etc.)',1,NULL,563,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (567,'Compra de activos (casa, coche, etc.)',1,NULL,563,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,9,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (568,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (569,'Salario',1,NULL,568,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (570,'Negocios / Freelance',1,NULL,568,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (571,'Inversiones',1,NULL,568,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (572,'Rentas / Alquileres',1,NULL,568,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (573,'Venta de bienes / servicios',1,NULL,568,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (574,'Regalos / Donaciones recibidas',1,NULL,568,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (575,'Reembolsos / Devoluciones',1,NULL,568,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (576,'Otros ingresos',1,NULL,568,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (577,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (578,'Hogar',1,NULL,577,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (579,'Alquiler',1,NULL,578,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (580,'Hipoteca',1,NULL,578,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (581,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,578,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (582,'Comunidad / Impuestos',1,NULL,578,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (583,'Seguros',1,NULL,578,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (584,'Decoración / Mantenimiento',1,NULL,578,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (585,'Compras comodidad hogar',1,NULL,578,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (586,'Alimentación',1,NULL,577,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (587,'Supermercado',1,NULL,586,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (588,'Comida en restaurantes',1,NULL,586,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (589,'Comida calle / rápida',1,NULL,586,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (590,'Café / Snacks',1,NULL,586,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (591,'Transporte',1,NULL,577,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (592,'Transporte público',1,NULL,591,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (593,'Combustible / Gasolina',1,NULL,591,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (594,'Mantenimiento / Taller',1,NULL,591,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (595,'Seguros vehiculares',1,NULL,591,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (596,'Impuestos vehiculares',1,NULL,591,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (597,'Salud y Bienestar',1,NULL,577,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (598,'Seguro médico',1,NULL,597,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (599,'Consultas / Salud médica',1,NULL,597,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (600,'Farmacia / Medicamentos',1,NULL,597,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (601,'Suplementos',1,NULL,597,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (602,'Gimnasio',1,NULL,597,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (603,'Bienestar personal (peluquería, spa, etc.)',1,NULL,597,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (604,'Educación',1,NULL,577,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (605,'Colegaturas',1,NULL,604,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (606,'Cursos / Talleres',1,NULL,604,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (607,'Libros y materiales',1,NULL,604,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (608,'Ocio y Entretenimiento',1,NULL,577,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (609,'Cine / Música / Streaming',1,NULL,608,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (610,'Viajes',1,NULL,608,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (611,'Eventos sociales',1,NULL,608,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (612,'Compras personales (ropa, gadgets, etc.)',1,NULL,608,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (613,'Donaciones',1,NULL,577,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (614,'Familia',1,NULL,613,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (615,'Benéficas',1,NULL,613,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (616,'Finanzas',1,NULL,577,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (617,'Tarjetas de crédito (intereses, comisiones)',1,NULL,616,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (618,'Impuestos financieros',1,NULL,616,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (619,'Comisiones bancarias',1,NULL,616,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (620,'Retirada de efectivo',1,NULL,616,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (621,'Traspasos entre cuentas',1,NULL,616,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (622,'Ajustes de cuenta',1,NULL,616,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (623,'Mascotas',1,NULL,577,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (624,'Veterinario',1,NULL,623,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (625,'Comida',1,NULL,623,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (626,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (627,'Fondo de emergencia',1,NULL,626,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (628,'Ahorro para retiro',1,NULL,626,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (629,'Inversiones (bolsa, cripto, etc.)',1,NULL,626,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (630,'Compra de activos (casa, coche, etc.)',1,NULL,626,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,10,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (631,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (632,'Salario',1,NULL,631,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (633,'Negocios / Freelance',1,NULL,631,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (634,'Inversiones',1,NULL,631,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (635,'Rentas / Alquileres',1,NULL,631,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (636,'Venta de bienes / servicios',1,NULL,631,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (637,'Regalos / Donaciones recibidas',1,NULL,631,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (638,'Reembolsos / Devoluciones',1,NULL,631,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (639,'Otros ingresos',1,NULL,631,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (640,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (641,'Hogar',1,NULL,640,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (642,'Alquiler',1,NULL,641,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (643,'Hipoteca',1,NULL,641,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (644,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,641,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (645,'Comunidad / Impuestos',1,NULL,641,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (646,'Seguros',1,NULL,641,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (647,'Decoración / Mantenimiento',1,NULL,641,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (648,'Compras comodidad hogar',1,NULL,641,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (649,'Alimentación',1,NULL,640,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (650,'Supermercado',1,NULL,649,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (651,'Comida en restaurantes',1,NULL,649,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (652,'Comida calle / rápida',1,NULL,649,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (653,'Café / Snacks',1,NULL,649,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (654,'Transporte',1,NULL,640,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (655,'Transporte público',1,NULL,654,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (656,'Combustible / Gasolina',1,NULL,654,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (657,'Mantenimiento / Taller',1,NULL,654,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (658,'Seguros vehiculares',1,NULL,654,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (659,'Impuestos vehiculares',1,NULL,654,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (660,'Salud y Bienestar',1,NULL,640,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (661,'Seguro médico',1,NULL,660,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (662,'Consultas / Salud médica',1,NULL,660,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (663,'Farmacia / Medicamentos',1,NULL,660,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (664,'Suplementos',1,NULL,660,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (665,'Gimnasio',1,NULL,660,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (666,'Bienestar personal (peluquería, spa, etc.)',1,NULL,660,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (667,'Educación',1,NULL,640,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (668,'Colegaturas',1,NULL,667,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (669,'Cursos / Talleres',1,NULL,667,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (670,'Libros y materiales',1,NULL,667,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (671,'Ocio y Entretenimiento',1,NULL,640,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (672,'Cine / Música / Streaming',1,NULL,671,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (673,'Viajes',1,NULL,671,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (674,'Eventos sociales',1,NULL,671,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (675,'Compras personales (ropa, gadgets, etc.)',1,NULL,671,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (676,'Donaciones',1,NULL,640,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (677,'Familia',1,NULL,676,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (678,'Benéficas',1,NULL,676,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (679,'Finanzas',1,NULL,640,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (680,'Tarjetas de crédito (intereses, comisiones)',1,NULL,679,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (681,'Impuestos financieros',1,NULL,679,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (682,'Comisiones bancarias',1,NULL,679,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (683,'Retirada de efectivo',1,NULL,679,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (684,'Traspasos entre cuentas',1,NULL,679,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (685,'Ajustes de cuenta',1,NULL,679,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (686,'Mascotas',1,NULL,640,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (687,'Veterinario',1,NULL,686,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (688,'Comida',1,NULL,686,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (689,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (690,'Fondo de emergencia',1,NULL,689,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (691,'Ahorro para retiro',1,NULL,689,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (692,'Inversiones (bolsa, cripto, etc.)',1,NULL,689,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (693,'Compra de activos (casa, coche, etc.)',1,NULL,689,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,11,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (694,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (695,'Salario',1,NULL,694,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (696,'Negocios / Freelance',1,NULL,694,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (697,'Inversiones',1,NULL,694,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (698,'Rentas / Alquileres',1,NULL,694,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (699,'Venta de bienes / servicios',1,NULL,694,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (700,'Regalos / Donaciones recibidas',1,NULL,694,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (701,'Reembolsos / Devoluciones',1,NULL,694,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (702,'Otros ingresos',1,NULL,694,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (703,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (704,'Hogar',1,NULL,703,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (705,'Alquiler',1,NULL,704,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (706,'Hipoteca',1,NULL,704,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (707,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,704,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (708,'Comunidad / Impuestos',1,NULL,704,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (709,'Seguros',1,NULL,704,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (710,'Decoración / Mantenimiento',1,NULL,704,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (711,'Compras comodidad hogar',1,NULL,704,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (712,'Alimentación',1,NULL,703,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (713,'Supermercado',1,NULL,712,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (714,'Comida en restaurantes',1,NULL,712,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (715,'Comida calle / rápida',1,NULL,712,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (716,'Café / Snacks',1,NULL,712,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (717,'Transporte',1,NULL,703,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (718,'Transporte público',1,NULL,717,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (719,'Combustible / Gasolina',1,NULL,717,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (720,'Mantenimiento / Taller',1,NULL,717,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (721,'Seguros vehiculares',1,NULL,717,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (722,'Impuestos vehiculares',1,NULL,717,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (723,'Salud y Bienestar',1,NULL,703,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (724,'Seguro médico',1,NULL,723,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (725,'Consultas / Salud médica',1,NULL,723,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (726,'Farmacia / Medicamentos',1,NULL,723,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (727,'Suplementos',1,NULL,723,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (728,'Gimnasio',1,NULL,723,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (729,'Bienestar personal (peluquería, spa, etc.)',1,NULL,723,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (730,'Educación',1,NULL,703,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (731,'Colegaturas',1,NULL,730,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (732,'Cursos / Talleres',1,NULL,730,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (733,'Libros y materiales',1,NULL,730,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (734,'Ocio y Entretenimiento',1,NULL,703,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (735,'Cine / Música / Streaming',1,NULL,734,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (736,'Viajes',1,NULL,734,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (737,'Eventos sociales',1,NULL,734,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (738,'Compras personales (ropa, gadgets, etc.)',1,NULL,734,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (739,'Donaciones',1,NULL,703,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (740,'Familia',1,NULL,739,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (741,'Benéficas',1,NULL,739,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (742,'Finanzas',1,NULL,703,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (743,'Tarjetas de crédito (intereses, comisiones)',1,NULL,742,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (744,'Impuestos financieros',1,NULL,742,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (745,'Comisiones bancarias',1,NULL,742,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (746,'Retirada de efectivo',1,NULL,742,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (747,'Traspasos entre cuentas',1,NULL,742,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (748,'Ajustes de cuenta',1,NULL,742,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (749,'Mascotas',1,NULL,703,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (750,'Veterinario',1,NULL,749,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (751,'Comida',1,NULL,749,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (752,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (753,'Fondo de emergencia',1,NULL,752,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (754,'Ahorro para retiro',1,NULL,752,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (755,'Inversiones (bolsa, cripto, etc.)',1,NULL,752,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (756,'Compra de activos (casa, coche, etc.)',1,NULL,752,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,12,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (757,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (758,'Salario',1,NULL,757,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (759,'Negocios / Freelance',1,NULL,757,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (760,'Inversiones',1,NULL,757,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (761,'Rentas / Alquileres',1,NULL,757,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (762,'Venta de bienes / servicios',1,NULL,757,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (763,'Regalos / Donaciones recibidas',1,NULL,757,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (764,'Reembolsos / Devoluciones',1,NULL,757,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (765,'Otros ingresos',1,NULL,757,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (766,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (767,'Hogar',1,NULL,766,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (768,'Alquiler',1,NULL,767,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (769,'Hipoteca',1,NULL,767,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (770,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,767,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (771,'Comunidad / Impuestos',1,NULL,767,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (772,'Seguros',1,NULL,767,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (773,'Decoración / Mantenimiento',1,NULL,767,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (774,'Compras comodidad hogar',1,NULL,767,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (775,'Alimentación',1,NULL,766,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (776,'Supermercado',1,NULL,775,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (777,'Comida en restaurantes',1,NULL,775,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (778,'Comida calle / rápida',1,NULL,775,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (779,'Café / Snacks',1,NULL,775,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (780,'Transporte',1,NULL,766,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (781,'Transporte público',1,NULL,780,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (782,'Combustible / Gasolina',1,NULL,780,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (783,'Mantenimiento / Taller',1,NULL,780,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (784,'Seguros vehiculares',1,NULL,780,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (785,'Impuestos vehiculares',1,NULL,780,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (786,'Salud y Bienestar',1,NULL,766,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (787,'Seguro médico',1,NULL,786,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (788,'Consultas / Salud médica',1,NULL,786,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (789,'Farmacia / Medicamentos',1,NULL,786,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (790,'Suplementos',1,NULL,786,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (791,'Gimnasio',1,NULL,786,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (792,'Bienestar personal (peluquería, spa, etc.)',1,NULL,786,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (793,'Educación',1,NULL,766,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (794,'Colegaturas',1,NULL,793,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (795,'Cursos / Talleres',1,NULL,793,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (796,'Libros y materiales',1,NULL,793,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (797,'Ocio y Entretenimiento',1,NULL,766,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (798,'Cine / Música / Streaming',1,NULL,797,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (799,'Viajes',1,NULL,797,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (800,'Eventos sociales',1,NULL,797,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (801,'Compras personales (ropa, gadgets, etc.)',1,NULL,797,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (802,'Donaciones',1,NULL,766,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (803,'Familia',1,NULL,802,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (804,'Benéficas',1,NULL,802,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (805,'Finanzas',1,NULL,766,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (806,'Tarjetas de crédito (intereses, comisiones)',1,NULL,805,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (807,'Impuestos financieros',1,NULL,805,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (808,'Comisiones bancarias',1,NULL,805,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (809,'Retirada de efectivo',1,NULL,805,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (810,'Traspasos entre cuentas',1,NULL,805,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (811,'Ajustes de cuenta',1,NULL,805,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (812,'Mascotas',1,NULL,766,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (813,'Veterinario',1,NULL,812,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (814,'Comida',1,NULL,812,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (815,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (816,'Fondo de emergencia',1,NULL,815,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (817,'Ahorro para retiro',1,NULL,815,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (818,'Inversiones (bolsa, cripto, etc.)',1,NULL,815,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (819,'Compra de activos (casa, coche, etc.)',1,NULL,815,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,13,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (820,'Ingresos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'trending_up',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (821,'Salario',1,NULL,820,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'work',NULL,1,'category',0);
INSERT INTO "categories" VALUES (822,'Negocios / Freelance',1,NULL,820,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'business_center',NULL,1,'category',1);
INSERT INTO "categories" VALUES (823,'Inversiones',1,NULL,820,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'show_chart',NULL,1,'category',2);
INSERT INTO "categories" VALUES (824,'Rentas / Alquileres',1,NULL,820,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'home',NULL,1,'category',3);
INSERT INTO "categories" VALUES (825,'Venta de bienes / servicios',1,NULL,820,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'sell',NULL,1,'category',4);
INSERT INTO "categories" VALUES (826,'Regalos / Donaciones recibidas',1,NULL,820,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'card_giftcard',NULL,1,'category',5);
INSERT INTO "categories" VALUES (827,'Reembolsos / Devoluciones',1,NULL,820,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'undo',NULL,1,'category',6);
INSERT INTO "categories" VALUES (828,'Otros ingresos',1,NULL,820,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'payments',NULL,1,'category',7);
INSERT INTO "categories" VALUES (829,'Gastos',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'trending_down',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (830,'Hogar',1,NULL,829,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'home',NULL,1,'folder',0);
INSERT INTO "categories" VALUES (831,'Alquiler',1,NULL,830,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'home',NULL,1,'category',0);
INSERT INTO "categories" VALUES (832,'Hipoteca',1,NULL,830,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'account_balance',NULL,1,'category',1);
INSERT INTO "categories" VALUES (833,'Servicios (Luz, Agua, Gas, Internet, Teléfono)',1,NULL,830,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'bolt',NULL,1,'category',2);
INSERT INTO "categories" VALUES (834,'Comunidad / Impuestos',1,NULL,830,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'gavel',NULL,1,'category',3);
INSERT INTO "categories" VALUES (835,'Seguros',1,NULL,830,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'policy',NULL,1,'category',4);
INSERT INTO "categories" VALUES (836,'Decoración / Mantenimiento',1,NULL,830,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'handyman',NULL,1,'category',5);
INSERT INTO "categories" VALUES (837,'Compras comodidad hogar',1,NULL,830,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'shopping_cart',NULL,1,'category',6);
INSERT INTO "categories" VALUES (838,'Alimentación',1,NULL,829,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'restaurant',NULL,1,'folder',1);
INSERT INTO "categories" VALUES (839,'Supermercado',1,NULL,838,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'local_grocery_store',NULL,1,'category',0);
INSERT INTO "categories" VALUES (840,'Comida en restaurantes',1,NULL,838,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (841,'Comida calle / rápida',1,NULL,838,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'fastfood',NULL,1,'category',2);
INSERT INTO "categories" VALUES (842,'Café / Snacks',1,NULL,838,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'local_cafe',NULL,1,'category',3);
INSERT INTO "categories" VALUES (843,'Transporte',1,NULL,829,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'directions_car',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (844,'Transporte público',1,NULL,843,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'directions_bus',NULL,1,'category',0);
INSERT INTO "categories" VALUES (845,'Combustible / Gasolina',1,NULL,843,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'local_gas_station',NULL,1,'category',1);
INSERT INTO "categories" VALUES (846,'Mantenimiento / Taller',1,NULL,843,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'build',NULL,1,'category',2);
INSERT INTO "categories" VALUES (847,'Seguros vehiculares',1,NULL,843,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'policy',NULL,1,'category',3);
INSERT INTO "categories" VALUES (848,'Impuestos vehiculares',1,NULL,843,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'receipt_long',NULL,1,'category',4);
INSERT INTO "categories" VALUES (849,'Salud y Bienestar',1,NULL,829,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'health_and_safety',NULL,1,'folder',3);
INSERT INTO "categories" VALUES (850,'Seguro médico',1,NULL,849,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'health_and_safety',NULL,1,'category',0);
INSERT INTO "categories" VALUES (851,'Consultas / Salud médica',1,NULL,849,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'medical_services',NULL,1,'category',1);
INSERT INTO "categories" VALUES (852,'Farmacia / Medicamentos',1,NULL,849,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'local_pharmacy',NULL,1,'category',2);
INSERT INTO "categories" VALUES (853,'Suplementos',1,NULL,849,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'medication',NULL,1,'category',3);
INSERT INTO "categories" VALUES (854,'Gimnasio',1,NULL,849,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'fitness_center',NULL,1,'category',4);
INSERT INTO "categories" VALUES (855,'Bienestar personal (peluquería, spa, etc.)',1,NULL,849,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'spa',NULL,1,'category',5);
INSERT INTO "categories" VALUES (856,'Educación',1,NULL,829,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'school',NULL,1,'folder',4);
INSERT INTO "categories" VALUES (857,'Colegaturas',1,NULL,856,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'school',NULL,1,'category',0);
INSERT INTO "categories" VALUES (858,'Cursos / Talleres',1,NULL,856,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'menu_book',NULL,1,'category',1);
INSERT INTO "categories" VALUES (859,'Libros y materiales',1,NULL,856,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'book',NULL,1,'category',2);
INSERT INTO "categories" VALUES (860,'Ocio y Entretenimiento',1,NULL,829,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'theaters',NULL,1,'folder',5);
INSERT INTO "categories" VALUES (861,'Cine / Música / Streaming',1,NULL,860,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'subscriptions',NULL,1,'category',0);
INSERT INTO "categories" VALUES (862,'Viajes',1,NULL,860,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'flight',NULL,1,'category',1);
INSERT INTO "categories" VALUES (863,'Eventos sociales',1,NULL,860,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'event',NULL,1,'category',2);
INSERT INTO "categories" VALUES (864,'Compras personales (ropa, gadgets, etc.)',1,NULL,860,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'shopping_basket',NULL,1,'category',3);
INSERT INTO "categories" VALUES (865,'Donaciones',1,NULL,829,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'favorite',NULL,1,'folder',6);
INSERT INTO "categories" VALUES (866,'Familia',1,NULL,865,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'family_restroom',NULL,1,'category',0);
INSERT INTO "categories" VALUES (867,'Benéficas',1,NULL,865,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'volunteer_activism',NULL,1,'category',1);
INSERT INTO "categories" VALUES (868,'Finanzas',1,NULL,829,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'account_balance_wallet',NULL,1,'folder',7);
INSERT INTO "categories" VALUES (869,'Tarjetas de crédito (intereses, comisiones)',1,NULL,868,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'credit_card',NULL,1,'category',0);
INSERT INTO "categories" VALUES (870,'Impuestos financieros',1,NULL,868,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'request_quote',NULL,1,'category',1);
INSERT INTO "categories" VALUES (871,'Comisiones bancarias',1,NULL,868,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'account_balance',NULL,1,'category',2);
INSERT INTO "categories" VALUES (872,'Retirada de efectivo',1,NULL,868,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'atm',NULL,1,'category',3);
INSERT INTO "categories" VALUES (873,'Traspasos entre cuentas',1,NULL,868,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'swap_horiz',NULL,0,'category',4);
INSERT INTO "categories" VALUES (874,'Ajustes de cuenta',1,NULL,868,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'tune',NULL,0,'category',5);
INSERT INTO "categories" VALUES (875,'Mascotas',1,NULL,829,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'pets',NULL,1,'folder',8);
INSERT INTO "categories" VALUES (876,'Veterinario',1,NULL,875,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'pets',NULL,1,'category',0);
INSERT INTO "categories" VALUES (877,'Comida',1,NULL,875,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'restaurant',NULL,1,'category',1);
INSERT INTO "categories" VALUES (878,'Ahorro / Inversión',1,NULL,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'savings',NULL,1,'folder',2);
INSERT INTO "categories" VALUES (879,'Fondo de emergencia',1,NULL,878,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'savings',NULL,1,'category',0);
INSERT INTO "categories" VALUES (880,'Ahorro para retiro',1,NULL,878,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'savings',NULL,1,'category',1);
INSERT INTO "categories" VALUES (881,'Inversiones (bolsa, cripto, etc.)',1,NULL,878,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'trending_up',NULL,1,'category',2);
INSERT INTO "categories" VALUES (882,'Compra de activos (casa, coche, etc.)',1,NULL,878,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL,14,'home',NULL,1,'category',3);
INSERT INTO "category_templates" VALUES (1,'Ingresos','ingresos',NULL,1,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "category_templates" VALUES (2,'Gastos','gastos',NULL,2,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "category_templates" VALUES (3,'Alquiler','alquiler','gastos',3,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "category_templates" VALUES (4,'Comida','comida','gastos',4,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "category_templates" VALUES (5,'Educación','educacion','gastos',5,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "category_templates" VALUES (6,'Ocio','ocio','gastos',6,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "currencies" VALUES (1,'Dólar Estadounidense','$','left','USD',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (2,'Euro','€','left','EUR',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (3,'Peso Chileno','$','left','CLP',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (4,'Peso Mexicano','MX$','left','MXN',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (5,'Bolívar Venezolano','Bs.','left','VES',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (6,'Libra Esterlina','£','left','GBP',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (7,'Yen Japonés','¥','left','JPY',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (8,'Yuan Chino','¥','left','CNY',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (9,'Real Brasileño','R$','left','BRL',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (10,'Dólar Canadiense','C$','left','CAD',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (11,'Tether','$T','left','USDT',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (12,'USD Coin','$C','left','USDC',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (13,'Bitcoin','₿','left','BTC',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (14,'Ethereum','Ξ','left','ETH',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "currencies" VALUES (15,'BNB','BNB','left','BNB',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "item_categories" VALUES (1,'Alimentación',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_categories" VALUES (2,'Supermercado',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',1);
INSERT INTO "item_categories" VALUES (3,'Arroz',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (4,'Pasta',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (5,'Pan',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (6,'Aceite',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (7,'Azúcar',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (8,'Leche',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (9,'Huevos',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (10,'Verduras',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (11,'Frutas',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (12,'Carne de res',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (13,'Pollo',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (14,'Pescado',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',2);
INSERT INTO "item_categories" VALUES (15,'Restaurantes',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',1);
INSERT INTO "item_categories" VALUES (16,'Almuerzo ejecutivo',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',15);
INSERT INTO "item_categories" VALUES (17,'Cena en restaurante',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',15);
INSERT INTO "item_categories" VALUES (18,'Comida rápida (hamburguesa, pizza)',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',15);
INSERT INTO "item_categories" VALUES (19,'Bebidas',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',15);
INSERT INTO "item_categories" VALUES (20,'Café / Snacks',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',1);
INSERT INTO "item_categories" VALUES (21,'Café',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',20);
INSERT INTO "item_categories" VALUES (22,'Té',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',20);
INSERT INTO "item_categories" VALUES (23,'Galletas',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',20);
INSERT INTO "item_categories" VALUES (24,'Pasteles',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',20);
INSERT INTO "item_categories" VALUES (25,'Chocolates',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',20);
INSERT INTO "item_categories" VALUES (26,'Hogar',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_categories" VALUES (27,'Alquiler / Hipoteca',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',26);
INSERT INTO "item_categories" VALUES (28,'Pago mensual de vivienda',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',27);
INSERT INTO "item_categories" VALUES (29,'Servicios',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',26);
INSERT INTO "item_categories" VALUES (30,'Electricidad',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',29);
INSERT INTO "item_categories" VALUES (31,'Agua potable',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',29);
INSERT INTO "item_categories" VALUES (32,'Gas doméstico',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',29);
INSERT INTO "item_categories" VALUES (33,'Internet',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',29);
INSERT INTO "item_categories" VALUES (34,'Teléfono',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',29);
INSERT INTO "item_categories" VALUES (35,'TV por cable / streaming',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',29);
INSERT INTO "item_categories" VALUES (36,'Mantenimiento',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',26);
INSERT INTO "item_categories" VALUES (37,'Reparación de plomería',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',36);
INSERT INTO "item_categories" VALUES (38,'Reparación eléctrica',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',36);
INSERT INTO "item_categories" VALUES (39,'Pintura',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',36);
INSERT INTO "item_categories" VALUES (40,'Cerrajería',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',36);
INSERT INTO "item_categories" VALUES (41,'Compras hogar',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',26);
INSERT INTO "item_categories" VALUES (42,'Muebles',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',41);
INSERT INTO "item_categories" VALUES (43,'Electrodomésticos',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',41);
INSERT INTO "item_categories" VALUES (44,'Ropa de cama',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',41);
INSERT INTO "item_categories" VALUES (45,'Decoración',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',41);
INSERT INTO "item_categories" VALUES (46,'Utensilios de cocina',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',41);
INSERT INTO "item_categories" VALUES (47,'Transporte',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_categories" VALUES (48,'Transporte público',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',47);
INSERT INTO "item_categories" VALUES (49,'Pasaje bus',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',48);
INSERT INTO "item_categories" VALUES (50,'Metro',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',48);
INSERT INTO "item_categories" VALUES (51,'Taxi / Uber',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',48);
INSERT INTO "item_categories" VALUES (52,'Vehículo propio',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',47);
INSERT INTO "item_categories" VALUES (53,'Combustible',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',52);
INSERT INTO "item_categories" VALUES (54,'Cambio de aceite',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',52);
INSERT INTO "item_categories" VALUES (55,'Neumáticos',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',52);
INSERT INTO "item_categories" VALUES (56,'Batería',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',52);
INSERT INTO "item_categories" VALUES (57,'Seguro anual',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',52);
INSERT INTO "item_categories" VALUES (58,'Impuesto circulación',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',52);
INSERT INTO "item_categories" VALUES (59,'Salud y Bienestar',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_categories" VALUES (60,'Farmacia',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',59);
INSERT INTO "item_categories" VALUES (61,'Analgésicos',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',60);
INSERT INTO "item_categories" VALUES (62,'Vitaminas',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',60);
INSERT INTO "item_categories" VALUES (63,'Antibióticos',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',60);
INSERT INTO "item_categories" VALUES (64,'Productos de higiene personal',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',60);
INSERT INTO "item_categories" VALUES (65,'Consultas médicas',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',59);
INSERT INTO "item_categories" VALUES (66,'General',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',65);
INSERT INTO "item_categories" VALUES (67,'Odontología',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',65);
INSERT INTO "item_categories" VALUES (68,'Oftalmología',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',65);
INSERT INTO "item_categories" VALUES (69,'Pediatría',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',65);
INSERT INTO "item_categories" VALUES (70,'Gimnasio',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',59);
INSERT INTO "item_categories" VALUES (71,'Membresía mensual',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',70);
INSERT INTO "item_categories" VALUES (72,'Clases grupales',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',70);
INSERT INTO "item_categories" VALUES (73,'Entrenador personal',1,'2025-10-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',70);
INSERT INTO "item_taxes" VALUES (1,1,2,16.33,3,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (2,2,2,0.67,3,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (3,3,1,34.68,16,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (4,4,3,0,0,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (5,5,3,0,0,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (6,6,4,1.22,0.3,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (7,7,4,1.92,0.3,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (8,8,4,2.81,0.3,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (9,9,2,12.52,3,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (10,10,3,0,0,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (11,11,1,43.43,16,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (12,12,5,0,0,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (13,13,1,36.74,16,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (14,14,4,1.58,0.3,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (15,15,3,0,0,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (16,16,5,0,0,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (17,17,4,2.63,0.3,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (18,18,1,26.36,16,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (19,19,5,0,0,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_taxes" VALUES (20,20,1,116.06,16,1,NULL,'2025-10-06','2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "item_transactions" VALUES (1,21,'quia',544.48,2,4,'Omnis quos id vero vero fuga et suscipit.',NULL,1,NULL,'2025-02-20 19:14:30',NULL,NULL,'quis','2025-10-06 15:01:51','2025-10-06 15:01:51',1,1,NULL);
INSERT INTO "item_transactions" VALUES (2,22,'non',22.25,2,9,'Molestiae consequatur voluptatem non non.',NULL,1,NULL,'2025-09-19 02:35:23',NULL,NULL,'soluta','2025-10-06 15:01:51','2025-10-06 15:01:51',2,3,NULL);
INSERT INTO "item_transactions" VALUES (3,23,'sit',216.75,1,10,'Voluptatem esse quo reiciendis voluptates.',NULL,1,NULL,'2025-09-20 23:36:45',NULL,NULL,'vero','2025-10-06 15:01:51','2025-10-06 15:01:51',3,8,NULL);
INSERT INTO "item_transactions" VALUES (4,24,'quasi',142.5,3,9,'Quibusdam et ut quibusdam saepe aliquid dolores excepturi.',NULL,0,NULL,'2025-09-20 23:23:04',NULL,NULL,'possimus','2025-10-06 15:01:51','2025-10-06 15:01:51',4,5,NULL);
INSERT INTO "item_transactions" VALUES (5,25,'omnis',296.27,3,7,'Animi nemo laborum praesentium quo ducimus.',NULL,0,NULL,'2025-06-24 13:39:22',NULL,NULL,'totam','2025-10-06 15:01:51','2025-10-06 15:01:51',5,2,NULL);
INSERT INTO "item_transactions" VALUES (6,26,'atque',405.88,4,3,'Doloribus consequatur nam quibusdam sit et velit hic.',NULL,1,NULL,'2025-09-11 13:19:54',NULL,NULL,'in','2025-10-06 15:01:51','2025-10-06 15:01:51',6,1,NULL);
INSERT INTO "item_transactions" VALUES (7,27,'expedita',639.48,4,5,'Corporis porro quia rerum sit et.',NULL,0,NULL,'2025-06-03 23:04:57',NULL,NULL,'iure','2025-10-06 15:01:51','2025-10-06 15:01:51',7,7,NULL);
INSERT INTO "item_transactions" VALUES (8,28,'doloribus',937.77,4,10,'Sit aspernatur modi eum voluptates id non.',NULL,0,NULL,'2025-02-11 15:56:52',NULL,NULL,'qui','2025-10-06 15:01:51','2025-10-06 15:01:51',8,1,NULL);
INSERT INTO "item_transactions" VALUES (9,29,'nesciunt',417.33,2,1,'Voluptatem ut quasi pariatur a asperiores qui.',NULL,1,NULL,'2025-03-05 15:40:52',NULL,NULL,'dolore','2025-10-06 15:01:51','2025-10-06 15:01:51',9,7,NULL);
INSERT INTO "item_transactions" VALUES (10,30,'ea',7.3,3,9,'Explicabo non voluptatem cupiditate repellendus dicta molestias.',NULL,1,NULL,'2025-06-25 17:05:53',NULL,NULL,'autem','2025-10-06 15:01:51','2025-10-06 15:01:51',10,3,NULL);
INSERT INTO "item_transactions" VALUES (11,31,'reiciendis',271.44,1,6,'Eligendi consectetur earum laborum delectus.',NULL,1,NULL,'2025-04-27 05:02:38',NULL,NULL,'dolores','2025-10-06 15:01:51','2025-10-06 15:01:51',11,4,NULL);
INSERT INTO "item_transactions" VALUES (12,32,'nihil',263.37,5,2,'Ut laborum deleniti unde modi quas sapiente.',NULL,0,NULL,'2025-09-21 08:26:04',NULL,NULL,'sed','2025-10-06 15:01:51','2025-10-06 15:01:51',12,8,NULL);
INSERT INTO "item_transactions" VALUES (13,33,'voluptatem',229.6,1,3,'Illum eum consequatur pariatur est.',NULL,1,NULL,'2025-06-16 21:44:29',NULL,NULL,'occaecati','2025-10-06 15:01:51','2025-10-06 15:01:51',13,1,NULL);
INSERT INTO "item_transactions" VALUES (14,34,'sunt',526.5,4,7,'Minima provident porro blanditiis sit sapiente.',NULL,1,NULL,'2025-07-15 00:08:05',NULL,NULL,'magni','2025-10-06 15:01:51','2025-10-06 15:01:51',14,2,NULL);
INSERT INTO "item_transactions" VALUES (15,35,'explicabo',341.45,3,5,'Rerum perferendis reprehenderit adipisci animi maxime sed.',NULL,0,NULL,'2025-02-16 02:52:26',NULL,NULL,'veniam','2025-10-06 15:01:51','2025-10-06 15:01:51',15,6,NULL);
INSERT INTO "item_transactions" VALUES (16,36,'repellat',67.59,5,7,'Ut beatae laboriosam tempora quo.',NULL,1,NULL,'2025-01-09 03:50:51',NULL,NULL,'dolorem','2025-10-06 15:01:51','2025-10-06 15:01:51',16,2,NULL);
INSERT INTO "item_transactions" VALUES (17,37,'aut',875.21,4,1,'Mollitia eligendi omnis ipsum reiciendis est aut impedit.',NULL,1,NULL,'2025-02-08 12:56:30',NULL,NULL,'eaque','2025-10-06 15:01:51','2025-10-06 15:01:51',17,8,NULL);
INSERT INTO "item_transactions" VALUES (18,38,'molestias',164.73,1,6,'Libero qui est maiores ratione.',NULL,1,NULL,'2025-09-23 04:56:14',NULL,NULL,'est','2025-10-06 15:01:51','2025-10-06 15:01:51',18,5,NULL);
INSERT INTO "item_transactions" VALUES (19,39,'animi',626.47,5,4,'Ex quos ut molestiae voluptatem odio enim.',NULL,1,NULL,'2025-05-11 13:36:47',NULL,NULL,'deserunt','2025-10-06 15:01:51','2025-10-06 15:01:51',19,6,NULL);
INSERT INTO "item_transactions" VALUES (20,40,'tenetur',725.39,1,1,'Quia eum quia dolores.',NULL,1,NULL,'2025-03-08 04:46:39',NULL,NULL,'natus','2025-10-06 15:01:51','2025-10-06 15:01:51',20,9,NULL);
INSERT INTO "item_transactions" VALUES (21,41,'Agrego 100',100,NULL,NULL,NULL,NULL,1,'2025-10-06 15:05:23','2025-10-06 11:03:00',NULL,4,NULL,'2025-10-06 15:03:40','2025-10-06 15:05:23',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (22,41,'Agrego 100',100,NULL,NULL,NULL,NULL,1,'2025-10-06 15:05:44','2025-10-06 11:03:00',NULL,4,NULL,'2025-10-06 15:05:23','2025-10-06 15:05:44',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (23,41,'Agrego 100',100,NULL,NULL,NULL,NULL,1,NULL,'2025-10-06 11:03:00',NULL,4,NULL,'2025-10-06 15:05:44','2025-10-06 15:05:44',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (24,42,'Reste deberia queda 238',200,NULL,NULL,NULL,NULL,1,NULL,'2025-10-06 11:05:00',NULL,4,NULL,'2025-10-06 15:06:01','2025-10-06 15:06:01',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (25,43,'resto deberia dar 250',-12,NULL,NULL,NULL,NULL,1,NULL,'2025-10-06 11:06:00',NULL,4,NULL,'2025-10-06 15:06:21','2025-10-06 15:06:21',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (26,45,'Panes en casa de karo',2.8738840976393,NULL,NULL,NULL,NULL,1,NULL,'2025-11-09 20:53:00',NULL,4,NULL,'2025-11-10 00:58:49','2025-11-10 00:58:49',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (27,46,'Cafe farmatodo',-0.7236,NULL,NULL,NULL,NULL,1,NULL,'2025-11-10 08:41:00',NULL,4,NULL,'2025-11-10 12:43:08','2025-11-10 12:43:08',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (28,47,'Pago 100 prueba',0.3921568627451,NULL,NULL,NULL,NULL,1,NULL,'2025-11-11 17:16:00',NULL,4,NULL,'2025-11-11 21:17:41','2025-11-11 21:17:41',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (29,48,'123213',0.38461538461538,NULL,NULL,NULL,NULL,1,NULL,'2025-11-11 18:27:00',NULL,4,NULL,'2025-11-11 22:32:15','2025-11-11 22:32:15',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (30,49,'prueba 20',0.08,NULL,NULL,NULL,NULL,1,NULL,'2025-11-11 23:49:00',NULL,4,NULL,'2025-11-12 03:52:23','2025-11-12 03:52:23',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (31,50,'prueba 10',0.04,NULL,NULL,NULL,NULL,1,NULL,'2025-11-11 23:56:00',NULL,4,NULL,'2025-11-12 03:58:00','2025-11-12 03:58:00',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (32,51,'pgo 20',0.08,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 06:44:00',NULL,4,NULL,'2025-11-12 10:45:53','2025-11-12 10:45:53',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (33,52,'pago 260',0.0038461538461538,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 06:45:00',NULL,4,NULL,'2025-11-12 10:46:36','2025-11-12 10:46:36',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (34,53,'Aumento 10 tasa 22 monto',0.084615384615385,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 09:37:00',NULL,4,NULL,'2025-11-12 13:37:46','2025-11-12 13:37:46',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (35,54,'concepto prueb',8.888,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 09:37:00',NULL,4,NULL,'2025-11-12 14:10:46','2025-11-12 14:10:46',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (36,55,'tsto',8.9346153846154,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 10:11:00',NULL,4,NULL,'2025-11-12 14:11:20','2025-11-12 14:11:20',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (37,56,'etrsd',0.084615384615385,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 10:11:00',NULL,4,NULL,'2025-11-12 14:17:09','2025-11-12 14:17:09',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (38,57,'sdsd',0.82222222222222,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 10:17:00',NULL,4,NULL,'2025-11-12 14:17:27','2025-11-12 14:17:27',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (39,58,'prueba 280',0.79285714285714,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 10:17:00',NULL,4,NULL,'2025-11-12 14:18:00','2025-11-12 14:18:00',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (40,59,'PAgo 250',8.932,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 10:18:00',NULL,4,NULL,'2025-11-12 14:18:51','2025-11-12 14:18:51',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (41,60,'PAGO 300',10,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 14:34:00',NULL,4,NULL,'2025-11-12 18:35:16','2025-11-12 18:35:16',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (42,61,'concepto con rate',1,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 14:49:00',NULL,4,NULL,'2025-11-12 18:49:30','2025-11-12 18:49:30',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (43,62,'222',0.099099099099099,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 14:53:00',NULL,4,NULL,'2025-11-12 19:10:06','2025-11-12 19:10:06',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (44,63,'sdsds',1,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 15:25:00',NULL,4,NULL,'2025-11-12 19:25:24','2025-11-12 19:25:24',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (45,64,'2323',6.6726726726727,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 15:33:00',NULL,4,NULL,'2025-11-12 19:33:53','2025-11-12 19:33:53',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (46,65,'sdsd',960.01351351351,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 15:33:00',NULL,4,NULL,'2025-11-12 19:51:08','2025-11-12 19:51:08',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (47,66,'2312312',1,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 16:50:00',NULL,4,NULL,'2025-11-12 20:51:28','2025-11-12 20:51:28',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (48,67,'3123213',0.43529411764706,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 16:52:00',NULL,4,NULL,'2025-11-12 20:52:33','2025-11-12 20:52:33',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (49,68,'sdadsad',100.18699186992,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 20:47:00',NULL,4,NULL,'2025-11-13 00:48:24','2025-11-13 00:48:24',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (50,69,'213123',1,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 20:48:00',NULL,4,NULL,'2025-11-13 00:48:54','2025-11-13 00:48:54',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (51,70,'tasa 350',1,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 20:48:00',NULL,4,NULL,'2025-11-13 01:17:46','2025-11-13 01:17:46',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (52,71,'pago 400',532.8075,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 21:17:00',NULL,4,NULL,'2025-11-13 01:18:28','2025-11-13 01:18:28',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (53,72,'330 pago',1,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 21:34:00',NULL,4,NULL,'2025-11-13 01:35:09','2025-11-13 01:35:09',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (54,73,'1312312',1,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 21:35:00',NULL,4,NULL,'2025-11-13 01:43:29','2025-11-13 01:43:29',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (55,74,'123123123',0.060869565217391,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 21:43:00',NULL,4,NULL,'2025-11-13 02:01:48','2025-11-13 02:01:48',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (56,75,'Pago',1,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 22:01:00',NULL,4,NULL,'2025-11-13 02:11:56','2025-11-13 02:11:56',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (57,76,'21323',0.65294117647059,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 22:11:00',NULL,4,NULL,'2025-11-13 02:12:27','2025-11-13 02:12:27',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (58,77,'330 tasa',0.67272727272727,NULL,NULL,NULL,NULL,1,'2025-11-13 02:55:27','2025-11-12 22:53:00',NULL,4,NULL,'2025-11-13 02:54:57','2025-11-13 02:55:27',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (59,77,'330 tasa',0.67272727272727,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 22:53:00',NULL,4,NULL,'2025-11-13 02:55:27','2025-11-13 02:55:27',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (60,78,'tasa 335',1,NULL,NULL,NULL,NULL,1,'2025-11-13 05:39:50','2025-11-12 22:56:00',NULL,4,NULL,'2025-11-13 02:57:15','2025-11-13 05:39:50',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (61,79,'pago 222 id 12',1,NULL,NULL,NULL,NULL,1,'2025-11-13 02:59:25','2025-11-12 22:57:00',NULL,4,NULL,'2025-11-13 02:58:10','2025-11-13 02:59:25',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (62,79,'pago 222 id 12',1.67,NULL,NULL,NULL,NULL,1,'2025-11-13 05:57:22','2025-11-12 22:57:00',NULL,4,NULL,'2025-11-13 02:59:25','2025-11-13 05:57:22',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (63,78,'tasa 335',0.60909090909091,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 22:56:00',NULL,4,NULL,'2025-11-13 05:39:50','2025-11-13 05:39:50',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (64,80,'tasa 220',-1,NULL,NULL,NULL,NULL,1,NULL,'2025-11-13 01:55:00',NULL,4,NULL,'2025-11-13 05:55:43','2025-11-13 05:55:43',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (65,79,'pago 222 id 12',-1.67,NULL,NULL,NULL,NULL,1,NULL,'2025-11-12 22:57:00',NULL,4,NULL,'2025-11-13 05:57:22','2025-11-13 05:57:22',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (66,86,'PAGO',0.21346153846154,NULL,NULL,NULL,NULL,1,NULL,'2025-11-16 04:22:00',NULL,4,NULL,'2025-11-16 08:22:49','2025-11-16 08:22:49',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (67,87,'pago',0.4188679245283,NULL,NULL,NULL,NULL,1,NULL,'2025-11-16 04:23:00',NULL,4,NULL,'2025-11-16 08:23:20','2025-11-16 08:23:20',NULL,1,NULL);
INSERT INTO "item_transactions" VALUES (68,88,'bajo a 530',6.2301886792453,NULL,NULL,NULL,NULL,1,NULL,'2025-11-16 04:23:00',NULL,4,NULL,'2025-11-16 08:23:41','2025-11-16 08:23:41',NULL,1,NULL);
INSERT INTO "items" VALUES (1,'eaque',234.16,1,1,'1971-12-15',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','modi',31);
INSERT INTO "items" VALUES (2,'magnam',874.73,1,1,'1984-12-30',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','eaque',16);
INSERT INTO "items" VALUES (3,'tempore',746.83,4,1,'1995-12-14',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','odit',34);
INSERT INTO "items" VALUES (4,'mollitia',497.82,1,1,'1975-08-24',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','nisi',48);
INSERT INTO "items" VALUES (5,'doloremque',821.76,3,1,'2007-01-21',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','officia',66);
INSERT INTO "items" VALUES (6,'commodi',393.14,1,1,'1988-10-04',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','ullam',18);
INSERT INTO "items" VALUES (7,'voluptas',264.7,5,1,'2012-12-02',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','consequuntur',46);
INSERT INTO "items" VALUES (8,'tempora',802.99,3,1,'2008-03-19',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','rerum',31);
INSERT INTO "items" VALUES (9,'corporis',725.23,2,1,'2013-07-23',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','aut',40);
INSERT INTO "items" VALUES (10,'laborum',36.25,3,1,'1973-11-02',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','aut',20);
INSERT INTO "items" VALUES (11,'sed',536.89,2,1,'2019-02-03',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','fugit',3);
INSERT INTO "items" VALUES (12,'aliquid',568.1,4,1,'2002-09-07',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','veritatis',21);
INSERT INTO "items" VALUES (13,'dolore',56.31,1,1,'1981-10-03',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','pariatur',29);
INSERT INTO "items" VALUES (14,'quia',739.02,2,1,'2015-06-30',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','voluptatem',34);
INSERT INTO "items" VALUES (15,'deleniti',80.41,2,1,'1984-01-14',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','nemo',42);
INSERT INTO "items" VALUES (16,'est',590.42,2,1,'1981-09-07',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','accusamus',36);
INSERT INTO "items" VALUES (17,'reprehenderit',18.31,5,1,'2017-01-06',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','dolorem',38);
INSERT INTO "items" VALUES (18,'enim',123,1,1,'1979-01-16',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','blanditiis',72);
INSERT INTO "items" VALUES (19,'fugit',997.57,3,1,'2012-08-29',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','aut',22);
INSERT INTO "items" VALUES (20,'asperiores',694.92,1,1,'1991-11-22',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','eum',44);
INSERT INTO "items" VALUES (21,'a',69.66,3,1,'1970-01-02',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','eaque',60);
INSERT INTO "items" VALUES (22,'accusamus',344.26,1,1,'1991-04-05',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','quae',28);
INSERT INTO "items" VALUES (23,'ab',779.18,5,1,'2016-06-15',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','et',15);
INSERT INTO "items" VALUES (24,'consectetur',332.85,2,1,'1978-03-04',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','tempora',14);
INSERT INTO "items" VALUES (25,'non',671.62,3,1,'2020-12-05',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','sunt',69);
INSERT INTO "items" VALUES (26,'quaerat',935.86,3,1,'1998-06-10',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','architecto',26);
INSERT INTO "items" VALUES (27,'est',741.52,4,1,'1974-05-09',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','dolorum',18);
INSERT INTO "items" VALUES (28,'aut',734.98,3,1,'2018-12-13',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','omnis',49);
INSERT INTO "items" VALUES (29,'harum',792.22,1,1,'1990-06-02',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','quos',73);
INSERT INTO "items" VALUES (30,'magnam',914.92,2,1,'1999-05-10',NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51','impedit',32);
INSERT INTO "jar_template_jar_categories" VALUES (1,1,1,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (2,2,2,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (3,3,3,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (4,7,1,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (5,8,2,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (6,9,3,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (7,15,1,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (8,16,2,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (9,17,3,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (10,21,1,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (11,22,2,NULL,NULL);
INSERT INTO "jar_template_jar_categories" VALUES (12,23,3,NULL,NULL);
INSERT INTO "jar_template_jars" VALUES (1,1,'Necesidades básicas','percent',55,NULL,'all_income','#6B7280',1,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (2,1,'Diversión','percent',10,NULL,'all_income','#F59E0B',2,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (3,1,'Ahorro','percent',10,NULL,'all_income','#10B981',3,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (4,1,'Educación','percent',10,NULL,'all_income','#3B82F6',4,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (5,1,'Reservas','percent',10,NULL,'all_income','#8B5CF6',5,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (6,1,'Caridad y regalos','percent',5,NULL,'all_income','#EF4444',6,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (7,2,'Necesidades básicas','percent',50,NULL,'all_income','#6B7280',1,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (8,2,'Salud','percent',10,NULL,'all_income','#EF4444',2,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (9,2,'Educación','percent',10,NULL,'all_income','#3B82F6',3,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (10,2,'Empresa','percent',10,NULL,'all_income','#8B5CF6',4,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (11,2,'Coche / Auto y transporte','percent',5,NULL,'all_income','#F59E0B',5,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (12,2,'Hogar cómodo','percent',5,NULL,'all_income','#10B981',6,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (13,2,'Ocio / Diversión','percent',5,NULL,'all_income','#FCD34D',7,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (14,2,'Viajes','percent',5,NULL,'all_income','#60A5FA',8,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (15,3,'Necesidades básicas','percent',60,NULL,'all_income','#6B7280',1,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (16,3,'Ahorro','percent',15,NULL,'all_income','#10B981',2,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (17,3,'Reservas','percent',10,NULL,'all_income','#8B5CF6',3,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (18,3,'Educación','percent',5,NULL,'all_income','#3B82F6',4,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (19,3,'Diversión','percent',5,NULL,'all_income','#F59E0B',5,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (20,3,'Caridad y regalos','percent',5,NULL,'all_income','#EF4444',6,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (21,4,'Necesidades básicas','percent',40,NULL,'all_income','#6B7280',1,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (22,4,'Diversión','percent',20,NULL,'all_income','#F59E0B',2,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (23,4,'Ahorro','percent',15,NULL,'all_income','#10B981',3,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (24,4,'Educación','percent',10,NULL,'all_income','#3B82F6',4,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (25,4,'Reservas','percent',10,NULL,'all_income','#8B5CF6',5,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_template_jars" VALUES (26,4,'Caridad y regalos','percent',5,NULL,'all_income','#EF4444',6,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "jar_templates" VALUES (1,'Moderado','moderado','Distribución equilibrada',1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "jar_templates" VALUES (2,'Avanzado','avanzado','Más categorías, coche 5% y reparto detallado',1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "jar_templates" VALUES (3,'Conservador','conservador','Más peso a necesidades y ahorro',1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "jar_templates" VALUES (4,'Arriesgado','arriesgado','Más ocio y metas',1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "jar_tpljar_cat_tpl" VALUES (1,1,3,NULL,NULL);
INSERT INTO "jar_tpljar_cat_tpl" VALUES (2,1,4,NULL,NULL);
INSERT INTO "jar_tpljar_cat_tpl" VALUES (3,2,6,NULL,NULL);
INSERT INTO "jar_tpljar_cat_tpl" VALUES (4,4,5,NULL,NULL);
INSERT INTO "migrations" VALUES (1,'0001_01_01_000001_create_cache_table',1);
INSERT INTO "migrations" VALUES (2,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO "migrations" VALUES (3,'2025_07_07_000001_create_clients_table',1);
INSERT INTO "migrations" VALUES (4,'2025_07_08_204336_create_personal_access_tokens_table',1);
INSERT INTO "migrations" VALUES (5,'2025_07_08_213111_create_currencies_table',1);
INSERT INTO "migrations" VALUES (6,'2025_07_09_144917_create_account_types_table',1);
INSERT INTO "migrations" VALUES (7,'2025_07_13_000000_create_rates_table',1);
INSERT INTO "migrations" VALUES (8,'2025_07_14_100000_create_accounts_table',1);
INSERT INTO "migrations" VALUES (9,'2025_07_14_119999_create_users_table',1);
INSERT INTO "migrations" VALUES (10,'2025_07_14_120000_create_providers_table',1);
INSERT INTO "migrations" VALUES (11,'2025_07_14_120000_create_transactions_table',1);
INSERT INTO "migrations" VALUES (12,'2025_07_16_022945_create_jar_table',1);
INSERT INTO "migrations" VALUES (13,'2025_07_16_022946_create_categories_table',1);
INSERT INTO "migrations" VALUES (14,'2025_07_16_193536_create_roles_table',1);
INSERT INTO "migrations" VALUES (15,'2025_07_16_193537_add_role_to_users_table',1);
INSERT INTO "migrations" VALUES (16,'2025_07_17_000000_create_taxes_table',1);
INSERT INTO "migrations" VALUES (17,'2025_07_17_000001_create_item_transactions_table',1);
INSERT INTO "migrations" VALUES (18,'2025_07_28_000000_create_account_user_table',1);
INSERT INTO "migrations" VALUES (19,'2025_07_28_000002_create_item_categories_table',1);
INSERT INTO "migrations" VALUES (20,'2025_07_28_000003_create_item_taxes_table',1);
INSERT INTO "migrations" VALUES (21,'2025_07_28_100010_create_items_table',1);
INSERT INTO "migrations" VALUES (22,'2025_07_28_100011_create_payment_transactions_table',1);
INSERT INTO "migrations" VALUES (23,'2025_07_28_100012_create_payment_transaction_taxes_table',1);
INSERT INTO "migrations" VALUES (24,'2025_07_28_100013_create_accounts_taxes_table',1);
INSERT INTO "migrations" VALUES (25,'2025_08_01_000001_add_item_id_to_item_transactions_table',1);
INSERT INTO "migrations" VALUES (26,'2025_08_01_000002_add_item_id_to_item_taxes_table',1);
INSERT INTO "migrations" VALUES (27,'2025_08_01_000002_add_quantity_to_item_transactions_table',1);
INSERT INTO "migrations" VALUES (28,'2025_08_01_000003_add_item_transaction_id_to_item_taxes_table',1);
INSERT INTO "migrations" VALUES (29,'2025_08_13_000001_create_transaction_types_table',1);
INSERT INTO "migrations" VALUES (30,'2025_08_13_000002_add_transaction_type_id_to_transactions_table',1);
INSERT INTO "migrations" VALUES (31,'2025_08_20_000000_create_account_folders_table',1);
INSERT INTO "migrations" VALUES (32,'2025_08_20_000002_add_folder_fk_to_account_user',1);
INSERT INTO "migrations" VALUES (33,'2025_08_21_000100_alter_jars_add_user_and_fields',1);
INSERT INTO "migrations" VALUES (34,'2025_08_21_000110_create_jar_category_table',1);
INSERT INTO "migrations" VALUES (35,'2025_08_21_000120_create_jar_base_category_table',1);
INSERT INTO "migrations" VALUES (36,'2025_08_22_000200_create_jar_templates_table',1);
INSERT INTO "migrations" VALUES (37,'2025_08_22_000205_cleanup_template_pivot_tables',1);
INSERT INTO "migrations" VALUES (38,'2025_08_22_000210_create_jar_template_jars_table',1);
INSERT INTO "migrations" VALUES (39,'2025_08_22_000220_create_jar_template_jar_categories_table',1);
INSERT INTO "migrations" VALUES (40,'2025_08_22_000230_create_jar_template_jar_base_categories_table',1);
INSERT INTO "migrations" VALUES (41,'2025_08_22_000240_add_user_id_to_categories_table',1);
INSERT INTO "migrations" VALUES (42,'2025_08_22_000241_add_unique_user_name_to_categories_table',1);
INSERT INTO "migrations" VALUES (43,'2025_08_22_000245_create_category_templates_tables',1);
INSERT INTO "migrations" VALUES (44,'2025_08_23_000300_add_unique_user_parent_name_to_account_folders',1);
INSERT INTO "migrations" VALUES (45,'2025_08_23_000400_change_unique_index_on_categories',1);
INSERT INTO "migrations" VALUES (46,'2025_08_25_000500_add_fields_to_categories_table',1);
INSERT INTO "migrations" VALUES (47,'2025_08_29_000300_update_jar_pivots_add_active_softdeletes',1);
INSERT INTO "migrations" VALUES (48,'2025_09_09_000001_add_applies_to_to_taxes_table',1);
INSERT INTO "migrations" VALUES (49,'2025_09_09_000002_add_parent_id_to_item_categories_table',1);
INSERT INTO "migrations" VALUES (50,'2025_09_11_000001_add_include_in_balance_to_transactions_table',1);
INSERT INTO "migrations" VALUES (51,'2025_09_11_000002_add_ajuste_transaction_type',1);
INSERT INTO "migrations" VALUES (52,'2025_09_12_000100_add_balance_cached_to_accounts_table',1);
INSERT INTO "migrations" VALUES (53,'2025_09_15_000001_add_item_category_id_to_item_transactions_table',1);
INSERT INTO "migrations" VALUES (54,'2025_10_01_120000_drop_legacy_category_id_from_item_transactions',1);
INSERT INTO "migrations" VALUES (55,'2025_10_02_030000_add_unique_index_to_currencies_code',1);
INSERT INTO "migrations" VALUES (56,'2025_10_02_120500_add_category_id_to_transactions_table',1);
INSERT INTO "migrations" VALUES (57,'2025_10_06_120000_create_user_currencies_table',2);
INSERT INTO "migrations" VALUES (58,'2025_10_06_121000_add_is_official_to_user_currencies_table',3);
INSERT INTO "migrations" VALUES (59,'2025_11_12_130000_add_official_at_to_user_currencies_table',4);
INSERT INTO "migrations" VALUES (60,'2025_11_12_230500_add_user_currency_id_to_payment_transactions_table',4);
INSERT INTO "payment_transaction_taxes" VALUES (1,1,2,28.33,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (2,1,4,2.83,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (3,2,2,26.39,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (4,2,4,2.64,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (5,3,2,4.36,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (6,3,4,0.44,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (7,4,2,14.01,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (8,4,4,1.4,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (9,5,2,16.46,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (10,5,4,1.65,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (11,6,2,12.34,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (12,6,4,1.23,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (13,7,2,3.22,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (14,7,4,0.32,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (15,8,2,28.2,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (16,8,4,2.82,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (17,9,2,16.05,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (18,9,4,1.6,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (19,10,2,28.64,3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transaction_taxes" VALUES (20,10,4,2.86,0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51');
INSERT INTO "payment_transactions" VALUES (1,1,1,944.19,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (2,1,1,879.8,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (3,1,1,145.18,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (4,1,1,467.02,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (5,1,1,548.81,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (6,1,1,411.39,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (7,1,1,107.43,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (8,1,1,940.06,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (9,1,1,534.87,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (10,1,1,954.77,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (11,41,31,100,1,'2025-10-06 15:03:40','2025-10-06 15:05:23','2025-10-06 15:05:23',NULL);
INSERT INTO "payment_transactions" VALUES (12,41,31,100,1,'2025-10-06 15:05:23','2025-10-06 15:05:44','2025-10-06 15:05:44',NULL);
INSERT INTO "payment_transactions" VALUES (13,41,31,100,1,'2025-10-06 15:05:44','2025-10-06 15:05:44',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (14,42,31,200,1,'2025-10-06 15:06:01','2025-10-06 15:06:01',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (15,43,31,-12,1,'2025-10-06 15:06:21','2025-10-06 15:06:21',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (16,45,16,664,1,'2025-11-10 00:58:49','2025-11-10 00:58:49',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (17,46,16,-180.9,1,'2025-11-10 12:43:08','2025-11-10 12:43:08',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (18,47,15,100,1,'2025-11-11 21:17:41','2025-11-11 21:17:41',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (19,48,17,100,1,'2025-11-11 22:32:15','2025-11-11 22:32:15',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (20,49,15,20,1,'2025-11-12 03:52:23','2025-11-12 03:52:23',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (21,50,15,10,1,'2025-11-12 03:58:00','2025-11-12 03:58:00',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (22,51,15,20,1,'2025-11-12 10:45:53','2025-11-12 10:45:53',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (23,52,15,1,1,'2025-11-12 10:46:36','2025-11-12 10:46:36',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (24,53,15,22,1,'2025-11-12 13:37:46','2025-11-12 13:37:46',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (25,54,15,2222,1,'2025-11-12 14:10:46','2025-11-12 14:10:46',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (26,55,15,2323,1,'2025-11-12 14:11:20','2025-11-12 14:11:20',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (27,56,15,22,1,'2025-11-12 14:17:09','2025-11-12 14:17:09',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (28,57,15,222,1,'2025-11-12 14:17:27','2025-11-12 14:17:27',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (29,58,15,222,1,'2025-11-12 14:18:00','2025-11-12 14:18:00',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (30,59,15,2233,1,'2025-11-12 14:18:51','2025-11-12 14:18:51',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (31,60,15,3000,1,'2025-11-12 18:35:16','2025-11-12 18:35:16',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (32,61,15,222,1,'2025-11-12 18:49:30','2025-11-12 18:49:30',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (33,62,15,22,1,'2025-11-12 19:10:06','2025-11-12 19:10:06',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (34,63,15,222,1,'2025-11-12 19:25:24','2025-11-12 19:25:24',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (35,64,15,2222,1,'2025-11-12 19:33:53','2025-11-12 19:33:53',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (36,65,15,213123,1,'2025-11-12 19:51:08','2025-11-12 19:51:08',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (37,66,15,22,1,'2025-11-12 20:51:28','2025-11-12 20:51:28',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (38,67,15,111,1,'2025-11-12 20:52:33','2025-11-12 20:52:33',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (39,68,15,12323,1,'2025-11-13 00:48:24','2025-11-13 00:48:24',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (40,69,15,300,1,'2025-11-13 00:48:54','2025-11-13 00:48:54',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (41,70,15,350,1,'2025-11-13 01:17:46','2025-11-13 01:17:46',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (42,71,15,213123,1,'2025-11-13 01:18:28','2025-11-13 01:18:28',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (43,72,15,330,1,'2025-11-13 01:35:09','2025-11-13 01:35:09',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (44,73,15,340,1,'2025-11-13 01:43:29','2025-11-13 01:43:29',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (45,74,15,21,1,'2025-11-13 02:01:48','2025-11-13 02:01:48',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (46,75,15,340,1,'2025-11-13 02:11:56','2025-11-13 02:11:56',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (47,76,15,222,1,'2025-11-13 02:12:27','2025-11-13 02:12:27',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (48,77,15,222,1,'2025-11-13 02:54:57','2025-11-13 02:55:27','2025-11-13 02:55:27',18);
INSERT INTO "payment_transactions" VALUES (49,77,15,222,1,'2025-11-13 02:55:27','2025-11-13 02:55:27',NULL,18);
INSERT INTO "payment_transactions" VALUES (50,78,15,335,1,'2025-11-13 02:57:15','2025-11-13 05:39:50','2025-11-13 05:39:50',22);
INSERT INTO "payment_transactions" VALUES (51,79,15,222,1,'2025-11-13 02:58:10','2025-11-13 02:59:25','2025-11-13 02:59:25',12);
INSERT INTO "payment_transactions" VALUES (52,79,15,222,1,'2025-11-13 02:59:25','2025-11-13 05:57:22','2025-11-13 05:57:22',18);
INSERT INTO "payment_transactions" VALUES (53,79,17,555,1,'2025-11-13 02:59:25','2025-11-13 05:57:22','2025-11-13 05:57:22',23);
INSERT INTO "payment_transactions" VALUES (54,78,15,335,1,'2025-11-13 05:39:50','2025-11-13 05:39:50',NULL,24);
INSERT INTO "payment_transactions" VALUES (55,80,15,-220,1,'2025-11-13 05:55:43','2025-11-13 05:55:43',NULL,25);
INSERT INTO "payment_transactions" VALUES (56,79,15,-222,1,'2025-11-13 05:57:22','2025-11-13 05:57:22',NULL,18);
INSERT INTO "payment_transactions" VALUES (57,79,17,-555,1,'2025-11-13 05:57:22','2025-11-13 05:57:22',NULL,23);
INSERT INTO "payment_transactions" VALUES (58,81,16,-1000,1,'2025-11-16 06:24:19','2025-11-16 06:24:19',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (59,81,17,1000,1,'2025-11-16 06:24:19','2025-11-16 06:24:19',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (60,82,11,-10,1,'2025-11-16 06:58:43','2025-11-16 06:58:43',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (61,82,34,15,1,'2025-11-16 06:58:43','2025-11-16 06:58:43',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (62,83,34,-5,1,'2025-11-16 06:59:56','2025-11-16 06:59:56',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (63,83,32,10,1,'2025-11-16 06:59:56','2025-11-16 06:59:56',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (64,84,11,-15,1,'2025-11-16 07:03:04','2025-11-16 07:03:04',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (65,84,34,30,1,'2025-11-16 07:03:04','2025-11-16 07:03:04',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (66,85,34,-10,1,'2025-11-16 07:24:50','2025-11-16 07:24:50',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (67,85,31,10,1,'2025-11-16 07:24:50','2025-11-16 07:24:50',NULL,NULL);
INSERT INTO "payment_transactions" VALUES (68,86,17,111,1,'2025-11-16 08:22:49','2025-11-16 08:22:49',NULL,26);
INSERT INTO "payment_transactions" VALUES (69,87,16,222,1,'2025-11-16 08:23:20','2025-11-16 08:23:20',NULL,27);
INSERT INTO "payment_transactions" VALUES (70,88,16,3302,1,'2025-11-16 08:23:41','2025-11-16 08:23:41',NULL,27);
INSERT INTO "personal_access_tokens" VALUES (1,'App\Models\User',4,'quasar-spa','3a5bbcedfbf0bc9a96eee4e61f80a6276fe1fa11324f18793b73317dd3cee416','["*"]','2025-11-11 06:38:54',NULL,'2025-10-06 15:03:17','2025-11-11 06:38:54');
INSERT INTO "personal_access_tokens" VALUES (2,'App\Models\User',4,'api','7216142afe5ee6f151c6641293311911a8569fb7df9852f246a914049f537980','["*"]',NULL,NULL,'2025-11-11 06:39:23','2025-11-11 06:39:23');
INSERT INTO "personal_access_tokens" VALUES (3,'App\Models\User',4,'api','27d0f330c20e623b82998db63930311f41ab13d34f0d6f3238b9999385773011','["*"]',NULL,NULL,'2025-11-11 06:39:26','2025-11-11 06:39:26');
INSERT INTO "personal_access_tokens" VALUES (4,'App\Models\User',4,'api','c8f335180774e039e60fea4e5fb2ece1b48d63035ca5ddff82d284ac6a708f41','["*"]',NULL,NULL,'2025-11-11 06:40:32','2025-11-11 06:40:32');
INSERT INTO "personal_access_tokens" VALUES (5,'App\Models\User',4,'api','a465ddae71369c56b9e8b6dc88d11a18cd8d2095db598ad7b895c0499246f74d','["*"]',NULL,NULL,'2025-11-11 20:40:56','2025-11-11 20:40:56');
INSERT INTO "personal_access_tokens" VALUES (6,'App\Models\User',4,'api','3845503427a74727ddb3d46e20d21a1d9b4231fecbbdc27c9faf373c9fe542a2','["*"]',NULL,NULL,'2025-11-11 20:42:44','2025-11-11 20:42:44');
INSERT INTO "personal_access_tokens" VALUES (7,'App\Models\User',4,'api','24b219c3b6ed463b590812f0b4193ec485fa209345f626336c9c94cf14d5987b','["*"]',NULL,NULL,'2025-11-11 20:42:50','2025-11-11 20:42:50');
INSERT INTO "personal_access_tokens" VALUES (8,'App\Models\User',4,'api','08ac21133096cf9d180cf4733592c09079c752501f5ed682840de847fb9d04d3','["*"]','2025-11-16 02:56:38',NULL,'2025-11-11 20:54:30','2025-11-16 02:56:38');
INSERT INTO "personal_access_tokens" VALUES (9,'App\Models\User',4,'api','e9236bbbb53b260e0a3d909a55e5585c6055d2f9b110ad2fb183c6e6981ebce2','["*"]','2025-11-16 07:34:18',NULL,'2025-11-16 06:01:55','2025-11-16 07:34:18');
INSERT INTO "personal_access_tokens" VALUES (10,'App\Models\User',4,'api','e21e5b0ddac5a80823fb35528f6b63a9c8747775ca4f9a5d7d2db9aa585376c7','["*"]','2025-11-16 06:25:42',NULL,'2025-11-16 06:06:04','2025-11-16 06:25:42');
INSERT INTO "personal_access_tokens" VALUES (11,'App\Models\User',4,'api','b2fe8d8c72c25d8c637c08d43fdeb58fed57dcc6d1739ec1293a778dc4607414','["*"]','2025-11-16 08:27:05',NULL,'2025-11-16 08:22:18','2025-11-16 08:27:05');
INSERT INTO "personal_access_tokens" VALUES (12,'App\Models\User',4,'api','df13ffa7ee93f0f6b6b1d5fc621d3c606ba5f0cb9474f6b1bb944e8244ccb678','["*"]',NULL,NULL,'2025-11-16 08:38:44','2025-11-16 08:38:44');
INSERT INTO "personal_access_tokens" VALUES (13,'App\Models\User',4,'api','a2cdc79ced6725c41bdcb4f1f9e0034ced7174b3b86213d65cfe9fa2b2330219','["*"]',NULL,NULL,'2025-11-16 09:25:49','2025-11-16 09:25:49');
INSERT INTO "personal_access_tokens" VALUES (14,'App\Models\User',4,'api','448c0ef1f32df71a28462d714f435187539c0b06f17ff5efa22751b259b17fa6','["*"]',NULL,NULL,'2025-11-16 09:26:19','2025-11-16 09:26:19');
INSERT INTO "personal_access_tokens" VALUES (15,'App\Models\User',4,'api','aa5cc0ed51c3f11c8a6982b38c992eb4df4890e87f94f8bc7bb502b977d5df3e','["*"]',NULL,NULL,'2025-11-16 09:26:48','2025-11-16 09:26:48');
INSERT INTO "personal_access_tokens" VALUES (16,'App\Models\User',4,'api','9f3289617e34d1805c85ce257c3fcf50d9d4f0ea8595d69c5a4c5bc9e464c33c','["*"]',NULL,NULL,'2025-11-16 09:27:48','2025-11-16 09:27:48');
INSERT INTO "personal_access_tokens" VALUES (17,'App\Models\User',4,'api','0cfba32f83393ba98d68b6329f83f93323cd8d01a1d188b1d0af4b5ef46ff2e3','["*"]',NULL,NULL,'2025-11-16 09:27:51','2025-11-16 09:27:51');
INSERT INTO "providers" VALUES (1,'Kessler-Shields','Aperiam dolor minima officia pariatur excepturi.','12059 Otilia Mission
New Hailie, SC 05574','devante.fisher@example.org','534-728-7440','http://www.dietrich.com/nisi-dolorum-occaecati-autem-labore-explicabo-id.html',NULL,NULL,NULL,NULL,NULL,14,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (2,'Champlin Ltd','Molestiae rerum quis officia ipsam laboriosam sed.','967 Carolyne Springs
Gudrunside, CO 07031-5420','kling.dedric@example.net','+1-586-692-7935','https://ullrich.com/quaerat-magni-vel-ut-ut-dignissimos.html',NULL,NULL,NULL,NULL,NULL,3,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (3,'Kihn-Beahan','Voluptatem consequatur quia et et fuga commodi non.','7605 Schamberger Lane Apt. 882
North Clemensberg, NV 86866','mozelle51@example.com','+1-817-315-0196','http://ullrich.com/aut-tempore-et-quia-dolor',NULL,NULL,NULL,NULL,NULL,9,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (4,'Roberts-Gusikowski','Beatae consequatur animi odit quia.','3331 Bashirian Alley
Shieldsport, NY 99826-8214','smitham.jewell@example.net','+1 (531) 360-3146','http://www.torp.info/',NULL,NULL,NULL,NULL,NULL,6,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (5,'Dietrich, Collins and Marks','Eos fugit perspiciatis laborum.','531 Abbott Village
North Trinity, DE 54190','hillard.bauch@example.net','+1-828-318-5587','http://nikolaus.com/voluptatem-et-sit-omnis-nobis-quibusdam-autem-iste.html',NULL,NULL,NULL,NULL,NULL,3,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (6,'Ratke-Yost','Nulla et ut libero voluptatem earum accusantium enim.','20533 Sadye Cliff
Linniefort, IA 08332','lisandro.littel@example.org','678-316-1668','https://nader.info/quia-temporibus-occaecati-doloremque-rerum-est-eum-perspiciatis.html',NULL,NULL,NULL,NULL,NULL,1,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (7,'Ortiz, Cruickshank and Carroll','Natus consequuntur cum laudantium sequi qui at.','1057 Fermin Fall
Nikolausland, VA 31480','mraz.kristina@example.net','1-763-327-1201','http://marks.com/necessitatibus-quisquam-placeat-consectetur-voluptatem-deserunt-et-sed',NULL,NULL,NULL,NULL,NULL,6,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (8,'Rippin, Orn and Hills','Enim doloremque numquam quis.','53133 Dooley Forks
Lake Naomieville, OR 72922','delta.schuppe@example.org','707-497-6233','http://hammes.com/quia-vel-voluptas-vel-numquam-voluptatum-quisquam-in-maiores',NULL,NULL,NULL,NULL,NULL,14,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (9,'Larkin-Green','Dignissimos illum error eum nam ullam iure.','8921 Zboncak Plain
North Blair, IL 40844-7534','jarmstrong@example.org','281.614.1502','http://www.reilly.com/',NULL,NULL,NULL,NULL,NULL,3,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (10,'Hartmann-Casper','Dolorem accusantium debitis quo a odio dolorum.','162 Orn Rue
Braunport, FL 90187','raven.miller@example.org','313-841-9262','http://turcotte.com/consequatur-deserunt-aut-quia-quod-maxime-aut-voluptatem-odit.html',NULL,NULL,NULL,NULL,NULL,4,1,'2025-10-06 15:01:50','2025-10-06 15:01:50',NULL);
INSERT INTO "providers" VALUES (11,'Panaderia, casa de karo','This is a sample provider.','Jacinto lara','provider@example.com','1234567890','http://example',NULL,NULL,NULL,NULL,NULL,4,1,'2025-11-10 00:57:11','2025-11-10 00:57:11',NULL);
INSERT INTO "rates" VALUES (1,'rem','1990-02-23',908.07,1,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "rates" VALUES (2,'et','1992-02-05',181.47,1,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "rates" VALUES (3,'veritatis','2001-12-31',309.7,0,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "rates" VALUES (4,'maiores','1978-08-08',226.63,1,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "rates" VALUES (5,'perspiciatis','2006-12-06',207.32,0,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "rates" VALUES (6,'earum','2023-10-09',289.02,0,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "rates" VALUES (7,'id','1987-12-26',251.98,0,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "rates" VALUES (8,'sed','2003-09-11',88.88,0,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "rates" VALUES (9,'sit','2000-09-30',939.95,1,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "rates" VALUES (10,'aut','2018-07-29',61.2,0,NULL,'2025-10-06 15:01:50','2025-10-06 15:01:50');
INSERT INTO "roles" VALUES (1,'Admin','admin','2025-10-06 15:01:48','2025-10-06 15:01:48');
INSERT INTO "roles" VALUES (2,'User','user','2025-10-06 15:01:48','2025-10-06 15:01:48');
INSERT INTO "roles" VALUES (3,'Guest','guest','2025-10-06 15:01:48','2025-10-06 15:01:48');
INSERT INTO "sessions" VALUES ('EKkuqcZhUPiALTTyWaw1OwOdr8IMHZJH5ehgqFKK',NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUm9ZM1dMSHh2YVBrQVg1ZWJxNWVsSDd1dEhnUm14dGJFUkQ1dEdobCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1762735662);
INSERT INTO "taxes" VALUES (1,'IVA',16,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,'item');
INSERT INTO "taxes" VALUES (2,'IGTF',3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,'payment');
INSERT INTO "taxes" VALUES (3,'Exento',0,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,'item');
INSERT INTO "taxes" VALUES (4,'Comisión Pago Móvil',0.3,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,'payment');
INSERT INTO "taxes" VALUES (5,'Contribuyente Especial',0,1,NULL,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,'item');
INSERT INTO "transaction_types" VALUES (1,'Ajuste de saldo','ajuste','Ajuste manual de saldo de cuenta',1,'2025-10-06 15:01:48','2025-10-06 15:01:48',NULL);
INSERT INTO "transaction_types" VALUES (2,'Income','income',NULL,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "transaction_types" VALUES (3,'Expense','expense',NULL,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "transaction_types" VALUES (4,'Transfer','transfer',NULL,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "transaction_types" VALUES (5,'Payment','payment',NULL,1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL);
INSERT INTO "transactions" VALUES (1,'consectetur',295.96,'Fugiat quia dolorem et sapiente eveniet hic sit.','2025-03-24 15:02:34',0,2,'https://sanford.com/officiis-eum-inventore-incidunt-repudiandae.html',2,1,4,57.7,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,3,1,NULL);
INSERT INTO "transactions" VALUES (2,'tenetur',218.91,'Laboriosam iure quae omnis et molestias natus.','2025-07-07 03:52:17',0,6,'http://www.schaden.com/officia-labore-iure-id',4,12,29,60.19,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (3,'molestias',838.1,'Qui quia tenetur facilis nulla ut.','2025-05-03 18:59:59',1,8,'http://www.olson.biz/',10,10,21,45.78,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (4,'et',390.04,'Velit sunt et molestiae amet nesciunt.','2025-04-02 13:00:27',1,4,'http://www.walsh.org/qui-est-sit-natus-hic-non-veritatis',4,10,18,98.09,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (5,'consequatur',526.29,'Velit animi nulla laborum eum.','2025-07-04 17:51:42',1,10,'http://www.rice.com/enim-accusamus-voluptatibus-consequuntur-deserunt-voluptatem-voluptatem-dolor.html',7,12,11,19.11,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (6,'esse',89.51,'Rerum exercitationem reprehenderit et exercitationem reiciendis facilis iste laudantium.','2025-08-30 09:09:43',0,8,'http://johns.com/minus-sit-maiores-laborum-maxime-laborum-rerum',8,4,24,3.29,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (7,'autem',173.15,'Ut enim a porro porro eos quo.','2025-08-25 06:49:25',1,2,'http://murphy.com/ut-quis-fugiat-molestias-quis-omnis-et-dicta.html',8,12,4,55.73,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (8,'aspernatur',574.84,'Dolor voluptatem excepturi ea dolorem.','2025-07-20 10:47:33',1,10,'http://effertz.info/',4,5,7,4.37,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,5,1,NULL);
INSERT INTO "transactions" VALUES (9,'eius',174.85,'Nihil doloribus quia in molestias quam.','2025-03-17 02:56:50',0,8,'https://funk.org/placeat-atque-excepturi-debitis.html',1,14,12,25.78,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,5,1,NULL);
INSERT INTO "transactions" VALUES (10,'velit',310.84,'Voluptatem dolor et porro ipsum nihil dolor fuga.','2025-05-15 19:48:06',0,9,'http://www.lind.biz/dignissimos-magnam-corrupti-magni-omnis-nihil',5,10,7,54.18,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (11,'suscipit',252,'Ex at quae similique ut ratione.','2025-09-27 17:13:33',0,1,'http://www.ward.com/maiores-quae-dignissimos-dolorem-iste-hic-molestiae.html',2,3,4,6.05,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,5,1,NULL);
INSERT INTO "transactions" VALUES (12,'eos',969.98,'Consequatur repellendus facere ut numquam reprehenderit et.','2025-07-08 05:11:57',1,1,'http://www.harber.net/ut-nobis-nostrum-earum',3,13,30,98.39,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (13,'ut',236.65,'Aut molestiae voluptatibus ipsa ex.','2025-04-15 22:31:54',0,7,'http://www.vandervort.com/corrupti-sapiente-enim-ut-dolores-corporis-voluptas-ab.html',2,8,7,9.57,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (14,'laudantium',936.08,'Vel voluptas neque veniam enim rem quis.','2025-06-22 18:29:25',0,1,'http://www.schamberger.com/',10,7,1,98.16,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (15,'et',826.93,'Ullam exercitationem nostrum ut omnis molestiae architecto.','2025-03-08 12:19:29',1,5,'http://www.farrell.com/vel-aut-temporibus-ex-illo',1,11,9,2.66,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (16,'tenetur',524.16,'Autem sunt at doloremque ut facere est iusto.','2025-04-22 08:29:55',0,7,'http://terry.com/fuga-et-ullam-corporis-et',9,14,34,81.04,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (17,'nobis',814.41,'Reiciendis est dolor sed voluptatem nobis laborum pariatur.','2025-06-21 01:29:54',0,6,'http://wilkinson.com/',6,11,28,5.26,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (18,'omnis',411.08,'Fugiat eum odit corrupti.','2025-09-06 19:44:22',0,9,'http://www.leannon.info/totam-laborum-quia-explicabo-ut.html',9,1,23,19.11,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,5,1,NULL);
INSERT INTO "transactions" VALUES (19,'accusamus',96.03,'Eveniet aperiam et cumque.','2025-06-16 21:09:17',0,4,'http://spencer.org/',6,10,16,2.17,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (20,'qui',142.1,'Rerum deleniti aperiam magni quo omnis.','2025-04-26 23:47:56',0,10,'http://deckow.org/dolor-sit-deleniti-minima-libero-molestiae-quia',5,7,22,65.27,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (21,'et',570.83,'Similique vel eos quibusdam est nihil optio.','2025-09-02 15:08:58',0,5,'http://yost.com/consectetur-dolor-odio-sed-dolor-sunt-dolore-ipsum',6,12,9,25.81,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (22,'ex',661.31,'Non rerum quibusdam ut quaerat pariatur deserunt.','2025-03-14 08:42:05',0,2,'http://www.durgan.com/enim-occaecati-et-voluptas',9,3,7,6.69,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (23,'soluta',692.78,'Soluta quaerat dolor aliquam velit mollitia enim.','2025-07-05 13:46:28',0,7,'http://halvorson.com/',4,3,15,91.3,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,5,1,NULL);
INSERT INTO "transactions" VALUES (24,'saepe',803.52,'Ut suscipit doloribus est et reprehenderit exercitationem dolor.','2025-06-04 09:14:15',1,2,'http://walsh.com/ut-harum-voluptates-tenetur-nostrum-temporibus-est-tempora-consequuntur.html',7,6,17,84.08,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (25,'et',420.5,'Et perspiciatis voluptatem laboriosam rem sunt.','2025-07-12 13:09:28',0,4,'http://www.grimes.com/consectetur-impedit-cupiditate-suscipit.html',9,10,9,10.1,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,3,1,NULL);
INSERT INTO "transactions" VALUES (26,'voluptates',759.48,'Odit facilis rerum atque inventore.','2025-09-08 06:08:59',1,3,'https://www.pollich.com/provident-totam-sed-aperiam-iure',3,11,27,83.61,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (27,'perspiciatis',349.07,'Id est possimus quasi accusantium eaque consequatur.','2025-05-14 03:38:41',0,4,'https://bergstrom.org/et-rerum-quae-nesciunt-non.html',7,6,12,10.01,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (28,'explicabo',884.53,'Quibusdam aut non pariatur omnis.','2025-04-05 01:20:21',1,3,'http://www.farrell.com/minima-quas-ut-quo-repellendus-rerum-placeat-quia',3,11,11,60.89,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,5,1,NULL);
INSERT INTO "transactions" VALUES (29,'quae',44.04,'Aut facere laboriosam sed repellendus deleniti velit culpa sunt.','2025-05-31 10:26:37',1,8,'http://www.mckenzie.net/culpa-ad-nobis-velit-reprehenderit',5,13,18,56.62,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (30,'laboriosam',140.74,'Voluptatum exercitationem est fugiat culpa ducimus recusandae.','2025-09-26 10:18:52',1,5,'https://www.schimmel.biz/quis-officia-in-dolore-sit-dicta-earum',4,4,17,42.2,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (31,'quas',844.1,'Eius placeat et eum non quisquam.','2025-03-26 19:01:38',0,6,'http://cruickshank.com/natus-necessitatibus-fugiat-tempore-iusto-doloremque-explicabo.html',10,6,12,90.54,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (32,'quis',388.36,'Beatae voluptatem nemo magni atque voluptate molestiae facilis cumque.','2025-05-14 01:28:33',0,3,'https://keebler.com/laudantium-labore-culpa-aut-ipsum-dolor-incidunt-voluptatem-id.html',2,5,23,67.64,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (33,'suscipit',128.7,'Molestias odit qui possimus enim officiis labore id.','2025-05-18 09:59:17',1,7,'http://www.jenkins.com/id-aut-non-illo-quis-molestiae-culpa-vero.html',10,6,8,60.55,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (34,'aut',815.3,'Animi maxime cupiditate voluptatem et modi eum quidem.','2025-07-09 15:48:45',0,6,'http://www.tromp.com/expedita-facilis-dolorem-ipsa-unde-quas',5,2,5,11.37,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,3,1,NULL);
INSERT INTO "transactions" VALUES (35,'aliquam',472.78,'Rerum alias voluptatibus sapiente qui fugit odit.','2025-07-13 15:46:57',1,9,'http://www.pagac.biz/ab-nihil-rerum-neque-enim-error-sapiente.html',2,6,29,29.62,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,5,1,NULL);
INSERT INTO "transactions" VALUES (36,'ducimus',243.47,'Quo nihil nihil aut repudiandae rerum illo.','2025-05-22 18:57:32',0,6,'http://willms.com/',6,13,7,70.99,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (37,'corrupti',295.76,'Recusandae voluptatem ut modi quibusdam atque harum unde.','2025-01-27 05:25:38',0,10,'http://www.feil.com/natus-odio-cumque-molestias-optio-quasi',6,14,29,10.53,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,1,1,NULL);
INSERT INTO "transactions" VALUES (38,'quam',151.17,'Qui nihil vel quidem quo harum.','2025-04-15 08:14:33',1,9,'https://www.oconnell.com/voluptatibus-neque-molestiae-qui-ut-mollitia-quisquam-dolor',10,1,17,17.27,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,5,1,NULL);
INSERT INTO "transactions" VALUES (39,'enim',975.19,'Temporibus officia incidunt tempore error.','2025-08-19 20:34:54',0,10,'http://dicki.com/architecto-velit-sit-dolorem',1,9,5,90.38,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,5,1,NULL);
INSERT INTO "transactions" VALUES (40,'ullam',262.55,'Sunt sed qui deserunt inventore a molestiae iure.','2025-05-21 17:38:05',1,1,'http://rodriguez.org/at-quia-quaerat-sint-enim-nesciunt',9,2,1,23.32,'2025-10-06 15:01:51','2025-10-06 15:01:51',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (41,'Agrego 100',100,NULL,'2025-10-06 11:03:00',1,NULL,NULL,NULL,4,NULL,0,'2025-10-06 15:03:40','2025-10-06 15:03:40',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (42,'Reste deberia queda 238',200,NULL,'2025-10-06 11:05:00',1,NULL,NULL,NULL,4,NULL,0,'2025-10-06 15:06:01','2025-10-06 15:06:01',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (43,'resto deberia dar 250',-12,NULL,'2025-10-06 11:06:00',1,NULL,NULL,NULL,4,NULL,0,'2025-10-06 15:06:21','2025-10-06 15:06:21',NULL,3,1,NULL);
INSERT INTO "transactions" VALUES (44,'Ajuste de saldo',442.46,'Ajuste manual de saldo','2025-11-10 00:51:37',1,NULL,NULL,NULL,4,16,0,'2025-11-10 00:51:37','2025-11-10 00:51:37',NULL,NULL,1,NULL);
INSERT INTO "transactions" VALUES (45,'Panes en casa de karo',2.87,NULL,'2025-11-09 20:53:00',1,11,NULL,NULL,4,NULL,0,'2025-11-10 00:58:49','2025-11-10 00:58:49',NULL,2,1,236);
INSERT INTO "transactions" VALUES (46,'Cafe farmatodo',-0.72,NULL,'2025-11-10 08:41:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-10 12:43:08','2025-11-10 12:43:08',NULL,3,1,211);
INSERT INTO "transactions" VALUES (47,'Pago 100 prueba',0.39,NULL,'2025-11-11 17:16:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-11 21:17:41','2025-11-11 21:17:41',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (48,'123213',0.38,NULL,'2025-11-11 18:27:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-11 22:32:15','2025-11-11 22:32:15',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (49,'prueba 20',0.08,NULL,'2025-11-11 23:49:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 03:52:23','2025-11-12 03:52:23',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (50,'prueba 10',0.04,NULL,'2025-11-11 23:56:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 03:58:00','2025-11-12 03:58:00',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (51,'pgo 20',0.08,NULL,'2025-11-12 06:44:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 10:45:53','2025-11-12 10:45:53',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (52,'pago 260',0,NULL,'2025-11-12 06:45:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 10:46:36','2025-11-12 10:46:36',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (53,'Aumento 10 tasa 22 monto',0.08,NULL,'2025-11-12 09:37:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 13:37:46','2025-11-12 13:37:46',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (54,'concepto prueb',8.89,NULL,'2025-11-12 09:37:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 14:10:46','2025-11-12 14:10:46',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (55,'tsto',8.93,NULL,'2025-11-12 10:11:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 14:11:20','2025-11-12 14:11:20',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (56,'etrsd',0.08,NULL,'2025-11-12 10:11:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 14:17:09','2025-11-12 14:17:09',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (57,'sdsd',0.82,NULL,'2025-11-12 10:17:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 14:17:27','2025-11-12 14:17:27',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (58,'prueba 280',0.79,NULL,'2025-11-12 10:17:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 14:18:00','2025-11-12 14:18:00',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (59,'PAgo 250',8.93,NULL,'2025-11-12 10:18:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 14:18:51','2025-11-12 14:18:51',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (60,'PAGO 300',10,NULL,'2025-11-12 14:34:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 18:35:16','2025-11-12 18:35:16',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (61,'concepto con rate',1,NULL,'2025-11-12 14:49:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 18:49:30','2025-11-12 18:49:30',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (62,'222',0.1,NULL,'2025-11-12 14:53:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 19:10:06','2025-11-12 19:10:06',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (63,'sdsds',1,NULL,'2025-11-12 15:25:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 19:25:24','2025-11-12 19:25:24',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (64,'2323',6.67,NULL,'2025-11-12 15:33:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 19:33:53','2025-11-12 19:33:53',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (65,'sdsd',960.01,NULL,'2025-11-12 15:33:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 19:51:08','2025-11-12 19:51:08',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (66,'2312312',1,NULL,'2025-11-12 16:50:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 20:51:28','2025-11-12 20:51:28',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (67,'3123213',0.44,NULL,'2025-11-12 16:52:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-12 20:52:33','2025-11-12 20:52:33',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (68,'sdadsad',100.19,NULL,'2025-11-12 20:47:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 00:48:24','2025-11-13 00:48:24',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (69,'213123',1,NULL,'2025-11-12 20:48:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 00:48:54','2025-11-13 00:48:54',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (70,'tasa 350',1,NULL,'2025-11-12 20:48:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 01:17:46','2025-11-13 01:17:46',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (71,'pago 400',532.81,NULL,'2025-11-12 21:17:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 01:18:28','2025-11-13 01:18:28',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (72,'330 pago',1,NULL,'2025-11-12 21:34:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 01:35:09','2025-11-13 01:35:09',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (73,'1312312',1,NULL,'2025-11-12 21:35:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 01:43:29','2025-11-13 01:43:29',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (74,'123123123',0.06,NULL,'2025-11-12 21:43:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 02:01:48','2025-11-13 02:01:48',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (75,'Pago',1,NULL,'2025-11-12 22:01:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 02:11:56','2025-11-13 02:11:56',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (76,'21323',0.65,NULL,'2025-11-12 22:11:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 02:12:27','2025-11-13 02:12:27',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (77,'330 tasa',0.67,NULL,'2025-11-12 22:53:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 02:54:57','2025-11-13 02:54:57',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (78,'tasa 335',0.61,NULL,'2025-11-12 22:56:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 02:57:15','2025-11-13 05:39:50',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (79,'pago 222 id 12',-1.67,NULL,'2025-11-12 22:57:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 02:58:10','2025-11-13 05:57:22',NULL,3,1,NULL);
INSERT INTO "transactions" VALUES (80,'tasa 220',-1,NULL,'2025-11-13 01:55:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-13 05:55:43','2025-11-13 05:55:43',NULL,3,1,NULL);
INSERT INTO "transactions" VALUES (81,'ENVIO 1000',1000,NULL,'2025-11-16 02:09:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-16 06:24:19','2025-11-16 06:24:19',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (82,'Cambio 10 menos fernando a euro',10,NULL,'2025-11-16 02:52:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-16 06:58:43','2025-11-16 06:58:43',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (83,'Cambio',10,NULL,'2025-11-16 02:58:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-16 06:59:56','2025-11-16 06:59:56',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (84,'CAmbio Actual',15,NULL,'2025-11-16 03:00:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-16 07:03:04','2025-11-16 07:03:04',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (85,'Cambio envio',50,NULL,'2025-11-16 03:03:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-16 07:24:50','2025-11-16 07:24:50',NULL,4,1,NULL);
INSERT INTO "transactions" VALUES (86,'PAGO',0.21,NULL,'2025-11-16 04:22:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-16 08:22:49','2025-11-16 08:22:49',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (87,'pago',0.42,NULL,'2025-11-16 04:23:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-16 08:23:20','2025-11-16 08:23:20',NULL,2,1,NULL);
INSERT INTO "transactions" VALUES (88,'bajo a 530',6.23,NULL,'2025-11-16 04:23:00',1,NULL,NULL,NULL,4,NULL,0,'2025-11-16 08:23:41','2025-11-16 08:23:41',NULL,2,1,NULL);
INSERT INTO "user_currencies" VALUES (1,4,5,231.0462,0,'2025-11-10 00:58:49','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (2,4,5,231.0462,0,'2025-11-10 00:58:49','2025-11-16 08:23:41',1,NULL);
INSERT INTO "user_currencies" VALUES (3,4,5,250,0,'2025-11-10 12:43:08','2025-11-16 08:23:41',1,NULL);
INSERT INTO "user_currencies" VALUES (4,4,5,250,0,'2025-11-10 12:43:08','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (5,4,5,255,0,'2025-11-11 21:17:41','2025-11-16 08:23:41',1,NULL);
INSERT INTO "user_currencies" VALUES (6,4,5,255,0,'2025-11-11 21:17:41','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (7,4,5,260,0,'2025-11-11 22:32:15','2025-11-16 08:23:41',1,NULL);
INSERT INTO "user_currencies" VALUES (8,4,5,260,0,'2025-11-11 22:32:15','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (9,4,5,270,0,'2025-11-12 14:17:27','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (10,4,5,280,0,'2025-11-12 14:18:00','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (11,4,5,300,0,'2025-11-12 18:35:16','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (12,4,5,222,0,'2025-11-12 18:49:30','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (13,4,5,333,0,'2025-11-12 19:33:53','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (14,4,5,22,0,'2025-11-12 20:51:28','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (15,4,5,123,0,'2025-11-13 00:48:24','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (16,4,5,350,0,'2025-11-13 01:17:46','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (17,4,5,400,0,'2025-11-13 01:18:28','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (18,4,5,330,0,'2025-11-13 01:35:09','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (19,4,5,340,0,'2025-11-13 01:43:29','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (20,4,5,345,0,'2025-11-13 02:01:48','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (21,4,5,343,0,'2025-11-13 02:41:28','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (22,4,5,335,0,'2025-11-13 02:57:15','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (23,4,5,555,0,'2025-11-13 02:59:25','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (24,4,5,550,0,'2025-11-13 05:39:50','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (25,4,5,220,0,'2025-11-13 05:55:43','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (26,4,5,520,0,'2025-11-16 08:22:49','2025-11-16 08:23:41',0,NULL);
INSERT INTO "user_currencies" VALUES (27,4,5,530,1,'2025-11-16 08:23:20','2025-11-16 08:23:41',0,NULL);
INSERT INTO "users" VALUES (1,'Administrador',NULL,'admin@demo.com',NULL,'$2y$12$A02ObYAviMjltx.niT9yMOEnAtz3nPMVDyJjJIhGOBQ.mkcW4QP4C',0,6,NULL,1,NULL,NULL,'2025-10-06 15:01:48','2025-10-06 15:01:48',1);
INSERT INTO "users" VALUES (2,'Usuario',NULL,'user@demo.com',NULL,'$2y$12$T3zq2XLIC9enDoeh5WhsEutOuyA9/sL94MZPBzr7cFcH/EUm9NWcm',0,4,NULL,1,NULL,NULL,'2025-10-06 15:01:48','2025-10-06 15:01:48',2);
INSERT INTO "users" VALUES (3,'Invitado',NULL,'guest@demo.com',NULL,'$2y$12$UeG3kJ/WGsDHu0jHMHrWCu1wLMk6de7EyBq2hASll4/dLF6WIRYoS',0,14,NULL,1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',3);
INSERT INTO "users" VALUES (4,'Jose Otero',NULL,'otero@demo.com',NULL,'$2y$12$/BfvfVA5vaDGBLfSgzLXjeE9.DAvU82mHx9jPXOhCcvCTuHDmsVau',0,1,NULL,1,NULL,NULL,'2025-10-06 15:01:49','2025-10-06 15:01:49',2);
INSERT INTO "users" VALUES (5,'Leatha Bernhard',NULL,'turcotte.jackeline@example.com','2025-10-06 15:01:49','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,3,NULL,1,NULL,'nwVb5DQEFO','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
INSERT INTO "users" VALUES (6,'Kariane Runolfsdottir',NULL,'mckenzie.vita@example.com','2025-10-06 15:01:50','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,7,NULL,1,NULL,'CojDJVDrae','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
INSERT INTO "users" VALUES (7,'Sienna Beatty',NULL,'whansen@example.com','2025-10-06 15:01:50','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,2,NULL,1,NULL,'IURMnr3QPM','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
INSERT INTO "users" VALUES (8,'Bonnie Wehner',NULL,'pierre.keebler@example.net','2025-10-06 15:01:50','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,2,NULL,1,NULL,'rj7p6JBgA2','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
INSERT INTO "users" VALUES (9,'Prof. Hassan Harber PhD',NULL,'friesen.keon@example.net','2025-10-06 15:01:50','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,8,NULL,1,NULL,'wiJsTIviTG','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
INSERT INTO "users" VALUES (10,'Geovany Smith I',NULL,'abigail90@example.net','2025-10-06 15:01:50','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,10,NULL,1,NULL,'gcabsA91ly','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
INSERT INTO "users" VALUES (11,'Rosanna Keeling I',NULL,'asa40@example.com','2025-10-06 15:01:50','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,15,NULL,1,NULL,'VPudPXyI8P','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
INSERT INTO "users" VALUES (12,'Rhiannon Kulas',NULL,'cathy.kilback@example.com','2025-10-06 15:01:50','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,2,NULL,1,NULL,'tzunVc18Nt','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
INSERT INTO "users" VALUES (13,'Corbin Koelpin',NULL,'jewell08@example.net','2025-10-06 15:01:50','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,15,NULL,1,NULL,'LQjfxxIJpe','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
INSERT INTO "users" VALUES (14,'Irwin Schneider',NULL,'alberto.oconner@example.org','2025-10-06 15:01:50','$2y$12$Vgd8wHHLFcAb3Va26vfiGeSocRxA9gNwyT08cUf9uyeQwoSkgRJkq',0,3,NULL,1,NULL,'njs0aLeWYL','2025-10-06 15:01:50','2025-10-06 15:01:50',2);
CREATE INDEX IF NOT EXISTS "account_folders_parent_id_index" ON "account_folders" (
	"parent_id"
);
CREATE INDEX IF NOT EXISTS "account_user_folder_id_index" ON "account_user" (
	"folder_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "account_user_user_id_account_id_unique" ON "account_user" (
	"user_id",
	"account_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "category_templates_slug_unique" ON "category_templates" (
	"slug"
);
CREATE UNIQUE INDEX IF NOT EXISTS "clients_email_unique" ON "clients" (
	"email"
);
CREATE UNIQUE INDEX IF NOT EXISTS "failed_jobs_uuid_unique" ON "failed_jobs" (
	"uuid"
);
CREATE INDEX IF NOT EXISTS "idx_cat_user" ON "categories" (
	"user_id"
);
CREATE INDEX IF NOT EXISTS "idx_jar_base_category_category_id" ON "jar_base_category" (
	"category_id"
);
CREATE INDEX IF NOT EXISTS "idx_jar_base_category_jar_id" ON "jar_base_category" (
	"jar_id"
);
CREATE INDEX IF NOT EXISTS "idx_jar_category_category_id" ON "jar_category" (
	"category_id"
);
CREATE INDEX IF NOT EXISTS "idx_jar_category_jar_id" ON "jar_category" (
	"jar_id"
);
CREATE INDEX IF NOT EXISTS "item_categories_parent_id_index" ON "item_categories" (
	"parent_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "jar_templates_slug_unique" ON "jar_templates" (
	"slug"
);
CREATE INDEX IF NOT EXISTS "jobs_queue_index" ON "jobs" (
	"queue"
);
CREATE UNIQUE INDEX IF NOT EXISTS "personal_access_tokens_token_unique" ON "personal_access_tokens" (
	"token"
);
CREATE INDEX IF NOT EXISTS "personal_access_tokens_tokenable_type_tokenable_id_index" ON "personal_access_tokens" (
	"tokenable_type",
	"tokenable_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "roles_slug_unique" ON "roles" (
	"slug"
);
CREATE INDEX IF NOT EXISTS "sessions_last_activity_index" ON "sessions" (
	"last_activity"
);
CREATE INDEX IF NOT EXISTS "sessions_user_id_index" ON "sessions" (
	"user_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "transaction_types_slug_unique" ON "transaction_types" (
	"slug"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_accfolders_user_parent_name" ON "account_folders" (
	"user_id",
	"parent_id",
	"name"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_cat_user_parent_name" ON "categories" (
	"user_id",
	"parent_id",
	"name"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_currencies_code" ON "currencies" (
	"code"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_itemcat_parent_name" ON "item_categories" (
	"parent_id",
	"name"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_jar_base_cat_del" ON "jar_base_category" (
	"jar_id",
	"category_id",
	"deleted_at"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_jar_cat_del" ON "jar_category" (
	"jar_id",
	"category_id",
	"deleted_at"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_jtj_cat" ON "jar_template_jar_categories" (
	"jar_template_jar_id",
	"category_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_jtjb" ON "jar_template_jar_base_categories" (
	"jar_template_jar_id",
	"category_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_jtjbc" ON "jar_tpljar_base_cat_tpl" (
	"jar_template_jar_id",
	"category_template_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "uniq_jtjc" ON "jar_tpljar_cat_tpl" (
	"jar_template_jar_id",
	"category_template_id"
);
CREATE INDEX IF NOT EXISTS "user_currencies_user_id_currency_id_index" ON "user_currencies" (
	"user_id",
	"currency_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "users_email_unique" ON "users" (
	"email"
);
COMMIT;
