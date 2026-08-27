-- ==================================================================
--  SH SERVICIOS - Importacion para InfinityFree (u otro hosting
--  compartido donde NO se pueden crear bases por SQL).
--
--  1) Crea la base desde el panel: Panel -> MySQL Databases.
--  2) Abri phpMyAdmin de InfinityFree y SELECCIONA esa base a la
--     izquierda (importante: no la dejes en 'server').
--  3) Pestana 'Importar' -> subi este archivo.
-- ==================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============ ESQUEMA (database.sql) ============

-- =====================================================================
--  SH SERVICIOS - Sistema de catálogo y gestión de maquinaria/repuestos
--  Esquema de base de datos (MySQL 5.7+ / MariaDB 10.4+ / XAMPP)
--  Motor: InnoDB · Charset: utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
-- ---------------------------------------------------------------------
-- 1. SEGURIDAD: roles, permisos y usuarios
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(60)  NOT NULL,
  `slug`        VARCHAR(60)  NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system`   TINYINT(1)   NOT NULL DEFAULT 0,
  `active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module`      VARCHAR(60)  NOT NULL,
  `name`        VARCHAR(120) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_slug` (`slug`),
  KEY `ix_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id`       INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `ix_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles` (`id`)       ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id`        INT UNSIGNED NOT NULL,
  `name`           VARCHAR(120) NOT NULL,
  `email`          VARCHAR(160) NOT NULL,
  `password`       VARCHAR(255) NOT NULL,
  `phone`          VARCHAR(40)  DEFAULT NULL,
  `avatar`         VARCHAR(255) DEFAULT NULL,
  `position`       VARCHAR(120) DEFAULT NULL,
  `active`         TINYINT(1)   NOT NULL DEFAULT 1,
  `must_change_pw` TINYINT(1)   NOT NULL DEFAULT 0,
  `failed_logins`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until`   DATETIME     DEFAULT NULL,
  `last_login_at`  DATETIME     DEFAULT NULL,
  `last_login_ip`  VARCHAR(45)  DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `ix_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. TAXONOMÍAS: categorías, marcas, etiquetas
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`   INT UNSIGNED DEFAULT NULL,
  `type`        ENUM('machine','spare_part','service') NOT NULL DEFAULT 'machine',
  `name`        VARCHAR(120) NOT NULL,
  `slug`        VARCHAR(140) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `icon`        VARCHAR(60)  DEFAULT NULL,
  `image`       VARCHAR(255) DEFAULT NULL,
  `meta_title`       VARCHAR(180) DEFAULT NULL,
  `meta_description` VARCHAR(300) DEFAULT NULL,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `featured`    TINYINT(1)   NOT NULL DEFAULT 0,
  `active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug_type` (`slug`,`type`),
  KEY `ix_categories_parent` (`parent_id`),
  KEY `ix_categories_type_active` (`type`,`active`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120) NOT NULL,
  `slug`        VARCHAR(140) NOT NULL,
  `logo`        VARCHAR(255) DEFAULT NULL,
  `description` TEXT         DEFAULT NULL,
  `website`     VARCHAR(255) DEFAULT NULL,
  `country`     VARCHAR(80)  DEFAULT NULL,
  `featured`    TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_brands_slug` (`slug`),
  KEY `ix_brands_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(60)  NOT NULL,
  `slug`       VARCHAR(80)  NOT NULL,
  `color`      VARCHAR(20)  NOT NULL DEFAULT 'accent',
  `icon`       VARCHAR(60)  DEFAULT NULL,
  `active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. PRODUCTOS (tabla base común a maquinaria y repuestos)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`              ENUM('machine','spare_part') NOT NULL,
  `code`              VARCHAR(60)  NOT NULL,
  `name`              VARCHAR(200) NOT NULL,
  `slug`              VARCHAR(220) NOT NULL,
  `brand_id`          INT UNSIGNED DEFAULT NULL,
  `category_id`       INT UNSIGNED DEFAULT NULL,
  `short_description` VARCHAR(400) DEFAULT NULL,
  `description`       MEDIUMTEXT   DEFAULT NULL,

  -- Precios (cost_price / profit_* NUNCA se exponen al público)
  `cost_price`        DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `profit_percent`    DECIMAL(7,3)  NOT NULL DEFAULT 0.000,
  `profit_amount`     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `final_price`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `offer_price`       DECIMAL(14,2) DEFAULT NULL,
  `currency`          CHAR(3)       NOT NULL DEFAULT 'ARS',
  `price_visible`     TINYINT(1)    NOT NULL DEFAULT 1,
  `price_updated_at`  DATETIME      DEFAULT NULL,

  -- Estado comercial
  `availability`      ENUM('disponible','reservada','vendida','mantenimiento','consultar')
                      NOT NULL DEFAULT 'disponible',
  `featured`          TINYINT(1)   NOT NULL DEFAULT 0,
  `is_new`            TINYINT(1)   NOT NULL DEFAULT 0,
  `is_offer`          TINYINT(1)   NOT NULL DEFAULT 0,

  -- Stock (usado principalmente por repuestos)
  `stock`             INT          NOT NULL DEFAULT 0,
  `stock_reserved`    INT          NOT NULL DEFAULT 0,
  `stock_min`         INT          NOT NULL DEFAULT 0,
  `track_stock`       TINYINT(1)   NOT NULL DEFAULT 0,

  -- SEO
  `meta_title`        VARCHAR(180) DEFAULT NULL,
  `meta_description`  VARCHAR(300) DEFAULT NULL,
  `og_image`          VARCHAR(255) DEFAULT NULL,

  -- Métricas
  `views`             INT UNSIGNED NOT NULL DEFAULT 0,
  `inquiries_count`   INT UNSIGNED NOT NULL DEFAULT 0,
  `quotes_count`      INT UNSIGNED NOT NULL DEFAULT 0,

  `active`            TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`        INT UNSIGNED DEFAULT NULL,
  `updated_by`        INT UNSIGNED DEFAULT NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`        DATETIME     DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_code` (`code`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `ix_products_type_active` (`type`,`active`,`deleted_at`),
  KEY `ix_products_brand` (`brand_id`),
  KEY `ix_products_category` (`category_id`),
  KEY `ix_products_featured` (`featured`),
  KEY `ix_products_price` (`final_price`),
  KEY `ix_products_views` (`views`),
  FULLTEXT KEY `ft_products_search` (`name`,`code`,`short_description`,`description`),
  CONSTRAINT `fk_products_brand`    FOREIGN KEY (`brand_id`)    REFERENCES `brands` (`id`)     ON DELETE SET NULL,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_creator`  FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `path`       VARCHAR(255) NOT NULL,
  `thumb_path` VARCHAR(255) DEFAULT NULL,
  `alt`        VARCHAR(200) DEFAULT NULL,
  `zone`       VARCHAR(60)  DEFAULT NULL COMMENT 'exterior, interior, motor, tablero, ruedas, horquillas, mastil, accesorios',
  `is_main`    TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_pi_product` (`product_id`,`sort_order`),
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_tags`;
CREATE TABLE `product_tags` (
  `product_id` INT UNSIGNED NOT NULL,
  `tag_id`     INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`tag_id`),
  KEY `ix_pt_tag` (`tag_id`),
  CONSTRAINT `fk_pt_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pt_tag`     FOREIGN KEY (`tag_id`)     REFERENCES `tags` (`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. CARACTERÍSTICAS TÉCNICAS DINÁMICAS (EAV configurable)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `features`;
CREATE TABLE `features` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120) NOT NULL,
  `slug`        VARCHAR(140) NOT NULL,
  `group_name`  VARCHAR(80)  NOT NULL DEFAULT 'General',
  `unit`        VARCHAR(20)  DEFAULT NULL COMMENT 'kg, t, mm, m, HP, kW, V, hs',
  `input_type`  ENUM('text','number','select','boolean') NOT NULL DEFAULT 'text',
  `options`     TEXT         DEFAULT NULL COMMENT 'JSON con opciones para input_type=select',
  `applies_to`  ENUM('machine','spare_part','both') NOT NULL DEFAULT 'machine',
  `filterable`  TINYINT(1)   NOT NULL DEFAULT 0,
  `comparable`  TINYINT(1)   NOT NULL DEFAULT 1,
  `public`      TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `active`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_features_slug` (`slug`),
  KEY `ix_features_applies` (`applies_to`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `feature_values`;
CREATE TABLE `feature_values` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`   INT UNSIGNED NOT NULL,
  `feature_id`   INT UNSIGNED NOT NULL,
  `value_text`   VARCHAR(255) DEFAULT NULL,
  `value_number` DECIMAL(16,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fv_product_feature` (`product_id`,`feature_id`),
  KEY `ix_fv_feature_number` (`feature_id`,`value_number`),
  CONSTRAINT `fk_fv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fv_feature` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. MAQUINARIA (extensión de products)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `machines`;
CREATE TABLE `machines` (
  `product_id`      INT UNSIGNED NOT NULL,
  `model`           VARCHAR(120) DEFAULT NULL,
  `year`            SMALLINT     DEFAULT NULL,
  `serial_number`   VARCHAR(80)  DEFAULT NULL,
  `condition_type`  ENUM('nuevo','usado','reacondicionado') NOT NULL DEFAULT 'usado',
  `hours`           INT          DEFAULT NULL,
  `fuel`            ENUM('electrico','diesel','nafta','gas','glp','hibrido','manual') DEFAULT NULL,
  `engine`          VARCHAR(120) DEFAULT NULL,
  `power_hp`        DECIMAL(8,2) DEFAULT NULL,
  `transmission`    VARCHAR(120) DEFAULT NULL,
  `capacity_kg`     DECIMAL(10,2) DEFAULT NULL,
  `lift_height_mm`  INT          DEFAULT NULL,
  `closed_height_mm` INT         DEFAULT NULL,
  `weight_kg`       DECIMAL(10,2) DEFAULT NULL,
  `length_mm`       INT          DEFAULT NULL,
  `width_mm`        INT          DEFAULT NULL,
  `turn_radius_mm`  INT          DEFAULT NULL,
  `battery`         VARCHAR(120) DEFAULT NULL,
  `voltage`         VARCHAR(40)  DEFAULT NULL,
  `mast_type`       VARCHAR(80)  DEFAULT NULL,
  `tire_type`       VARCHAR(80)  DEFAULT NULL,
  `location`        VARCHAR(160) DEFAULT NULL,
  `warranty`        VARCHAR(160) DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `ix_machines_model` (`model`),
  KEY `ix_machines_year` (`year`),
  KEY `ix_machines_capacity` (`capacity_kg`),
  KEY `ix_machines_fuel` (`fuel`),
  CONSTRAINT `fk_machines_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. REPUESTOS (extensión de products) + códigos + compatibilidad
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`    VARCHAR(120) NOT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `active`  TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `spare_parts`;
CREATE TABLE `spare_parts` (
  `product_id`         INT UNSIGNED NOT NULL,
  `oem_code`           VARCHAR(80)  DEFAULT NULL,
  `manufacturer_code`  VARCHAR(80)  DEFAULT NULL,
  `manufacturer`       VARCHAR(120) DEFAULT NULL,
  `origin`             ENUM('original','alternativo','remanufacturado') NOT NULL DEFAULT 'alternativo',
  `unit`               VARCHAR(20)  NOT NULL DEFAULT 'unidad',
  `weight_kg`          DECIMAL(10,3) DEFAULT NULL,
  `warehouse_id`       INT UNSIGNED DEFAULT NULL,
  `sector`             VARCHAR(60)  DEFAULT NULL,
  `shelf`              VARCHAR(60)  DEFAULT NULL,
  `position`           VARCHAR(60)  DEFAULT NULL,
  `lead_time_days`     SMALLINT     DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `ix_sp_oem` (`oem_code`),
  KEY `ix_sp_manufacturer_code` (`manufacturer_code`),
  KEY `ix_sp_warehouse` (`warehouse_id`),
  CONSTRAINT `fk_sp_product`   FOREIGN KEY (`product_id`)   REFERENCES `products` (`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_sp_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `spare_part_codes`;
CREATE TABLE `spare_part_codes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `code_type`  ENUM('interno','oem','fabricante','alternativo','cruzado') NOT NULL DEFAULT 'alternativo',
  `code`       VARCHAR(80)  NOT NULL,
  `brand_id`   INT UNSIGNED DEFAULT NULL,
  `note`       VARCHAR(160) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_spc_code` (`code`),
  KEY `ix_spc_product` (`product_id`),
  CONSTRAINT `fk_spc_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_spc_brand`   FOREIGN KEY (`brand_id`)   REFERENCES `brands` (`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatibilidad "libre": el repuesto sirve para una marca+modelo aunque
-- esa máquina no exista en el catálogo (caso muy común en repuestos).
DROP TABLE IF EXISTS `spare_part_compatibility`;
CREATE TABLE `spare_part_compatibility` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `spare_part_id` INT UNSIGNED NOT NULL,
  `brand_id`      INT UNSIGNED DEFAULT NULL,
  `model`         VARCHAR(120) NOT NULL,
  `year_from`     SMALLINT     DEFAULT NULL,
  `year_to`       SMALLINT     DEFAULT NULL,
  `note`          VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_spcomp_part` (`spare_part_id`),
  KEY `ix_spcomp_model` (`model`),
  CONSTRAINT `fk_spcomp_part`  FOREIGN KEY (`spare_part_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_spcomp_brand` FOREIGN KEY (`brand_id`)      REFERENCES `brands` (`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación explícita muchos-a-muchos máquina <-> repuesto del catálogo
DROP TABLE IF EXISTS `machine_spare_parts`;
CREATE TABLE `machine_spare_parts` (
  `machine_id`    INT UNSIGNED NOT NULL,
  `spare_part_id` INT UNSIGNED NOT NULL,
  `note`          VARCHAR(200) DEFAULT NULL,
  `recommended`   TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`machine_id`,`spare_part_id`),
  KEY `ix_msp_part` (`spare_part_id`),
  CONSTRAINT `fk_msp_machine` FOREIGN KEY (`machine_id`)    REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msp_part`    FOREIGN KEY (`spare_part_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. DOCUMENTACIÓN Y VIDEOS
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `title`      VARCHAR(180) NOT NULL,
  `doc_type`   ENUM('manual','ficha_tecnica','certificado','mantenimiento','compatibilidad','otro')
               NOT NULL DEFAULT 'otro',
  `path`       VARCHAR(255) NOT NULL,
  `mime`       VARCHAR(100) DEFAULT NULL,
  `size_bytes` INT UNSIGNED DEFAULT NULL,
  `public`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_documents_product` (`product_id`),
  CONSTRAINT `fk_documents_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `videos`;
CREATE TABLE `videos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `title`      VARCHAR(180) NOT NULL,
  `provider`   ENUM('youtube','vimeo','file') NOT NULL DEFAULT 'youtube',
  `video_ref`  VARCHAR(255) NOT NULL COMMENT 'ID de YouTube/Vimeo o ruta del archivo',
  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_videos_product` (`product_id`),
  CONSTRAINT `fk_videos_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. PRECIOS Y STOCK: historial y movimientos
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `price_history`;
CREATE TABLE `price_history` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`         INT UNSIGNED NOT NULL,
  `old_cost`           DECIMAL(14,2) DEFAULT NULL,
  `new_cost`           DECIMAL(14,2) DEFAULT NULL,
  `old_profit_percent` DECIMAL(7,3)  DEFAULT NULL,
  `new_profit_percent` DECIMAL(7,3)  DEFAULT NULL,
  `old_profit_amount`  DECIMAL(14,2) DEFAULT NULL,
  `new_profit_amount`  DECIMAL(14,2) DEFAULT NULL,
  `old_price`          DECIMAL(14,2) DEFAULT NULL,
  `new_price`          DECIMAL(14,2) DEFAULT NULL,
  `currency`           CHAR(3)       NOT NULL DEFAULT 'ARS',
  `reason`             VARCHAR(255)  DEFAULT NULL,
  `user_id`            INT UNSIGNED  DEFAULT NULL,
  `created_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_ph_product` (`product_id`,`created_at`),
  CONSTRAINT `fk_ph_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ph_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`   INT UNSIGNED NOT NULL,
  `type`         ENUM('entrada','salida','reserva','liberacion','ajuste','venta') NOT NULL,
  `quantity`     INT          NOT NULL,
  `stock_before` INT          NOT NULL DEFAULT 0,
  `stock_after`  INT          NOT NULL DEFAULT 0,
  `reason`       VARCHAR(255) DEFAULT NULL,
  `reference`    VARCHAR(80)  DEFAULT NULL COMMENT 'Nº de remito, cotización, OC',
  `user_id`      INT UNSIGNED DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_sm_product` (`product_id`,`created_at`),
  KEY `ix_sm_type` (`type`),
  CONSTRAINT `fk_sm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sm_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. FINANCIACIÓN
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE `payment_methods` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120) NOT NULL,
  `slug`        VARCHAR(140) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `icon`        VARCHAR(60)  DEFAULT NULL,
  `discount_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `active`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pm_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `financing_options`;
CREATE TABLE `financing_options` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_method_id`    INT UNSIGNED DEFAULT NULL,
  `name`                 VARCHAR(120) NOT NULL,
  `description`          VARCHAR(255) DEFAULT NULL,
  `down_payment_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `installments`         SMALLINT     NOT NULL DEFAULT 1,
  `interest_percent`     DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT 'Interés total sobre el saldo financiado',
  `interest_type`        ENUM('total','mensual') NOT NULL DEFAULT 'total',
  `min_amount`           DECIMAL(14,2) DEFAULT NULL,
  `max_amount`           DECIMAL(14,2) DEFAULT NULL,
  `currency`             CHAR(3)      NOT NULL DEFAULT 'ARS',
  `applies_to`           ENUM('machine','spare_part','both') NOT NULL DEFAULT 'both',
  `featured`             TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`           SMALLINT     NOT NULL DEFAULT 0,
  `active`               TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_fo_method` (`payment_method_id`),
  CONSTRAINT `fk_fo_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. COTIZACIONES
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `quotes`;
CREATE TABLE `quotes` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `number`             VARCHAR(20)  NOT NULL,
  `status`             ENUM('borrador','enviada','aceptada','rechazada','vencida') NOT NULL DEFAULT 'borrador',
  `source`             ENUM('web','admin') NOT NULL DEFAULT 'admin',

  `customer_name`      VARCHAR(160) NOT NULL,
  `customer_company`   VARCHAR(160) DEFAULT NULL,
  `customer_email`     VARCHAR(160) DEFAULT NULL,
  `customer_phone`     VARCHAR(40)  DEFAULT NULL,
  `customer_taxid`     VARCHAR(40)  DEFAULT NULL,
  `customer_address`   VARCHAR(255) DEFAULT NULL,

  `subtotal`           DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `discount_percent`   DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
  `discount_amount`    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `shipping_cost`      DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `other_costs`        DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `interest_amount`    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total`              DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `currency`           CHAR(3)       NOT NULL DEFAULT 'ARS',
  `exchange_rate`      DECIMAL(14,4) DEFAULT NULL,

  `financing_option_id` INT UNSIGNED DEFAULT NULL,
  `down_payment`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `installments`       SMALLINT      NOT NULL DEFAULT 0,
  `installment_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  `notes`              TEXT         DEFAULT NULL,
  `conditions`         TEXT         DEFAULT NULL,
  `valid_until`        DATE         DEFAULT NULL,
  `sent_at`            DATETIME     DEFAULT NULL,
  `responded_at`       DATETIME     DEFAULT NULL,
  `user_id`            INT UNSIGNED DEFAULT NULL,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quotes_number` (`number`),
  KEY `ix_quotes_status` (`status`,`created_at`),
  KEY `ix_quotes_user` (`user_id`),
  CONSTRAINT `fk_quotes_user`      FOREIGN KEY (`user_id`)             REFERENCES `users` (`id`)              ON DELETE SET NULL,
  CONSTRAINT `fk_quotes_financing` FOREIGN KEY (`financing_option_id`) REFERENCES `financing_options` (`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `quote_items`;
CREATE TABLE `quote_items` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote_id`         INT UNSIGNED NOT NULL,
  `product_id`       INT UNSIGNED DEFAULT NULL,
  `item_type`        ENUM('machine','spare_part','service','other') NOT NULL DEFAULT 'other',
  `code`             VARCHAR(60)  DEFAULT NULL,
  `description`      VARCHAR(255) NOT NULL,
  `quantity`         DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `discount_percent` DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
  `line_total`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `sort_order`       SMALLINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_qi_quote` (`quote_id`),
  KEY `ix_qi_product` (`product_id`),
  CONSTRAINT `fk_qi_quote`   FOREIGN KEY (`quote_id`)   REFERENCES `quotes` (`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_qi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `quote_payments`;
CREATE TABLE `quote_payments` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote_id`   INT UNSIGNED NOT NULL,
  `concept`    VARCHAR(160) NOT NULL,
  `amount`     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `due_date`   DATE         DEFAULT NULL,
  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_qp_quote` (`quote_id`),
  CONSTRAINT `fk_qp_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11. CONSULTAS / CONTACTO
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `inquiries`;
CREATE TABLE `inquiries` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(160) NOT NULL,
  `email`      VARCHAR(160) DEFAULT NULL,
  `phone`      VARCHAR(40)  DEFAULT NULL,
  `company`    VARCHAR(160) DEFAULT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `subject`    VARCHAR(200) DEFAULT NULL,
  `message`    TEXT         NOT NULL,
  `channel`    ENUM('web','whatsapp','email','telefono') NOT NULL DEFAULT 'web',
  `status`     ENUM('nueva','en_proceso','respondida','cerrada') NOT NULL DEFAULT 'nueva',
  `assigned_to` INT UNSIGNED DEFAULT NULL,
  `internal_note` TEXT      DEFAULT NULL,
  `ip`         VARCHAR(45)  DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `replied_at` DATETIME     DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_inq_status` (`status`,`created_at`),
  KEY `ix_inq_product` (`product_id`),
  CONSTRAINT `fk_inq_product`  FOREIGN KEY (`product_id`)  REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inq_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 12. SERVICIOS
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`             VARCHAR(160) NOT NULL,
  `slug`              VARCHAR(180) NOT NULL,
  `icon`              VARCHAR(60)  DEFAULT NULL,
  `image`             VARCHAR(255) DEFAULT NULL,
  `short_description` VARCHAR(300) DEFAULT NULL,
  `description`       MEDIUMTEXT   DEFAULT NULL,
  `bullets`           TEXT         DEFAULT NULL COMMENT 'JSON array de características',
  `featured`          TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`        SMALLINT     NOT NULL DEFAULT 0,
  `active`            TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_services_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 13. ANALÍTICA: vistas, búsquedas, favoritos
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `product_views`;
CREATE TABLE `product_views` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `ip_hash`    CHAR(64)     DEFAULT NULL,
  `referer`    VARCHAR(255) DEFAULT NULL,
  `device`     ENUM('desktop','mobile','tablet','bot','otro') NOT NULL DEFAULT 'otro',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_pv_product_date` (`product_id`,`created_at`),
  KEY `ix_pv_date` (`created_at`),
  CONSTRAINT `fk_pv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `search_logs`;
CREATE TABLE `search_logs` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `term`          VARCHAR(160) NOT NULL,
  `normalized`    VARCHAR(160) NOT NULL,
  `context`       ENUM('global','machine','spare_part') NOT NULL DEFAULT 'global',
  `results_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `ip_hash`       CHAR(64)     DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_sl_normalized` (`normalized`),
  KEY `ix_sl_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `favorites`;
CREATE TABLE `favorites` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`    INT UNSIGNED NOT NULL,
  `user_id`       INT UNSIGNED DEFAULT NULL,
  `session_token` CHAR(64)     DEFAULT NULL COMMENT 'Visitantes anónimos (localStorage sincronizado)',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav` (`product_id`,`user_id`,`session_token`),
  KEY `ix_fav_product` (`product_id`),
  CONSTRAINT `fk_fav_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 14. AUDITORÍA
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED DEFAULT NULL,
  `user_name`   VARCHAR(120) DEFAULT NULL COMMENT 'Copia por si se elimina el usuario',
  `action`      VARCHAR(60)  NOT NULL COMMENT 'login, logout, create, update, delete, price_change, stock_change...',
  `module`      VARCHAR(60)  NOT NULL,
  `entity_type` VARCHAR(60)  DEFAULT NULL,
  `entity_id`   INT UNSIGNED DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `data`        MEDIUMTEXT   DEFAULT NULL COMMENT 'JSON con los cambios',
  `ip`          VARCHAR(45)  DEFAULT NULL,
  `user_agent`  VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_al_user_date` (`user_id`,`created_at`),
  KEY `ix_al_module` (`module`,`action`),
  KEY `ix_al_entity` (`entity_type`,`entity_id`),
  KEY `ix_al_date` (`created_at`),
  CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 15. CONFIGURACIÓN Y MONEDAS
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_name`  VARCHAR(60)  NOT NULL DEFAULT 'general',
  `key_name`    VARCHAR(80)  NOT NULL,
  `value`       TEXT         DEFAULT NULL,
  `type`        ENUM('text','textarea','number','boolean','email','url','image','color','select','json')
                NOT NULL DEFAULT 'text',
  `options`     TEXT         DEFAULT NULL,
  `label`       VARCHAR(160) NOT NULL,
  `help`        VARCHAR(255) DEFAULT NULL,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key_name`),
  KEY `ix_settings_group` (`group_name`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `code`        CHAR(3)      NOT NULL,
  `name`        VARCHAR(60)  NOT NULL,
  `symbol`      VARCHAR(10)  NOT NULL,
  `rate_to_base` DECIMAL(16,6) NOT NULL DEFAULT 1.000000 COMMENT 'Cuántas unidades de la moneda base equivalen a 1 de ésta',
  `is_base`     TINYINT(1)   NOT NULL DEFAULT 0,
  `active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 16. PREPARACIÓN PARA E-COMMERCE FUTURO (estructura creada, sin uso)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(160) NOT NULL,
  `company`    VARCHAR(160) DEFAULT NULL,
  `email`      VARCHAR(160) NOT NULL,
  `password`   VARCHAR(255) DEFAULT NULL,
  `phone`      VARCHAR(40)  DEFAULT NULL,
  `taxid`      VARCHAR(40)  DEFAULT NULL,
  `address`    VARCHAR(255) DEFAULT NULL,
  `city`       VARCHAR(120) DEFAULT NULL,
  `province`   VARCHAR(120) DEFAULT NULL,
  `active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `number`      VARCHAR(20)  NOT NULL,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `quote_id`    INT UNSIGNED DEFAULT NULL,
  `status`      ENUM('pendiente','pagado','preparando','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `subtotal`    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `currency`    CHAR(3)       NOT NULL DEFAULT 'ARS',
  `payment_provider` VARCHAR(60) DEFAULT NULL,
  `payment_ref` VARCHAR(120) DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`number`),
  KEY `ix_orders_customer` (`customer_id`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_quote`    FOREIGN KEY (`quote_id`)    REFERENCES `quotes` (`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity`   DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `ix_oi_order` (`order_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders` (`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  DATOS BASE (imprescindibles para que el sistema funcione)
-- =====================================================================

-- Roles ---------------------------------------------------------------
INSERT INTO `roles` (`id`,`name`,`slug`,`description`,`is_system`) VALUES
 (1,'Administrador','admin','Acceso total al sistema',1),
 (2,'Operario','operario','Gestión de productos, stock, consultas y cotizaciones',1),
 (3,'Vendedor','vendedor','Cotizaciones y consultas, sin acceso a costos',0),
 (4,'Cliente','cliente','Rol reservado para cuentas de clientes (e-commerce futuro)',1);

-- Permisos ------------------------------------------------------------
INSERT INTO `permissions` (`module`,`name`,`slug`) VALUES
 ('dashboard','Ver dashboard','dashboard.view'),
 ('machines','Ver maquinaria','machines.view'),
 ('machines','Crear maquinaria','machines.create'),
 ('machines','Editar maquinaria','machines.edit'),
 ('machines','Eliminar maquinaria','machines.delete'),
 ('parts','Ver repuestos','parts.view'),
 ('parts','Crear repuestos','parts.create'),
 ('parts','Editar repuestos','parts.edit'),
 ('parts','Eliminar repuestos','parts.delete'),
 ('catalog','Gestionar categorías','categories.manage'),
 ('catalog','Gestionar marcas','brands.manage'),
 ('catalog','Gestionar características','features.manage'),
 ('catalog','Gestionar etiquetas','tags.manage'),
 ('catalog','Gestionar servicios','services.manage'),
 ('prices','Ver precio final','prices.view'),
 ('prices','Ver costo y ganancia','prices.view_cost'),
 ('prices','Editar precios','prices.edit'),
 ('prices','Ver historial de precios','prices.history'),
 ('stock','Ver stock','stock.view'),
 ('stock','Registrar movimientos','stock.move'),
 ('financing','Gestionar financiación','financing.manage'),
 ('quotes','Ver cotizaciones','quotes.view'),
 ('quotes','Crear cotizaciones','quotes.create'),
 ('quotes','Editar cotizaciones','quotes.edit'),
 ('quotes','Eliminar cotizaciones','quotes.delete'),
 ('inquiries','Ver consultas','inquiries.view'),
 ('inquiries','Gestionar consultas','inquiries.manage'),
 ('users','Ver usuarios','users.view'),
 ('users','Gestionar usuarios','users.manage'),
 ('users','Gestionar roles y permisos','roles.manage'),
 ('stats','Ver estadísticas','stats.view'),
 ('audit','Ver auditoría','audit.view'),
 ('settings','Gestionar configuración','settings.manage'),
 ('data','Importar datos','data.import'),
 ('data','Exportar datos','data.export');

-- Admin: todos los permisos
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
  SELECT 1, `id` FROM `permissions`;

-- Operario: todo menos usuarios/roles/configuración/costos/auditoría
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
  SELECT 2, `id` FROM `permissions`
  WHERE `slug` IN ('dashboard.view','machines.view','machines.create','machines.edit',
                   'parts.view','parts.create','parts.edit','categories.manage','brands.manage',
                   'features.manage','tags.manage','prices.view','prices.edit','prices.history',
                   'stock.view','stock.move','quotes.view','quotes.create','quotes.edit',
                   'inquiries.view','inquiries.manage','stats.view','data.import','data.export');

-- Vendedor: consulta y cotiza, sin tocar productos ni costos
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
  SELECT 3, `id` FROM `permissions`
  WHERE `slug` IN ('dashboard.view','machines.view','parts.view','prices.view','stock.view',
                   'quotes.view','quotes.create','quotes.edit','inquiries.view','inquiries.manage');

-- Usuario administrador inicial ---------------------------------------
-- Email: admin@shservicios.com.ar   Contraseña: Admin2026!
-- (hash generado con password_hash(..., PASSWORD_DEFAULT) — CAMBIAR AL INSTALAR)
INSERT INTO `users` (`id`,`role_id`,`name`,`email`,`password`,`position`,`active`,`must_change_pw`) VALUES
 (1,1,'Administrador','admin@shservicios.com.ar','$2y$12$70mRfcTgmOkQEXK2PzeIqeizRCdYzmDq0DdSh.8slzESLomly.XE6','Gerencia',1,1);

-- Monedas -------------------------------------------------------------
INSERT INTO `currencies` (`code`,`name`,`symbol`,`rate_to_base`,`is_base`,`active`) VALUES
 ('ARS','Peso argentino','$',1.000000,1,1),
 ('USD','Dólar estadounidense','USD',1000.000000,0,1);

-- Depósitos -----------------------------------------------------------
INSERT INTO `warehouses` (`id`,`name`,`address`,`active`) VALUES
 (1,'Depósito Central',NULL,1),
 (2,'Depósito Taller',NULL,1);

-- Métodos de pago -----------------------------------------------------
INSERT INTO `payment_methods` (`id`,`name`,`slug`,`description`,`icon`,`discount_percent`,`sort_order`,`active`) VALUES
 (1,'Contado','contado','Pago total al momento de la operación','bi-cash-coin',5.00,1,1),
 (2,'Transferencia bancaria','transferencia','Transferencia en pesos o dólares','bi-bank',3.00,2,1),
 (3,'Anticipo + cuotas','anticipo-cuotas','Entrega inicial y saldo en cuotas','bi-calendar-check',0.00,3,1),
 (4,'Financiación','financiacion','Plan de financiación propio','bi-graph-up-arrow',0.00,4,1),
 (5,'Mixto','mixto','Combinación de medios de pago','bi-sliders',0.00,5,1),
 (6,'Leasing','leasing','Operación de leasing con entidad financiera','bi-file-earmark-text',0.00,6,1),
 (7,'Consultar financiación','consultar','Planes a medida según la operación','bi-question-circle',0.00,7,1);

-- Planes de financiación ----------------------------------------------
INSERT INTO `financing_options`
 (`payment_method_id`,`name`,`description`,`down_payment_percent`,`installments`,`interest_percent`,`interest_type`,`min_amount`,`max_amount`,`applies_to`,`featured`,`sort_order`,`active`) VALUES
 (1,'Contado','Precio de lista con descuento por pago contado',100.00,1,0.00,'total',NULL,NULL,'both',1,1,1),
 (3,'30% + 6 cuotas','Anticipo del 30% y saldo en 6 cuotas',30.00,6,12.00,'total',1000000.00,NULL,'machine',1,2,1),
 (3,'30% + 12 cuotas','Anticipo del 30% y saldo en 12 cuotas',30.00,12,24.00,'total',1000000.00,NULL,'machine',1,3,1),
 (4,'50% + 18 cuotas','Anticipo del 50% y saldo financiado a 18 meses',50.00,18,32.00,'total',3000000.00,NULL,'machine',0,4,1),
 (4,'Repuestos en 3 cuotas','Compras de repuestos en 3 cuotas sin interés',0.00,3,0.00,'total',100000.00,3000000.00,'spare_part',1,5,1),
 (6,'Leasing 24 meses','Leasing con opción de compra a 24 meses',10.00,24,45.00,'total',5000000.00,NULL,'machine',0,6,1);

-- Etiquetas -----------------------------------------------------------
INSERT INTO `tags` (`name`,`slug`,`color`,`icon`,`sort_order`) VALUES
 ('Nuevo','nuevo','success','bi-stars',1),
 ('Usado','usado','secondary','bi-clock-history',2),
 ('Oferta','oferta','danger','bi-tag-fill',3),
 ('Destacado','destacado','accent','bi-star-fill',4),
 ('Eléctrico','electrico','info','bi-lightning-charge-fill',5),
 ('Diésel','diesel','dark','bi-fuel-pump-fill',6),
 ('Gas','gas','warning','bi-fire',7),
 ('Importado','importado','info','bi-globe-americas',8),
 ('Alta capacidad','alta-capacidad','dark','bi-arrows-expand',9),
 ('Bajo consumo','bajo-consumo','success','bi-droplet-half',10),
 ('Últimas unidades','ultimas-unidades','warning','bi-exclamation-triangle-fill',11);

-- Categorías de maquinaria --------------------------------------------
INSERT INTO `categories` (`id`,`parent_id`,`type`,`name`,`slug`,`description`,`icon`,`sort_order`,`featured`,`active`) VALUES
 (1,NULL,'machine','Autoelevadores','autoelevadores','Autoelevadores eléctricos, diésel y a gas para todo tipo de operación.','bi-truck-front-fill',1,1,1),
 (2,NULL,'machine','Apiladores','apiladores','Apiladores eléctricos y manuales para almacenamiento en altura.','bi-box-seam-fill',2,1,1),
 (3,NULL,'machine','Zorras eléctricas','zorras-electricas','Transpaletas manuales y eléctricas para movimiento de pallets.','bi-cart4',3,1,1),
 (4,NULL,'machine','Plataformas elevadoras','plataformas-elevadoras','Plataformas tijera y articuladas para trabajo en altura.','bi-arrow-up-square-fill',4,1,1),
 (5,NULL,'machine','Manipuladores telescópicos','manipuladores-telescopicos','Manipuladores telescópicos para carga en obra e industria.','bi-arrows-angle-expand',5,1,1),
 (6,NULL,'machine','Maquinaria de carga','maquinaria-de-carga','Palas cargadoras, minicargadoras y equipos de movimiento de materiales.','bi-truck-flatbed',6,0,1),
 (7,NULL,'machine','Maquinaria industrial','maquinaria-industrial','Equipos industriales para planta y producción.','bi-gear-wide-connected',7,0,1),
 (8,NULL,'machine','Equipamiento','equipamiento','Accesorios e implementos: pinzas, desplazadores, prolongadores.','bi-tools',8,0,1);

-- Categorías de repuestos ---------------------------------------------
INSERT INTO `categories` (`id`,`parent_id`,`type`,`name`,`slug`,`description`,`icon`,`sort_order`,`featured`,`active`) VALUES
 (20,NULL,'spare_part','Motores','motores','Componentes de motor: culatas, pistones, juntas y conjuntos.','bi-cpu-fill',1,1,1),
 (21,NULL,'spare_part','Transmisión','transmision','Cajas, convertidores, embragues y componentes de transmisión.','bi-gear-fill',2,1,1),
 (22,NULL,'spare_part','Frenos','frenos','Pastillas, cintas, bombas y kits de freno.','bi-record-circle-fill',3,1,1),
 (23,NULL,'spare_part','Dirección','direccion','Orbitrol, cilindros de dirección, terminales y rótulas.','bi-steering-wheel',4,0,1),
 (24,NULL,'spare_part','Hidráulica','hidraulica','Bombas, válvulas, cilindros y componentes hidráulicos.','bi-droplet-fill',5,1,1),
 (25,NULL,'spare_part','Electricidad','electricidad','Alternadores, arranques, contactores, cableado y tableros.','bi-lightning-fill',6,1,1),
 (26,NULL,'spare_part','Baterías','baterias','Baterías de tracción, cargadores y accesorios.','bi-battery-full',7,1,1),
 (27,NULL,'spare_part','Filtros','filtros','Filtros de aceite, aire, combustible e hidráulico.','bi-funnel-fill',8,1,1),
 (28,NULL,'spare_part','Rodamientos','rodamientos','Rodamientos, retenes y bujes.','bi-circle-half',9,0,1),
 (29,NULL,'spare_part','Neumáticos','neumaticos','Neumáticos sólidos, neumáticos con cámara y bandas.','bi-circle-fill',10,1,1),
 (30,NULL,'spare_part','Horquillas','horquillas','Uñas y horquillas de todas las medidas y capacidades.','bi-distribute-horizontal',11,0,1),
 (31,NULL,'spare_part','Mástiles','mastiles','Componentes de mástil: cadenas, rodillos, perfiles.','bi-align-bottom',12,0,1),
 (32,NULL,'spare_part','Cilindros','cilindros','Cilindros de elevación, inclinación y dirección.','bi-usb-plug-fill',13,0,1),
 (33,NULL,'spare_part','Bombas','bombas','Bombas hidráulicas, de agua y de combustible.','bi-fan',14,0,1),
 (34,NULL,'spare_part','Mangueras','mangueras','Mangueras hidráulicas, conexiones y racores.','bi-bezier2',15,0,1),
 (35,NULL,'spare_part','Sensores','sensores','Sensores de temperatura, presión, nivel y velocidad.','bi-broadcast-pin',16,0,1),
 (36,NULL,'spare_part','Repuestos de motor','repuestos-de-motor','Kits, correas, bujías, inyectores y accesorios de motor.','bi-nut-fill',17,0,1),
 (37,NULL,'spare_part','Repuestos de transmisión','repuestos-de-transmision','Discos, juntas, ejes y componentes de transmisión.','bi-hexagon-fill',18,0,1),
 (38,NULL,'spare_part','Otros','otros-repuestos','Repuestos varios y accesorios generales.','bi-three-dots',19,0,1);

-- Características técnicas dinámicas ----------------------------------
INSERT INTO `features`
 (`name`,`slug`,`group_name`,`unit`,`input_type`,`applies_to`,`filterable`,`comparable`,`public`,`sort_order`) VALUES
 ('Capacidad de carga','capacidad-de-carga','Capacidades','kg','number','machine',1,1,1,1),
 ('Altura máxima de elevación','altura-maxima','Capacidades','mm','number','machine',1,1,1,2),
 ('Altura replegada','altura-replegada','Dimensiones','mm','number','machine',0,1,1,3),
 ('Centro de carga','centro-de-carga','Capacidades','mm','number','machine',0,1,1,4),
 ('Peso operativo','peso-operativo','Dimensiones','kg','number','machine',0,1,1,5),
 ('Largo total','largo-total','Dimensiones','mm','number','machine',0,1,1,6),
 ('Ancho total','ancho-total','Dimensiones','mm','number','machine',0,1,1,7),
 ('Radio de giro','radio-de-giro','Dimensiones','mm','number','machine',0,1,1,8),
 ('Combustible','combustible','Motorización',NULL,'select','machine',1,1,1,9),
 ('Motor','motor','Motorización',NULL,'text','machine',0,1,1,10),
 ('Potencia','potencia','Motorización','HP','number','machine',1,1,1,11),
 ('Transmisión','transmision-feature','Motorización',NULL,'text','machine',0,1,1,12),
 ('Horas de uso','horas-de-uso','Estado','hs','number','machine',1,1,1,13),
 ('Tipo de batería','tipo-de-bateria','Motorización',NULL,'text','machine',0,1,1,14),
 ('Voltaje','voltaje','Motorización','V','number','machine',1,1,1,15),
 ('Tipo de mástil','tipo-de-mastil','Mástil',NULL,'text','machine',0,1,1,16),
 ('Tipo de rueda','tipo-de-rueda','Tren rodante',NULL,'text','machine',0,1,1,17),
 ('Velocidad de traslación','velocidad-traslacion','Rendimiento','km/h','number','machine',0,1,1,18),
 ('Material','material','Especificaciones',NULL,'text','spare_part',0,1,1,30),
 ('Medida','medida','Especificaciones',NULL,'text','spare_part',0,1,1,31),
 ('Rosca','rosca','Especificaciones',NULL,'text','spare_part',0,1,1,32),
 ('Diámetro','diametro','Especificaciones','mm','number','spare_part',0,1,1,33),
 ('Garantía','garantia','Comercial',NULL,'text','both',0,1,1,40);

UPDATE `features` SET `options` = '["Eléctrico","Diésel","Nafta","Gas","GLP","Híbrido","Manual"]' WHERE `slug` = 'combustible';

-- Servicios -----------------------------------------------------------
INSERT INTO `services` (`title`,`slug`,`icon`,`short_description`,`description`,`bullets`,`featured`,`sort_order`,`active`) VALUES
 ('Venta','venta','bi-cash-stack','Maquinaria nueva y usada con garantía y respaldo técnico.','Comercializamos autoelevadores, apiladores, zorras y plataformas de las principales marcas del mercado, nuevos y usados, con revisión técnica previa a la entrega y garantía escrita.','["Equipos nuevos y usados revisados","Garantía escrita","Entrega en todo el país","Asesoramiento técnico previo"]',1,1,1),
 ('Alquiler','alquiler','bi-calendar-range','Alquiler por día, mes o largo plazo con asistencia incluida.','Planes de alquiler flexibles con mantenimiento preventivo incluido y equipo de reemplazo ante cualquier eventualidad.','["Corto y largo plazo","Mantenimiento incluido","Equipo de reemplazo","Facturación mensual"]',1,2,1),
 ('Mantenimiento preventivo','mantenimiento','bi-clipboard-check','Planes de mantenimiento programado para reducir paradas.','Programas de mantenimiento preventivo con planillas de control, repuestos originales y registro digital de cada intervención.','["Planes mensuales o por horas","Repuestos originales","Informe por equipo","Historial digital"]',1,3,1),
 ('Service técnico','service','bi-wrench-adjustable-circle','Service integral en taller propio y a domicilio.','Taller equipado y unidades móviles para atención en planta. Diagnóstico electrónico, hidráulico y mecánico.','["Taller propio","Unidades móviles","Diagnóstico electrónico","Presupuesto sin cargo"]',1,4,1),
 ('Reparación','reparacion','bi-tools','Reparación integral de mástiles, motores y sistemas hidráulicos.','Reparamos y reacondicionamos equipos completos: motor, transmisión, hidráulica, mástiles y sistemas eléctricos.','["Rectificación de motores","Reparación de mástiles","Hidráulica y cilindros","Sistemas eléctricos"]',0,5,1),
 ('Repuestos','repuestos','bi-nut','Stock permanente de repuestos originales y alternativos.','Amplio stock de repuestos con búsqueda por código OEM y compatibilidad verificada por modelo de máquina.','["Búsqueda por código OEM","Originales y alternativos","Envíos a todo el país","Compatibilidad verificada"]',1,6,1),
 ('Transporte','transporte','bi-truck','Logística propia para el traslado de equipos.','Contamos con carretones y camiones con hidrogrúa para el traslado seguro de maquinaria.','["Carretones propios","Cobertura nacional","Seguro de carga","Coordinación de entrega"]',0,7,1),
 ('Asesoramiento','asesoramiento','bi-people-fill','Te ayudamos a elegir el equipo correcto para tu operación.','Analizamos tu operación (cargas, alturas, pasillos, turnos) y recomendamos la configuración óptima.','["Análisis de la operación","Estudio de pasillos y alturas","Comparativa de equipos","Sin cargo"]',1,8,1),
 ('Inspección y certificación','inspeccion','bi-shield-check','Inspecciones técnicas y certificación de equipos de izaje.','Inspección técnica documentada según normativa vigente para equipos de elevación y movimiento de cargas.','["Informe técnico","Certificado","Checklist documentado","Recomendaciones"]',0,9,1),
 ('Capacitación','capacitacion','bi-mortarboard-fill','Formación de operadores y seguridad en el manejo de cargas.','Cursos teórico-prácticos de manejo seguro de autoelevadores y equipos de elevación, con certificado.','["Teórico y práctico","En tu planta","Certificado por participante","Material didáctico"]',0,10,1);

-- Configuración del sitio ---------------------------------------------
INSERT INTO `site_settings` (`group_name`,`key_name`,`value`,`type`,`label`,`help`,`sort_order`) VALUES
 ('empresa','company_name','SH Servicios','text','Nombre de la empresa',NULL,1),
 ('empresa','company_legal','SH Servicios','text','Razón social',NULL,2),
 ('empresa','company_taxid','','text','CUIT',NULL,3),
 ('empresa','company_slogan','Soluciones para la industria y el movimiento de cargas','text','Eslogan',NULL,4),
 ('empresa','company_description','Venta, alquiler y service de autoelevadores, maquinaria de carga, maquinaria industrial y repuestos. Taller propio y asistencia técnica.','textarea','Descripción institucional',NULL,5),
 ('empresa','company_logo','','image','Logo','Se muestra en el sitio y en los PDF',6),
 ('empresa','company_founded','','text','Año de fundación',NULL,7),

 ('contacto','contact_phone','','text','Teléfono',NULL,1),
 ('contacto','contact_whatsapp','5491100000000','text','WhatsApp','Solo números, con código de país y área. Ej: 5491122334455',2),
 ('contacto','contact_email','ventas@shservicios.com.ar','email','Email de ventas',NULL,3),
 ('contacto','contact_email_parts','repuestos@shservicios.com.ar','email','Email de repuestos',NULL,4),
 ('contacto','contact_address','','text','Dirección',NULL,5),
 ('contacto','contact_city','','text','Ciudad / Provincia',NULL,6),
 ('contacto','contact_hours','Lunes a viernes de 8:00 a 18:00 · Sábados de 9:00 a 13:00','textarea','Horarios de atención',NULL,7),
 ('contacto','contact_map_embed','','textarea','Mapa (URL de Google Maps embed)','Pegá solo la URL del iframe de Google Maps',8),
 ('contacto','social_facebook','','url','Facebook',NULL,9),
 ('contacto','social_instagram','','url','Instagram',NULL,10),
 ('contacto','social_linkedin','','url','LinkedIn',NULL,11),
 ('contacto','social_youtube','','url','YouTube',NULL,12),

 ('catalogo','items_per_page','12','number','Productos por página',NULL,1),
 ('catalogo','show_prices_public','1','boolean','Mostrar precios en el sitio público',NULL,2),
 ('catalogo','show_stock_public','1','boolean','Mostrar estado de stock en el sitio público',NULL,3),
 ('catalogo','low_stock_threshold','3','number','Umbral de stock bajo','Se usa cuando el repuesto no define su propio mínimo',4),
 ('catalogo','compare_max','3','number','Máximo de máquinas a comparar',NULL,5),
 ('catalogo','price_display_mode','both','select','Cómo mostrar el precio',NULL,6),

 ('moneda','base_currency','ARS','select','Moneda base',NULL,1),
 ('moneda','usd_rate','1000','number','Cotización del dólar','Cuántos pesos equivalen a 1 USD. Se usa en toda la conversión.',2),
 ('moneda','show_dual_currency','1','boolean','Mostrar precio en ambas monedas',NULL,3),
 ('moneda','usd_rate_auto','0','boolean','Actualizar la cotización del dólar automáticamente','Toma el valor de lanacion.com.ar cada tantas horas. Si lo activás, el campo "Cotización del dólar" se completa solo.',4),
 ('moneda','usd_rate_source','blue','select','Qué dólar usar','Cuál de las cotizaciones de lanacion.com.ar se aplica a los precios.',5),
 ('moneda','usd_rate_ttl_hours','12','number','Cada cuántas horas actualizar','Con qué frecuencia se vuelve a consultar lanacion.com.ar.',6),

 ('cotizaciones','quote_prefix','COT-','text','Prefijo de cotización',NULL,1),
 ('cotizaciones','quote_padding','6','number','Dígitos del número de cotización',NULL,2),
 ('cotizaciones','quote_validity_days','15','number','Días de validez por defecto',NULL,3),
 ('cotizaciones','quote_conditions','Los precios expresados no incluyen IVA. Precios sujetos a modificación sin previo aviso. Entrega sujeta a disponibilidad de stock al momento de la confirmación. Flete y puesta en marcha a convenir según destino.','textarea','Condiciones por defecto',NULL,4),
 ('cotizaciones','quote_footer','Gracias por confiar en nosotros.','text','Pie del PDF',NULL,5),

 ('seo','seo_title','SH Servicios · Autoelevadores, maquinaria y repuestos','text','Título del sitio',NULL,1),
 ('seo','seo_description','Venta y alquiler de autoelevadores, apiladores, plataformas y maquinaria industrial. Repuestos con búsqueda por código OEM y compatibilidad por modelo.','textarea','Meta descripción',NULL,2),
 ('seo','seo_keywords','autoelevadores, montacargas, repuestos autoelevador, apiladores, zorras eléctricas, maquinaria industrial','text','Palabras clave',NULL,3),
 ('seo','site_url','','url','URL pública del sitio','Se usa en sitemap.xml y en los enlaces de los PDF',4),
 ('seo','google_analytics','','text','ID de Google Analytics',NULL,5),

 ('sistema','maintenance_mode','0','boolean','Modo mantenimiento',NULL,1),
 ('sistema','enable_online_sales','0','boolean','Habilitar venta online','Reservado para la futura etapa de e-commerce',2),
 ('sistema','track_views','1','boolean','Registrar visitas de productos',NULL,3),
 ('sistema','track_searches','1','boolean','Registrar búsquedas',NULL,4);

UPDATE `site_settings` SET `options`='["ARS","USD"]' WHERE `key_name`='base_currency';
UPDATE `site_settings` SET `options`='{"ars":"Solo pesos","usd":"Solo dólares","both":"Ambas monedas"}' WHERE `key_name`='price_display_mode';
UPDATE `site_settings` SET `options`='{"oficial":"Oficial","blue":"Blue","mep":"MEP","ccl":"Contado con liqui (CCL)","tarjeta":"Tarjeta / turista"}' WHERE `key_name`='usd_rate_source';


-- ============ DATOS DE EJEMPLO (seed.sql) ============

-- =====================================================================
--  SH SERVICIOS · DATOS DE PRUEBA (demo)
--  Ejecutar DESPUÉS de database.sql
--  ¡ATENCIÓN! Son datos ficticios de demostración. Borralos antes
--  de poner el sistema en producción:  CALL sp_limpiar_demo();  o
--  simplemente eliminá los productos desde el panel.
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Usuarios de prueba ---------------------------------------------------
-- operario@shservicios.com.ar / Operario2026!
-- vendedor@shservicios.com.ar / Vendedor2026!
INSERT INTO `users` (`id`,`role_id`,`name`,`email`,`password`,`position`,`phone`,`active`) VALUES
 (2,2,'Juan Pérez','operario@shservicios.com.ar','$2y$12$acdJ94JVDUpj9H7ikplIr.Iaty/9nL2HvBDVWHv2fabRes2i0aDzG','Jefe de taller','11-4000-0002',1),
 (3,3,'Carla Gómez','vendedor@shservicios.com.ar','$2y$12$JFDBw.Yn75uMGRZyvHm2IOCYTUxg54OLtsuLbu17uBW7eFfCmut3O','Ventas','11-4000-0003',1);

-- Marcas ---------------------------------------------------------------
INSERT INTO `brands` (`id`,`name`,`slug`,`description`,`website`,`country`,`featured`,`sort_order`,`active`) VALUES
 (1,'Toyota','toyota','Líder mundial en equipos de movimiento de materiales.','https://toyota-forklifts.com','Japón',1,1,1),
 (2,'Hyster','hyster','Equipos robustos para aplicaciones exigentes.','https://hyster.com','Estados Unidos',1,2,1),
 (3,'Yale','yale','Autoelevadores y equipos de almacenamiento.','https://yale.com','Estados Unidos',1,3,1),
 (4,'Caterpillar','caterpillar','Maquinaria pesada e industrial.','https://cat.com','Estados Unidos',1,4,1),
 (5,'Komatsu','komatsu','Equipos industriales y de construcción.','https://komatsu.com','Japón',1,5,1),
 (6,'Mitsubishi','mitsubishi','Autoelevadores y equipos de almacén.','https://mitforklift.com','Japón',1,6,1),
 (7,'Linde','linde','Tecnología hidrostática de alto rendimiento.','https://linde-mh.com','Alemania',1,7,1),
 (8,'Jungheinrich','jungheinrich','Especialistas en intralogística.','https://jungheinrich.com','Alemania',1,8,1),
 (9,'Nissan','nissan','Autoelevadores industriales.','https://nissanforklift.com','Japón',0,9,1),
 (10,'Clark','clark','Pioneros del autoelevador.','https://clarkmhc.com','Estados Unidos',0,10,1),
 (11,'Genie','genie','Plataformas de elevación de personas.','https://genielift.com','Estados Unidos',0,11,1),
 (12,'Genérico','generico','Repuestos alternativos de fabricación nacional e importada.',NULL,NULL,0,20,1);

-- =====================================================================
-- MAQUINARIA
-- =====================================================================
INSERT INTO `products`
 (`id`,`type`,`code`,`name`,`slug`,`brand_id`,`category_id`,`short_description`,`description`,
  `cost_price`,`profit_percent`,`profit_amount`,`final_price`,`currency`,`price_visible`,
  `availability`,`featured`,`is_new`,`is_offer`,`offer_price`,`stock`,`track_stock`,`views`,`active`,`created_by`) VALUES

 (1,'machine','AE-001','Autoelevador Toyota 8FG25 2.500 kg','autoelevador-toyota-8fg25-2500-kg',1,1,
  'Autoelevador a gas de 2.500 kg de capacidad, mástil triple de 4.700 mm y desplazador lateral.',
  'Autoelevador Toyota serie 8 con motor 4Y a gas, uno de los equipos más confiables del mercado para operación continua en depósitos y plantas. Revisado íntegramente en nuestro taller: motor, transmisión, sistema hidráulico y frenos. Incluye desplazador lateral hidráulico y mástil triple con visión libre. Se entrega con garantía escrita de 6 meses y service inicial sin cargo.',
  16000000.00,25.000,4000000.00,20000000.00,'ARS',1,'disponible',1,0,0,NULL,1,0,1245,1,1),

 (2,'machine','AE-002','Autoelevador Toyota 8FD30 3.000 kg diésel','autoelevador-toyota-8fd30-3000-kg-diesel',1,1,
  'Autoelevador diésel de 3.000 kg, mástil triple 4.500 mm, ideal para exterior.',
  'Equipo diésel de 3 toneladas con motor Toyota 1DZ-III, pensado para operación en exteriores, patios de maniobra y superficies irregulares. Cuenta con cabina con techo protector, luces de trabajo LED y neumáticos neumáticos nuevos. Mantenimiento al día con historial documentado.',
  22400000.00,25.000,5600000.00,28000000.00,'ARS',1,'disponible',1,0,1,25900000.00,1,0,876,1,1),

 (3,'machine','AE-003','Autoelevador eléctrico Hyster E30XN 1.400 kg','autoelevador-electrico-hyster-e30xn-1400-kg',2,1,
  'Autoelevador eléctrico contrabalanceado, batería 48 V, ideal para uso interior.',
  'Autoelevador eléctrico de tres ruedas con excelente maniobrabilidad en pasillos estrechos. Batería de tracción de 48 V con 80% de capacidad remanente y cargador incluido. Cero emisiones: apto para industria alimenticia, farmacéutica y depósitos cerrados.',
  19200000.00,25.000,4800000.00,24000000.00,'ARS',1,'disponible',1,0,0,NULL,1,0,654,1,1),

 (4,'machine','AE-004','Autoelevador Yale GLP050 2.300 kg','autoelevador-yale-glp050-2300-kg',3,1,
  'Autoelevador a GLP de 2.300 kg con mástil dúplex de 3.300 mm.',
  'Unidad Yale a gas licuado, de bajo consumo y mantenimiento sencillo. Recomendado para operaciones mixtas interior/exterior. Se entrega con dos tubos de GLP y kit de conversión revisado.',
  13600000.00,25.000,3400000.00,17000000.00,'ARS',1,'reservada',0,0,0,NULL,1,0,412,1,1),

 (5,'machine','AP-001','Apilador eléctrico Jungheinrich EJC 112 1.200 kg','apilador-electrico-jungheinrich-ejc-112',8,2,
  'Apilador eléctrico conductor acompañante, 1.200 kg y 3.300 mm de elevación.',
  'Apilador eléctrico compacto ideal para almacenes con espacio reducido. Timón ergonómico con control de velocidad progresivo, batería de 24 V y cargador incorporado. Muy bajo nivel sonoro.',
  8000000.00,30.000,2400000.00,10400000.00,'ARS',1,'disponible',1,1,0,NULL,2,0,533,1,1),

 (6,'machine','AP-002','Apilador manual hidráulico 1.000 kg','apilador-manual-hidraulico-1000-kg',12,2,
  'Apilador manual de 1.000 kg con elevación de 1.600 mm.',
  'Apilador manual con bomba hidráulica de doble pistón. Equipo nuevo, sin uso, con garantía de fábrica de 12 meses. Solución económica para movimientos ocasionales.',
  2400000.00,35.000,840000.00,3240000.00,'ARS',1,'disponible',0,1,0,NULL,4,0,298,1,1),

 (7,'machine','ZE-001','Zorra eléctrica Linde T20 2.000 kg','zorra-electrica-linde-t20-2000-kg',7,3,
  'Transpaleta eléctrica de 2.000 kg con batería de litio.',
  'Transpaleta eléctrica Linde T20 con batería de litio de carga rápida (30 minutos al 80%). Reduce drásticamente el esfuerzo del operador y aumenta la productividad en la carga y descarga de camiones.',
  5600000.00,30.000,1680000.00,7280000.00,'ARS',1,'disponible',1,0,0,NULL,3,0,721,1,1),

 (8,'machine','ZE-002','Zorra hidráulica manual 2.500 kg','zorra-hidraulica-manual-2500-kg',12,3,
  'Transpaleta manual reforzada de 2.500 kg, uñas de 1.150 mm.',
  'Transpaleta manual de uso intensivo con ruedas de nylon y timón de tres posiciones. Equipo nuevo con garantía.',
  600000.00,40.000,240000.00,840000.00,'ARS',1,'disponible',0,1,0,NULL,12,1,489,1,1),

 (9,'machine','PL-001','Plataforma elevadora tijera Genie GS-1932','plataforma-elevadora-tijera-genie-gs-1932',11,4,
  'Plataforma tijera eléctrica, 7,80 m de altura de trabajo, 227 kg.',
  'Plataforma tipo tijera eléctrica para trabajo en interiores. Altura de trabajo de 7,80 m, plataforma extensible y tracción en dos ruedas. Ideal para mantenimiento edilicio, montaje e instalaciones.',
  14400000.00,25.000,3600000.00,18000000.00,'ARS',1,'disponible',1,0,0,NULL,1,0,602,1,1),

 (10,'machine','MT-001','Manipulador telescópico Caterpillar TH255C','manipulador-telescopico-caterpillar-th255c',4,5,
  'Manipulador telescópico 2.500 kg, 5,60 m de alcance en altura.',
  'Manipulador telescópico compacto con tracción 4x4 y dirección en cuatro ruedas. Excelente para obra, agro e industria. Incluye pala y horquillas.',
  36000000.00,22.000,7920000.00,43920000.00,'ARS',1,'consultar',0,0,0,NULL,1,0,341,1,1),

 (11,'machine','MC-001','Minicargadora Komatsu SK820-5','minicargadora-komatsu-sk820-5',5,6,
  'Minicargadora de 900 kg de capacidad operativa.',
  'Minicargadora compacta con motor diésel de 74 HP, sistema hidráulico auxiliar y cabina cerrada con aire acondicionado. Apta para múltiples implementos.',
  25600000.00,25.000,6400000.00,32000000.00,'ARS',1,'mantenimiento',0,0,0,NULL,1,0,187,1,1),

 (12,'machine','EQ-001','Pinza para bobinas hidráulica','pinza-para-bobinas-hidraulica',12,8,
  'Implemento hidráulico para manipulación de bobinas de papel.',
  'Pinza hidráulica para bobinas con apertura de 300 a 1.500 mm, montaje clase II. Compatible con la mayoría de los autoelevadores de 2 a 3 toneladas.',
  4400000.00,30.000,1320000.00,5720000.00,'ARS',1,'disponible',0,0,0,NULL,2,0,156,1,1);

INSERT INTO `machines`
 (`product_id`,`model`,`year`,`condition_type`,`hours`,`fuel`,`engine`,`power_hp`,`transmission`,
  `capacity_kg`,`lift_height_mm`,`closed_height_mm`,`weight_kg`,`length_mm`,`width_mm`,`turn_radius_mm`,
  `battery`,`voltage`,`mast_type`,`tire_type`,`location`,`warranty`) VALUES
 (1,'8FG25',2018,'usado',6200,'gas','Toyota 4Y 2.2L',52.00,'Automática (convertidor)',2500,4700,2100,3800,3695,1150,2200,NULL,NULL,'Triple visión libre','Neumático','Depósito Central','6 meses'),
 (2,'8FD30',2019,'usado',4800,'diesel','Toyota 1DZ-III 2.5L',54.00,'Automática (convertidor)',3000,4500,2200,4400,3830,1250,2350,NULL,NULL,'Triple','Neumático','Depósito Central','6 meses'),
 (3,'E30XN',2020,'usado',3100,'electrico','Motor AC 48V',NULL,'Eléctrica AC',1400,4500,2000,3200,3050,1070,1600,'Tracción 48V/625Ah','48','Triple visión libre','Cushion','Depósito Central','6 meses'),
 (4,'GLP050',2016,'usado',9400,'glp','Yale 2.0L GLP',48.00,'Automática',2300,3300,1990,3450,3500,1140,2150,NULL,NULL,'Dúplex','Neumático','Depósito Taller','3 meses'),
 (5,'EJC 112',2021,'usado',1800,'electrico','Motor AC 24V',NULL,'Eléctrica AC',1200,3300,1990,1150,1800,800,1520,'Tracción 24V/250Ah','24','Dúplex','Poliuretano','Depósito Central','6 meses'),
 (6,'MH-1016',2026,'nuevo',0,'manual',NULL,NULL,'Manual',1000,1600,1900,320,1650,800,1300,NULL,NULL,'Simple','Nylon','Depósito Central','12 meses'),
 (7,'T20',2022,'usado',900,'electrico','Motor AC 24V litio',NULL,'Eléctrica AC',2000,205,1250,520,1750,720,1550,'Litio 24V/205Ah','24',NULL,'Poliuretano','Depósito Central','6 meses'),
 (8,'ZM-2500',2026,'nuevo',0,'manual',NULL,NULL,'Manual',2500,200,1220,78,1550,685,1400,NULL,NULL,NULL,'Nylon','Depósito Central','12 meses'),
 (9,'GS-1932',2017,'usado',2400,'electrico','Motor DC 24V',NULL,'Eléctrica DC',227,5800,1980,1520,1830,810,NULL,'Tracción 24V/220Ah','24',NULL,'Antihuella','Depósito Taller','3 meses'),
 (10,'TH255C',2018,'usado',3900,'diesel','Perkins 404D-22T',74.00,'Hidrostática',2500,5600,1900,4900,4090,1810,3400,NULL,NULL,'Telescópico','Neumático','Depósito Taller','3 meses'),
 (11,'SK820-5',2015,'usado',5600,'diesel','Komatsu 3D88E',74.00,'Hidrostática',900,NULL,1990,3100,3350,1550,NULL,NULL,NULL,NULL,'Neumático','Depósito Taller',NULL),
 (12,'PB-1500',2026,'nuevo',NULL,NULL,NULL,NULL,NULL,2000,NULL,NULL,420,NULL,1500,NULL,NULL,NULL,NULL,NULL,'Depósito Central','12 meses');

-- =====================================================================
-- REPUESTOS
-- =====================================================================
INSERT INTO `products`
 (`id`,`type`,`code`,`name`,`slug`,`brand_id`,`category_id`,`short_description`,`description`,
  `cost_price`,`profit_percent`,`profit_amount`,`final_price`,`currency`,`price_visible`,
  `availability`,`featured`,`is_new`,`is_offer`,`offer_price`,`stock`,`stock_reserved`,`stock_min`,`track_stock`,`views`,`active`,`created_by`) VALUES

 (101,'spare_part','FIL-00125','Filtro de aceite motor Toyota serie 8','filtro-de-aceite-motor-toyota-serie-8',1,27,
  'Filtro de aceite para motores Toyota 4Y y 1DZ de la serie 8.',
  'Filtro de aceite de flujo total con válvula antirretorno. Recomendado para cambio cada 250 horas de operación. Compatible con la mayoría de los autoelevadores Toyota serie 7 y 8 con motor 4Y (gas/nafta) y 1DZ (diésel).',
  12000.00,60.000,7200.00,19200.00,'ARS',1,'disponible',1,0,0,NULL,24,2,5,1,342,1,1),

 (102,'spare_part','FIL-00210','Filtro de aire primario autoelevador 2-3 t','filtro-de-aire-primario-autoelevador-2-3-t',12,27,
  'Filtro de aire primario para autoelevadores de 2 a 3 toneladas.',
  'Elemento filtrante de papel plisado de alta eficiencia. Fundamental para prolongar la vida del motor en ambientes con polvo. Se recomienda su reemplazo cada 500 horas o antes en ambientes severos.',
  18000.00,55.000,9900.00,27900.00,'ARS',1,'disponible',1,0,1,24900.00,11,0,4,1,268,1,1),

 (103,'spare_part','FIL-00330','Filtro hidráulico de retorno','filtro-hidraulico-de-retorno',12,27,
  'Filtro hidráulico de retorno roscado, 10 micrones.',
  'Filtro hidráulico de retorno con elemento de 10 micrones. Protege bomba, válvulas y cilindros de la contaminación del aceite.',
  26000.00,55.000,14300.00,40300.00,'ARS',1,'disponible',0,0,0,NULL,7,0,3,1,141,1,1),

 (104,'spare_part','BOM-01000','Bomba hidráulica de engranajes 2.500 kg','bomba-hidraulica-de-engranajes-2500-kg',12,24,
  'Bomba hidráulica de engranajes para autoelevadores de 2 a 3 toneladas.',
  'Bomba de engranajes de alta presión con carcasa de aluminio y engranajes rectificados. Caudal de 32 l/min a 2.000 rpm, presión máxima de 210 bar. Se entrega probada en banco con certificado de ensayo.',
  480000.00,45.000,216000.00,696000.00,'ARS',1,'disponible',1,0,0,NULL,3,1,2,1,412,1,1),

 (105,'spare_part','MAN-00450','Manguera hidráulica R2 1/2" x 1.200 mm','manguera-hidraulica-r2-1-2-1200-mm',12,34,
  'Manguera hidráulica de dos mallas, 1/2 pulgada, con racores prensados.',
  'Manguera hidráulica SAE 100 R2AT de 1/2" con terminales JIC prensados en ambos extremos. Presión de trabajo 275 bar. Fabricación a medida en el día.',
  62000.00,50.000,31000.00,93000.00,'ARS',1,'disponible',0,0,0,NULL,18,0,6,1,98,1,1),

 (106,'spare_part','ROD-00088','Rodamiento de rueda directriz 6208-2RS','rodamiento-de-rueda-directriz-6208-2rs',12,28,
  'Rodamiento rígido de bolas 6208-2RS sellado.',
  'Rodamiento rígido de una hilera de bolas, 40 x 80 x 18 mm, con doble sello de goma y engrase permanente. Aplicación en ruedas directrices y rodillos de mástil.',
  28000.00,60.000,16800.00,44800.00,'ARS',1,'disponible',0,0,0,NULL,26,0,8,1,76,1,1),

 (107,'spare_part','FRE-00512','Juego de cintas de freno 2.500 kg','juego-de-cintas-de-freno-2500-kg',12,22,
  'Juego completo de cintas de freno con remaches.',
  'Juego de cintas de freno de material sinterizado libre de asbesto, con remaches incluidos. Reemplazo recomendado cada 2.000 horas o cuando el espesor sea menor a 3 mm.',
  95000.00,55.000,52250.00,147250.00,'ARS',1,'disponible',1,0,0,NULL,4,0,2,1,203,1,1),

 (108,'spare_part','FRE-00610','Bomba de freno principal','bomba-de-freno-principal',12,22,
  'Cilindro maestro de freno para autoelevadores 2-3 t.',
  'Bomba de freno principal con depósito integrado, diámetro de pistón 22,2 mm. Incluye kit de sellos.',
  156000.00,50.000,78000.00,234000.00,'ARS',1,'consultar',0,0,0,NULL,0,0,1,1,64,1,1),

 (109,'spare_part','SEN-00021','Sensor de temperatura de motor','sensor-de-temperatura-de-motor',12,35,
  'Sensor/bulbo de temperatura de agua con rosca M16.',
  'Sensor de temperatura de refrigerante con rosca M16 x 1,5 y conector tipo pala. Rango de medición -40 a 130 °C.',
  34000.00,60.000,20400.00,54400.00,'ARS',1,'disponible',0,1,0,NULL,9,0,3,1,55,1,1),

 (110,'spare_part','BAT-00480','Batería de tracción 48V 625Ah','bateria-de-traccion-48v-625ah',12,26,
  'Batería de tracción de plomo-ácido 48 V 625 Ah con cuba metálica.',
  'Batería de tracción de 24 elementos de 2 V, 625 Ah en descarga de 5 horas. Incluye cuba metálica, tapones y sistema de llenado centralizado. Garantía de 12 meses.',
  5200000.00,35.000,1820000.00,7020000.00,'ARS',1,'consultar',1,0,0,NULL,0,0,1,1,187,1,1),

 (111,'spare_part','NEU-00700','Neumático sólido 7.00-12','neumatico-solido-7-00-12',12,29,
  'Cubierta sólida (maciza) 7.00-12 para autoelevador.',
  'Neumático macizo de tres compuestos con banda de rodamiento antihuella. Larga duración y cero pinchaduras. Requiere prensa para el montaje (servicio disponible en nuestro taller).',
  310000.00,45.000,139500.00,449500.00,'ARS',1,'disponible',1,0,0,NULL,6,2,2,1,231,1,1),

 (112,'spare_part','HOR-01070','Par de horquillas 1.070 x 100 x 40 mm','par-de-horquillas-1070-x-100-x-40-mm',12,30,
  'Par de uñas clase II de 1.070 mm para 2.500 kg.',
  'Par de horquillas forjadas clase II, 1.070 x 100 x 40 mm, capacidad 2.500 kg a 500 mm de centro de carga. Certificadas y con marcado de capacidad.',
  520000.00,45.000,234000.00,754000.00,'ARS',1,'disponible',0,0,0,NULL,3,0,1,1,144,1,1),

 (113,'spare_part','MAS-00330','Cadena de mástil Leaf BL634','cadena-de-mastil-leaf-bl634',12,31,
  'Cadena de mástil tipo Leaf BL634 por metro.',
  'Cadena de placas tipo Leaf BL634 para mástiles de autoelevador. Se vende por metro. Recomendamos reemplazar siempre de a pares y verificar el alargamiento cada 1.000 horas.',
  74000.00,50.000,37000.00,111000.00,'ARS',1,'disponible',0,0,0,NULL,15,0,5,1,89,1,1),

 (114,'spare_part','CIL-00120','Cilindro de inclinación completo','cilindro-de-inclinacion-completo',12,32,
  'Cilindro hidráulico de inclinación con vástago cromado.',
  'Cilindro de inclinación completo, vástago cromado rectificado y kit de sellos nuevo. Probado a 250 bar.',
  620000.00,45.000,279000.00,899000.00,'ARS',1,'disponible',0,0,0,NULL,2,0,1,1,71,1,1),

 (115,'spare_part','ELE-00090','Contactor de potencia 48V 400A','contactor-de-potencia-48v-400a',12,25,
  'Contactor principal para equipos eléctricos de 48 V.',
  'Contactor de potencia de simple contacto, bobina de 48 V, corriente de trabajo 400 A. Aplicación en autoelevadores y apiladores eléctricos.',
  178000.00,50.000,89000.00,267000.00,'ARS',1,'disponible',0,1,0,NULL,5,0,2,1,63,1,1),

 (116,'spare_part','TRA-00340','Kit de embrague de transmisión','kit-de-embrague-de-transmision',12,21,
  'Kit completo de discos de embrague de transmisión.',
  'Kit de discos y contra-discos para transmisión automática de autoelevador, incluye juntas y sellos.',
  430000.00,45.000,193500.00,623500.00,'ARS',1,'disponible',0,0,0,NULL,2,0,1,1,58,1,1),

 (117,'spare_part','DIR-00210','Orbitrol de dirección hidráulica','orbitrol-de-direccion-hidraulica',12,23,
  'Unidad de dirección hidrostática (orbitrol) 80 cc.',
  'Orbitrol de dirección de 80 cc/rev con válvula de alivio incorporada. Reacondicionado y probado en banco.',
  580000.00,45.000,261000.00,841000.00,'ARS',1,'disponible',0,0,0,NULL,1,0,1,1,47,1,1),

 (118,'spare_part','MOT-00990','Kit de juntas de motor 4Y','kit-de-juntas-de-motor-4y',1,20,
  'Juego completo de juntas para motor Toyota 4Y.',
  'Juego completo de juntas de motor Toyota 4Y incluyendo tapa de cilindros, cárter y colectores. Material de alta temperatura.',
  138000.00,55.000,75900.00,213900.00,'ARS',1,'disponible',1,0,0,NULL,6,0,2,1,152,1,1);

INSERT INTO `spare_parts`
 (`product_id`,`oem_code`,`manufacturer_code`,`manufacturer`,`origin`,`unit`,`weight_kg`,`warehouse_id`,`sector`,`shelf`,`position`,`lead_time_days`) VALUES
 (101,'15601-U2100-71','W68/3','Toyota','original','unidad',0.450,1,'A','Estantería A','Fila 3 · Pos. 4',0),
 (102,'17801-U2140-71','AF-2140','Toyota','alternativo','unidad',0.900,1,'A','Estantería A','Fila 3 · Pos. 7',0),
 (103,'67501-23320-71','HF-3320','Genérico','alternativo','unidad',0.700,1,'A','Estantería B','Fila 1 · Pos. 2',0),
 (104,'67110-23620-71','BH-2362','Genérico','alternativo','unidad',9.400,1,'B','Estantería C','Fila 2 · Pos. 1',0),
 (105,'R2AT-12-1200','MH-1200','Genérico','alternativo','unidad',1.800,1,'B','Estantería D','Fila 4 · Pos. 6',2),
 (106,'6208-2RS','SKF-6208','SKF','original','unidad',0.370,1,'A','Estantería B','Fila 5 · Pos. 3',0),
 (107,'47411-23320-71','BR-3320','Genérico','alternativo','juego',3.200,1,'B','Estantería C','Fila 3 · Pos. 5',0),
 (108,'47201-23320-71','MC-2233','Genérico','alternativo','unidad',1.400,1,'B','Estantería C','Fila 3 · Pos. 6',7),
 (109,'83420-U2110-71','TS-2110','Genérico','alternativo','unidad',0.120,1,'A','Estantería B','Fila 2 · Pos. 9',0),
 (110,'BT-48-625','BT48625','Genérico','alternativo','unidad',980.000,2,'Playón','Sector baterías','Pos. 2',30),
 (111,'70012-SOL','NS-70012','Genérico','alternativo','unidad',52.000,2,'Playón','Sector neumáticos','Pos. 5',0),
 (112,'HOR-1070-II','FK-1070','Genérico','alternativo','par',86.000,2,'Playón','Sector horquillas','Pos. 1',0),
 (113,'BL634','LC-634','Genérico','alternativo','metro',2.900,1,'B','Estantería D','Fila 1 · Pos. 1',0),
 (114,'65500-23330-71','TC-2333','Genérico','alternativo','unidad',14.500,2,'Taller','Estantería E','Fila 2 · Pos. 2',0),
 (115,'CT-48-400','SW200-48','Genérico','alternativo','unidad',1.100,1,'A','Estantería B','Fila 4 · Pos. 8',0),
 (116,'32210-23330-71','CK-2333','Genérico','alternativo','kit',11.000,2,'Taller','Estantería E','Fila 3 · Pos. 4',5),
 (117,'45510-23320-71','OR-80','Genérico','remanufacturado','unidad',6.800,2,'Taller','Estantería E','Fila 1 · Pos. 3',0),
 (118,'04111-78156-71','GK-4Y','Toyota','original','kit',1.900,1,'A','Estantería A','Fila 6 · Pos. 2',0);

-- Códigos alternativos / cruzados --------------------------------------
INSERT INTO `spare_part_codes` (`product_id`,`code_type`,`code`,`brand_id`,`note`) VALUES
 (101,'oem','15601-U2100-71',1,'Código original Toyota'),
 (101,'alternativo','1560123020-71',1,'Código superado'),
 (101,'cruzado','W68/3',NULL,'Equivalente Mann'),
 (101,'cruzado','LF3349',NULL,'Equivalente Fleetguard'),
 (102,'oem','17801-U2140-71',1,NULL),
 (102,'cruzado','C17137',NULL,'Equivalente Mann'),
 (103,'oem','67501-23320-71',1,NULL),
 (104,'oem','67110-23620-71',1,NULL),
 (104,'alternativo','67110-U2170-71',1,'Aplicación 3 t'),
 (106,'cruzado','6208-2RS1',NULL,'SKF'),
 (106,'cruzado','6208DDU',NULL,'NSK'),
 (107,'oem','47411-23320-71',1,NULL),
 (111,'alternativo','700-12',NULL,'Medida comercial'),
 (118,'oem','04111-78156-71',1,NULL);

-- Compatibilidad libre (marca + modelo) --------------------------------
INSERT INTO `spare_part_compatibility` (`spare_part_id`,`brand_id`,`model`,`year_from`,`year_to`,`note`) VALUES
 (101,1,'8FG25',2010,2024,'Motor 4Y'),
 (101,1,'8FG30',2010,2024,'Motor 4Y'),
 (101,1,'8FD25',2010,2024,'Motor 1DZ'),
 (101,1,'8FD30',2010,2024,'Motor 1DZ'),
 (101,1,'7FG25',2003,2010,'Motor 4Y'),
 (101,2,'H2.5FT',2012,2022,'Verificar motor'),
 (102,1,'8FG25',2010,2024,NULL),
 (102,1,'8FD30',2010,2024,NULL),
 (102,2,'H2.5FT',2012,2022,NULL),
 (102,3,'GLP050',2012,2020,NULL),
 (103,1,'8FG25',2010,2024,NULL),
 (103,1,'8FD30',2010,2024,NULL),
 (104,1,'8FG25',2010,2024,NULL),
 (104,1,'8FD30',2010,2024,NULL),
 (104,3,'GLP050',2012,2020,NULL),
 (105,NULL,'Universal 2-3 t',NULL,NULL,'Fabricación a medida'),
 (106,1,'8FG25',2010,2024,'Rueda directriz'),
 (106,2,'E30XN',2015,2023,'Rodillo de mástil'),
 (107,1,'8FG25',2010,2024,NULL),
 (107,1,'8FD30',2010,2024,NULL),
 (108,1,'8FG25',2010,2024,NULL),
 (109,1,'8FD30',2010,2024,NULL),
 (110,2,'E30XN',2015,2023,'Batería de tracción'),
 (110,8,'EJC 112',2018,2024,'Verificar cuba'),
 (111,1,'8FG25',2010,2024,'Rueda delantera'),
 (111,3,'GLP050',2012,2020,NULL),
 (112,1,'8FG25',2010,2024,'Clase II'),
 (112,1,'8FD30',2010,2024,'Clase II'),
 (113,1,'8FG25',2010,2024,NULL),
 (113,2,'E30XN',2015,2023,NULL),
 (114,1,'8FG25',2010,2024,NULL),
 (115,2,'E30XN',2015,2023,NULL),
 (115,8,'EJC 112',2018,2024,NULL),
 (115,7,'T20',2019,2024,NULL),
 (116,1,'8FG25',2010,2024,NULL),
 (117,1,'8FG25',2010,2024,NULL),
 (118,1,'8FG25',2010,2024,'Motor 4Y'),
 (118,1,'7FG25',2003,2010,'Motor 4Y');

-- Relación explícita máquina del catálogo <-> repuesto -----------------
INSERT INTO `machine_spare_parts` (`machine_id`,`spare_part_id`,`recommended`,`note`) VALUES
 (1,101,1,'Cambio cada 250 h'),(1,102,1,'Cambio cada 500 h'),(1,103,1,NULL),(1,104,0,NULL),
 (1,107,1,NULL),(1,111,0,NULL),(1,112,0,NULL),(1,113,0,NULL),(1,118,0,NULL),(1,106,0,NULL),
 (2,101,1,NULL),(2,102,1,NULL),(2,103,1,NULL),(2,104,0,NULL),(2,107,1,NULL),(2,109,0,NULL),(2,112,0,NULL),
 (3,106,0,NULL),(3,110,1,'Batería de reemplazo'),(3,113,0,NULL),(3,115,1,NULL),
 (4,102,1,NULL),(4,104,0,NULL),(4,111,0,NULL),
 (5,110,0,'Verificar cuba'),(5,115,1,NULL),
 (7,115,1,NULL);

-- Características técnicas cargadas ------------------------------------
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 1, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'2.500' txt, 2500 num UNION ALL
  SELECT 'altura-maxima','4.700',4700 UNION ALL
  SELECT 'altura-replegada','2.100',2100 UNION ALL
  SELECT 'centro-de-carga','500',500 UNION ALL
  SELECT 'peso-operativo','3.800',3800 UNION ALL
  SELECT 'largo-total','3.695',3695 UNION ALL
  SELECT 'ancho-total','1.150',1150 UNION ALL
  SELECT 'radio-de-giro','2.200',2200 UNION ALL
  SELECT 'combustible','Gas',NULL UNION ALL
  SELECT 'motor','Toyota 4Y 2.2L',NULL UNION ALL
  SELECT 'potencia','52',52 UNION ALL
  SELECT 'transmision-feature','Automática (convertidor)',NULL UNION ALL
  SELECT 'horas-de-uso','6.200',6200 UNION ALL
  SELECT 'tipo-de-mastil','Triple visión libre',NULL UNION ALL
  SELECT 'tipo-de-rueda','Neumático',NULL UNION ALL
  SELECT 'velocidad-traslacion','19',19 UNION ALL
  SELECT 'garantia','6 meses',NULL
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 2, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'3.000' txt, 3000 num UNION ALL
  SELECT 'altura-maxima','4.500',4500 UNION ALL
  SELECT 'peso-operativo','4.400',4400 UNION ALL
  SELECT 'combustible','Diésel',NULL UNION ALL
  SELECT 'motor','Toyota 1DZ-III 2.5L',NULL UNION ALL
  SELECT 'potencia','54',54 UNION ALL
  SELECT 'horas-de-uso','4.800',4800 UNION ALL
  SELECT 'tipo-de-mastil','Triple',NULL UNION ALL
  SELECT 'garantia','6 meses',NULL
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 3, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'1.400' txt, 1400 num UNION ALL
  SELECT 'altura-maxima','4.500',4500 UNION ALL
  SELECT 'peso-operativo','3.200',3200 UNION ALL
  SELECT 'combustible','Eléctrico',NULL UNION ALL
  SELECT 'voltaje','48',48 UNION ALL
  SELECT 'tipo-de-bateria','Plomo-ácido 625Ah',NULL UNION ALL
  SELECT 'horas-de-uso','3.100',3100 UNION ALL
  SELECT 'radio-de-giro','1.600',1600 UNION ALL
  SELECT 'garantia','6 meses',NULL
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 5, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'1.200' txt, 1200 num UNION ALL
  SELECT 'altura-maxima','3.300',3300 UNION ALL
  SELECT 'combustible','Eléctrico',NULL UNION ALL
  SELECT 'voltaje','24',24 UNION ALL
  SELECT 'horas-de-uso','1.800',1800 UNION ALL
  SELECT 'peso-operativo','1.150',1150
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 7, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'2.000' txt, 2000 num UNION ALL
  SELECT 'combustible','Eléctrico',NULL UNION ALL
  SELECT 'voltaje','24',24 UNION ALL
  SELECT 'tipo-de-bateria','Litio 205Ah',NULL UNION ALL
  SELECT 'horas-de-uso','900',900
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 9, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'227' txt, 227 num UNION ALL
  SELECT 'altura-maxima','5.800',5800 UNION ALL
  SELECT 'combustible','Eléctrico',NULL UNION ALL
  SELECT 'voltaje','24',24 UNION ALL
  SELECT 'horas-de-uso','2.400',2400
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 101, f.id, 'Papel plisado', NULL FROM features f WHERE f.slug='material';
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 101, f.id, '3/4"-16 UNF', NULL FROM features f WHERE f.slug='rosca';
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 106, f.id, '40 x 80 x 18 mm', NULL FROM features f WHERE f.slug='medida';
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 106, f.id, '80', 80 FROM features f WHERE f.slug='diametro';
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 111, f.id, '7.00-12', NULL FROM features f WHERE f.slug='medida';

-- Etiquetas por producto -----------------------------------------------
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'usado'
 WHERE p.id IN (1,2,3,4,5,7,9,10,11);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'nuevo'
 WHERE p.id IN (6,8,12,109,115);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'oferta'
 WHERE p.id IN (2,102);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'electrico'
 WHERE p.id IN (3,5,7,9);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'diesel'
 WHERE p.id IN (2,10,11);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'destacado'
 WHERE p.featured = 1;
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'ultimas-unidades'
 WHERE p.id IN (104,107,114,117);

-- Videos y documentación -----------------------------------------------
INSERT INTO `videos` (`product_id`,`title`,`provider`,`video_ref`,`sort_order`) VALUES
 (1,'Ver la máquina trabajando','youtube','dQw4w9WgXcQ',1),
 (3,'Demostración en depósito','youtube','dQw4w9WgXcQ',1);

-- Historial de precios --------------------------------------------------
INSERT INTO `price_history`
 (`product_id`,`old_cost`,`new_cost`,`old_profit_percent`,`new_profit_percent`,`old_profit_amount`,`new_profit_amount`,`old_price`,`new_price`,`reason`,`user_id`,`created_at`) VALUES
 (1,15200000.00,16000000.00,25.000,25.000,3800000.00,4000000.00,19000000.00,20000000.00,'Actualización de lista de proveedor',2,'2026-08-15 10:24:00'),
 (1,14400000.00,15200000.00,25.000,25.000,3600000.00,3800000.00,18000000.00,19000000.00,'Ajuste por tipo de cambio',1,'2026-06-02 09:10:00'),
 (101,10000.00,12000.00,60.000,60.000,6000.00,7200.00,16000.00,19200.00,'Actualización de proveedor',2,'2026-08-10 16:40:00'),
 (104,420000.00,480000.00,45.000,45.000,189000.00,216000.00,609000.00,696000.00,'Aumento importación',1,'2026-07-22 11:05:00');

-- Movimientos de stock --------------------------------------------------
INSERT INTO `stock_movements` (`product_id`,`type`,`quantity`,`stock_before`,`stock_after`,`reason`,`reference`,`user_id`,`created_at`) VALUES
 (101,'entrada',30,0,30,'Compra a proveedor','OC-1042',2,'2026-08-01 09:00:00'),
 (101,'salida',4,30,26,'Service preventivo cliente Logística SA','OT-2211',2,'2026-08-08 14:30:00'),
 (101,'salida',2,26,24,'Venta mostrador',NULL,3,'2026-08-19 11:12:00'),
 (102,'entrada',15,0,15,'Compra a proveedor','OC-1042',2,'2026-08-01 09:05:00'),
 (102,'salida',4,15,11,'Service preventivo','OT-2214',2,'2026-08-12 10:00:00'),
 (104,'entrada',4,0,4,'Compra importación','OC-1050',1,'2026-07-25 12:00:00'),
 (104,'salida',1,4,3,'Reparación equipo cliente','OT-2230',2,'2026-08-20 15:45:00'),
 (111,'entrada',8,0,8,'Compra a proveedor','OC-1055',2,'2026-08-05 08:30:00'),
 (111,'salida',2,8,6,'Cambio de cubiertas','OT-2240',2,'2026-08-21 09:20:00'),
 (108,'entrada',2,0,2,'Compra a proveedor','OC-1060',2,'2026-07-15 10:00:00'),
 (108,'salida',2,2,0,'Venta','FC-A-0001',3,'2026-08-18 17:00:00');

-- Consultas -------------------------------------------------------------
INSERT INTO `inquiries` (`name`,`email`,`phone`,`company`,`product_id`,`subject`,`message`,`channel`,`status`,`created_at`) VALUES
 ('Martín Suárez','msuarez@ejemplo.com','11-5555-1122','Logística del Sur SA',1,'Consulta por Autoelevador Toyota 8FG25','Buenas tardes, necesito saber disponibilidad y si aceptan parte de pago con un equipo usado. Gracias.','web','nueva','2026-08-24 10:15:00'),
 ('Verónica Díaz','vdiaz@ejemplo.com','11-5555-3344','Depósitos Norte SRL',101,'Consulta por Filtro de aceite','Necesito 20 unidades del filtro FIL-00125. ¿Tienen stock y qué precio por cantidad?','web','en_proceso','2026-08-25 09:02:00'),
 ('Roberto Klein','rklein@ejemplo.com','351-555-7788','Metalúrgica Klein',NULL,'Alquiler mensual','Quisiera cotizar el alquiler de dos autoelevadores de 2.5 t por seis meses en Córdoba.','web','nueva','2026-08-25 16:40:00'),
 ('Pablo Ferrari','pferrari@ejemplo.com','11-5555-9900',NULL,3,'Autoelevador eléctrico','¿El equipo eléctrico sirve para cámara de frío? ¿Qué autonomía tiene?','whatsapp','respondida','2026-08-20 11:30:00');

-- Cotizaciones ----------------------------------------------------------
INSERT INTO `quotes`
 (`id`,`number`,`status`,`source`,`customer_name`,`customer_company`,`customer_email`,`customer_phone`,`customer_taxid`,
  `subtotal`,`discount_percent`,`discount_amount`,`shipping_cost`,`other_costs`,`interest_amount`,`total`,`currency`,
  `financing_option_id`,`down_payment`,`installments`,`installment_amount`,`notes`,`valid_until`,`user_id`,`created_at`) VALUES
 (1,'COT-000001','enviada','admin','Martín Suárez','Logística del Sur SA','msuarez@ejemplo.com','11-5555-1122','30-71234567-9',
  21500000.00,0.00,1000000.00,500000.00,0.00,0.00,21000000.00,'ARS',
  2,6300000.00,6,2695000.00,'Incluye entrega en planta y puesta en marcha.','2026-09-10',3,'2026-08-22 12:00:00'),
 (2,'COT-000002','borrador','admin','Verónica Díaz','Depósitos Norte SRL','vdiaz@ejemplo.com','11-5555-3344',NULL,
  384000.00,5.00,19200.00,0.00,0.00,0.00,364800.00,'ARS',
  NULL,0.00,0,0.00,'Cotización por 20 filtros de aceite.','2026-09-08',3,'2026-08-25 09:30:00');

INSERT INTO `quote_items` (`quote_id`,`product_id`,`item_type`,`code`,`description`,`quantity`,`unit_price`,`discount_percent`,`line_total`,`sort_order`) VALUES
 (1,1,'machine','AE-001','Autoelevador Toyota 8FG25 2.500 kg',1.00,20000000.00,0.00,20000000.00,1),
 (1,12,'machine','EQ-001','Pinza para bobinas hidráulica',1.00,5720000.00,0.00,5720000.00,2),
 (1,NULL,'service',NULL,'Puesta en marcha y capacitación de operadores',1.00,280000.00,0.00,280000.00,3),
 (2,101,'spare_part','FIL-00125','Filtro de aceite motor Toyota serie 8',20.00,19200.00,0.00,384000.00,1);

UPDATE `quotes` SET `subtotal` = 26000000.00, `discount_amount` = 5000000.00, `total` = 21500000.00 WHERE `id` = 1;

INSERT INTO `quote_payments` (`quote_id`,`concept`,`amount`,`due_date`,`sort_order`) VALUES
 (1,'Anticipo 30%',6300000.00,'2026-09-01',1),
 (1,'Cuota 1 de 6',2695000.00,'2026-10-01',2),
 (1,'Cuota 2 de 6',2695000.00,'2026-11-01',3);

-- Analítica de ejemplo ---------------------------------------------------
INSERT INTO `search_logs` (`term`,`normalized`,`context`,`results_count`,`created_at`) VALUES
 ('8FG25','8fg25','spare_part',9,'2026-08-25 10:00:00'),
 ('filtro toyota','filtro toyota','spare_part',3,'2026-08-25 10:04:00'),
 ('filtro de aceite','filtro de aceite','spare_part',2,'2026-08-24 15:20:00'),
 ('bomba hidraulica','bomba hidraulica','spare_part',1,'2026-08-24 16:00:00'),
 ('pastillas de freno','pastillas de freno','spare_part',2,'2026-08-23 11:00:00'),
 ('autoelevador electrico','autoelevador electrico','machine',2,'2026-08-23 09:40:00'),
 ('8FG25','8fg25','global',10,'2026-08-22 18:05:00'),
 ('15601-U2100-71','15601-u2100-71','spare_part',1,'2026-08-22 12:30:00'),
 ('apilador','apilador','machine',2,'2026-08-21 14:10:00'),
 ('zorra electrica','zorra electrica','machine',1,'2026-08-20 10:00:00');

INSERT INTO `activity_logs` (`user_id`,`user_name`,`action`,`module`,`entity_type`,`entity_id`,`description`,`ip`,`created_at`) VALUES
 (1,'Administrador','login','auth',NULL,NULL,'Inicio de sesión correcto','127.0.0.1','2026-08-26 08:00:00'),
 (2,'Juan Pérez','price_change','prices','product',1,'Precio actualizado de $19.000.000 a $20.000.000','127.0.0.1','2026-08-15 10:24:00'),
 (2,'Juan Pérez','stock_change','stock','product',101,'Salida de 2 unidades','127.0.0.1','2026-08-19 11:12:00'),
 (3,'Carla Gómez','create','quotes','quote',1,'Cotización COT-000001 creada','127.0.0.1','2026-08-22 12:00:00');

SET FOREIGN_KEY_CHECKS = 1;

SET FOREIGN_KEY_CHECKS = 1;
