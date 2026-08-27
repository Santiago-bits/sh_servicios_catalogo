-- =====================================================================
--  SH SERVICIOS - Sistema de catálogo y gestión de maquinaria/repuestos
--  Esquema de base de datos (MySQL 5.7+ / MariaDB 10.4+ / XAMPP)
--  Motor: InnoDB · Charset: utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE IF NOT EXISTS `sh_servicios`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sh_servicios`;

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
