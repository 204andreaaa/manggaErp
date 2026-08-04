-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mp3
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'Seeder Init','System',NULL,'Seeder initial setup (real testing data) berhasil dibuat.','2026-06-22 02:56:18','2026-06-22 02:56:18');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bom_items`
--

DROP TABLE IF EXISTS `bom_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bom_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bom_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bom_items_bom_id_foreign` (`bom_id`),
  KEY `bom_items_material_id_foreign` (`material_id`),
  CONSTRAINT `bom_items_bom_id_foreign` FOREIGN KEY (`bom_id`) REFERENCES `boms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bom_items_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bom_items`
--

LOCK TABLES `bom_items` WRITE;
/*!40000 ALTER TABLE `bom_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `bom_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bom_transaction_items`
--

DROP TABLE IF EXISTS `bom_transaction_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bom_transaction_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bom_transaction_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `qty_used` decimal(15,2) NOT NULL,
  `cost_per_unit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bom_transaction_items_bom_transaction_id_foreign` (`bom_transaction_id`),
  KEY `bom_transaction_items_material_id_foreign` (`material_id`),
  CONSTRAINT `bom_transaction_items_bom_transaction_id_foreign` FOREIGN KEY (`bom_transaction_id`) REFERENCES `bom_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bom_transaction_items_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bom_transaction_items`
--

LOCK TABLES `bom_transaction_items` WRITE;
/*!40000 ALTER TABLE `bom_transaction_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `bom_transaction_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bom_transactions`
--

DROP TABLE IF EXISTS `bom_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bom_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bom_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `production_qty` bigint(20) NOT NULL,
  `total_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bom_transactions_bom_id_foreign` (`bom_id`),
  KEY `bom_transactions_product_id_foreign` (`product_id`),
  KEY `bom_transactions_user_id_foreign` (`user_id`),
  CONSTRAINT `bom_transactions_bom_id_foreign` FOREIGN KEY (`bom_id`) REFERENCES `boms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bom_transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bom_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bom_transactions`
--

LOCK TABLES `bom_transactions` WRITE;
/*!40000 ALTER TABLE `bom_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `bom_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `boms`
--

DROP TABLE IF EXISTS `boms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `bom_code` varchar(255) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `output_qty` bigint(20) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `boms_bom_code_unique` (`bom_code`),
  KEY `boms_product_id_foreign` (`product_id`),
  KEY `boms_created_by_foreign` (`created_by`),
  KEY `boms_updated_by_foreign` (`updated_by`),
  CONSTRAINT `boms_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `boms_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boms_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `boms`
--

LOCK TABLES `boms` WRITE;
/*!40000 ALTER TABLE `boms` DISABLE KEYS */;
/*!40000 ALTER TABLE `boms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_category_code_unique` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'CAT-VCR','Voucher','Produk voucher data / fisik','2026-06-22 02:56:17','2026-06-22 02:56:17'),(2,'CAT-SLD','Saldo Elektronik','Produk saldo elektronik','2026-06-22 02:56:17','2026-06-22 02:56:17'),(3,'CAT-KPK','Kartu','Produk kartu pedana kosong','2026-06-22 02:56:17','2026-06-22 02:56:17'),(4,'CAT-AST','Aset & Perangkat','Modem, brankas, dll','2026-06-22 02:56:17','2026-06-22 02:56:17'),(5,'CAT-PRJ','Project','Backlog Project','2026-06-22 02:56:17','2026-06-22 02:56:17');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `short_name` varchar(50) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `logo_small_path` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_code_index` (`code`),
  KEY `companies_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Mandau','PT Mandiri Daya Utama Nusantara','MANDAU','MANDAU','Komplek Golden Plaza Blok C17, Jl. RS Fatmawati No. 15','Jakarta Selatan','DKI Jakarta','+62 21 7590 9945','info@mandau.id','https://mandau.id',NULL,'ImageAsset/logo-mandau.png',NULL,1,1,'2026-06-22 02:56:17','2026-06-22 02:56:17',NULL),(2,'IOH','Indosat Ooredoo Hutchison','IOH','IOH','Jl. Medan Merdeka Barat No.21, RW.3, Gambir, Kecamatan Gambir, Kota Jakarta Pusat, Indonesia','Jakarta','Jakarta Pusat','08116027598','care@im3.id','https://ioh.co.id/portal/id/iohindex',NULL,'ImageAsset/logo-indosat.png',NULL,0,1,'2026-06-22 02:56:17','2026-06-22 02:56:17',NULL);
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `damaged_stock_photos`
--

DROP TABLE IF EXISTS `damaged_stock_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `damaged_stock_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `damaged_stock_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `kind` enum('initial','action_proof','resolved') NOT NULL DEFAULT 'initial',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `damaged_stock_photos_damaged_stock_id_foreign` (`damaged_stock_id`),
  CONSTRAINT `damaged_stock_photos_damaged_stock_id_foreign` FOREIGN KEY (`damaged_stock_id`) REFERENCES `damaged_stocks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `damaged_stock_photos`
--

LOCK TABLES `damaged_stock_photos` WRITE;
/*!40000 ALTER TABLE `damaged_stock_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `damaged_stock_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `damaged_stocks`
--

DROP TABLE IF EXISTS `damaged_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `damaged_stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `source_type` varchar(255) NOT NULL DEFAULT 'manual',
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` bigint(20) NOT NULL,
  `condition` enum('damaged','expired') NOT NULL,
  `action` enum('repair','return_to_supplier','dispose','other') DEFAULT NULL,
  `status` enum('quarantine','pending_approval','in_progress','resolved','rejected') NOT NULL DEFAULT 'quarantine',
  `notes` text DEFAULT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `damaged_stocks_product_id_foreign` (`product_id`),
  KEY `damaged_stocks_requested_by_foreign` (`requested_by`),
  KEY `damaged_stocks_approved_by_foreign` (`approved_by`),
  KEY `damaged_stocks_resolved_by_foreign` (`resolved_by`),
  KEY `damaged_stocks_warehouse_id_status_index` (`warehouse_id`,`status`),
  CONSTRAINT `damaged_stocks_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `damaged_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `damaged_stocks_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `damaged_stocks_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `damaged_stocks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `damaged_stocks`
--

LOCK TABLES `damaged_stocks` WRITE;
/*!40000 ALTER TABLE `damaged_stocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `damaged_stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gr_delete_requests`
--

DROP TABLE IF EXISTS `gr_delete_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gr_delete_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `restock_receipt_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gr_delete_requests_restock_receipt_id_foreign` (`restock_receipt_id`),
  KEY `gr_delete_requests_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `gr_delete_requests_requested_by_foreign` (`requested_by`),
  KEY `gr_delete_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `gr_delete_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gr_delete_requests_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gr_delete_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gr_delete_requests_restock_receipt_id_foreign` FOREIGN KEY (`restock_receipt_id`) REFERENCES `restock_receipts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gr_delete_requests`
--

LOCK TABLES `gr_delete_requests` WRITE;
/*!40000 ALTER TABLE `gr_delete_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `gr_delete_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_100000_create_password_reset_tokens_table',1),(2,'2019_08_19_000000_create_failed_jobs_table',1),(3,'2019_12_14_000001_create_personal_access_tokens_table',1),(4,'2025_09_29_000006_create_warehouses_table',1),(5,'2025_09_30_000001_create_users_table',1),(6,'2025_09_30_000002_create_roles_table',1),(7,'2025_09_30_000003_create_role_user_table',1),(8,'2025_10_30_000001_create_companies_table',1),(9,'2025_10_30_000001_create_simple_packages_table',1),(10,'2025_10_30_000005_create_suppliers_table',1),(11,'2025_10_30_000007_create_categories_table',1),(12,'2025_10_30_000009_create_products_table',1),(13,'2025_10_30_000010_create_stock_levels_table',1),(14,'2025_10_30_000011_create_stock_snapshots_table',1),(15,'2025_10_30_000012_create_stock_movements_table',1),(16,'2025_10_30_000014_create_request_restocks_table',1),(17,'2025_11_10_000001_create_restock_receipts_table',1),(18,'2025_11_10_000002_create_restock_receipt_photos_table',1),(19,'2025_11_10_000003_create_purchase_orders_table',1),(20,'2025_11_10_000004_create_purchase_order_items_table',1),(21,'2025_11_12_000001_add_menu_keys_to_roles_table',1),(22,'2025_11_20_000001_create_stock_adjustments_table',1),(23,'2025_11_20_000002_create_stock_adjustment_items_table',1),(24,'2025_11_22_000002_create_gr_delete_requests_table',1),(25,'2025_11_22_000003_create_sales_handovers_table',1),(26,'2025_11_22_000004_create_sales_handover_items_table',1),(27,'2025_11_22_000005_create_sales_reports_table',1),(28,'2025_11_22_000006_create_sales_returns_table',1),(29,'2025_11_22_000007_create_stock_requests_table',1),(30,'2025_12_01_000001_create_activity_logs_table',1),(31,'2026_01_07_000001_create_warehouse_transfers_table',1),(32,'2026_01_07_000002_create_warehouse_transfer_items_table',1),(33,'2026_01_07_000003_create_warehouse_transfer_logs_table',1),(34,'2026_02_14_000001_create_boms_table',1),(35,'2026_02_14_000002_create_bom_items_table',1),(36,'2026_02_14_000003_create_bom_transactions_table',1),(37,'2026_02_14_000004_create_bom_transaction_items_table',1),(38,'2026_03_26_070120_add_permissions_to_roles_table',1),(39,'2026_03_31_090000_add_split_payment_fields_to_sales_handover_items_table',1),(40,'2026_03_31_120000_add_payment_transfer_proof_paths_to_sales_handover_items_table',1),(41,'2026_04_05_024800_create_damaged_stocks_table',1),(42,'2026_04_05_024801_create_damaged_stock_photos_table',1),(43,'2026_04_09_000934_add_gr_type_and_drop_unique_code_on_restock_receipts',1),(44,'2026_04_15_135723_create_notifications_table',1),(45,'2026_04_21_000001_expand_stock_adjustment_quantities_to_bigint',1),(46,'2026_04_21_000001_upgrade_stock_and_price_columns_to_bigint',1),(47,'2026_04_21_000002_comprehensive_bigint_upgrade',1),(48,'2026_04_24_153553_add_discount_flexible_fields_to_sales_handover_items_table',1),(49,'2026_04_26_100000_add_direct_sales_fields_to_handovers',1),(50,'2026_04_26_110000_add_indexes_to_handovers_for_performance',1),(51,'2026_04_29_132131_create_warehouse_settlements_table',1),(52,'2026_04_29_132132_add_settlement_id_to_sales_handovers_table',1),(53,'2026_05_05_000001_final_biginteger_standardization',1),(54,'2026_05_18_094500_change_proof_path_columns_to_text_in_handovers_and_settlements',1),(55,'2026_05_19_110331_create_sales_handover_payments_table',1),(56,'2026_06_17_000001_drop_unused_stock_snapshots_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_index` (`user_id`),
  KEY `notifications_is_read_index` (`is_read`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `package_name` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `packages_package_name_unique` (`package_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
INSERT INTO `packages` VALUES (1,'BOX','2026-06-22 02:56:17','2026-06-22 02:56:17'),(2,'PCS','2026-06-22 02:56:17','2026-06-22 02:56:17'),(3,'Rp.','2026-06-22 02:56:17','2026-06-22 02:56:17');
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `product_type` enum('material','BOM','normal') NOT NULL DEFAULT 'normal',
  `description` text DEFAULT NULL,
  `purchasing_price` bigint(20) DEFAULT 0,
  `standard_cost` decimal(15,2) DEFAULT NULL,
  `selling_price` bigint(20) DEFAULT 0,
  `stock_minimum` bigint(20) DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `package_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_product_code_unique` (`product_code`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_supplier_id_foreign` (`supplier_id`),
  KEY `products_package_id_foreign` (`package_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'PRD-017','Trade Supply (Saldo) January 2026',2,'normal','Trade Supply (Saldo) January 2026',1,1.00,1,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(2,'PRD-018','Trade Supply (Saldo) Februari 2026',1,'normal','Voucher February',10701,10701.00,10701,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(3,'PRD-019','Trade Supply (Voucher) Februari 2026',1,'normal','Voucher February 2026',500,500.00,500,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(4,'PRD-020','PERDANA ORI 3GB 30D February 2026',3,'normal','February 2026',29000,29000.00,29000,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(5,'PRD-021','PERDANA ORI 3GB 30D Maret 2026',3,'normal','Backlog Maret 2026',29000,29000.00,29000,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(6,'PRD-022','Voucher 5 GB 3D Maret 2026',1,'normal','Backlog Voucher 2026',12300,12300.00,12300,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(7,'PRD-023','Voucher 6 GB 2D Maret 2026',1,'normal','Backlog Voucher Maret 2026',9500,9500.00,9500,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(8,'PRD-024','Voucher 12 GB 7D Maret 2026',1,'normal','Backlog Maret 2026',23500,23500.00,23500,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(9,'PRD-025','Trymee Maret 2026',3,'normal','Trymee April 2026',10000,10000.00,10000,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(10,'PRD-026','Trade Supply (Saldo) April 2026',2,'normal','Trade supply April 2026',1,1.00,1,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(11,'PRD-027','Hifi Rabit',4,'normal','Hifi Rabit April 2026',350000,350000.00,350000,50,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(12,'PRD-028','HKM0172+',4,'normal','Wifi',368000,368000.00,368000,1,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(13,'PRD-029','Voucher Blank',1,'material','Voucher Blank',500,500.00,500,1,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(14,'PRD-030','Voucher Blank 9GB 7D',1,'normal','Voucher Blank 9GB 7D',500,500.00,500,1,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(15,'PRD-031','Voucher Blank 3GB 3D',1,'normal','Voucher Blank 3GB 3D',500,500.00,500,1,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL),(16,'PRD-034','KPK Regular',3,'normal','Kartu Perdana Kosong',10000,10000.00,10000,1,1,2,1,'2026-06-22 02:56:18','2026-06-22 02:56:18',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `request_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `qty_ordered` bigint(20) NOT NULL,
  `qty_received` bigint(20) NOT NULL DEFAULT 0,
  `unit_price` bigint(20) NOT NULL DEFAULT 0,
  `discount_type` enum('percent','amount') DEFAULT NULL,
  `discount_value` decimal(16,2) DEFAULT NULL,
  `line_total` bigint(20) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_purchase_order_id_index` (`purchase_order_id`),
  KEY `purchase_order_items_request_id_index` (`request_id`),
  KEY `purchase_order_items_product_id_index` (`product_id`),
  KEY `purchase_order_items_warehouse_id_index` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `po_code` varchar(50) NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `ordered_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by_procurement` bigint(20) unsigned DEFAULT NULL,
  `approved_at_procurement` timestamp NULL DEFAULT NULL,
  `approved_by_ceo` bigint(20) unsigned DEFAULT NULL,
  `approved_at_ceo` timestamp NULL DEFAULT NULL,
  `approval_status` varchar(30) NOT NULL DEFAULT 'draft',
  `status` enum('draft','approved','ordered','partially_received','completed','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(16,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(16,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(16,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `ordered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_code_unique` (`po_code`),
  KEY `purchase_orders_supplier_id_index` (`supplier_id`),
  KEY `purchase_orders_ordered_by_index` (`ordered_by`),
  KEY `purchase_orders_approved_by_procurement_index` (`approved_by_procurement`),
  KEY `purchase_orders_approved_by_ceo_index` (`approved_by_ceo`),
  KEY `purchase_orders_approval_status_index` (`approval_status`),
  KEY `purchase_orders_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `request_restocks`
--

DROP TABLE IF EXISTS `request_restocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `request_restocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `quantity_requested` bigint(20) NOT NULL,
  `quantity_received` bigint(20) NOT NULL DEFAULT 0,
  `cost_per_item` bigint(20) NOT NULL DEFAULT 0,
  `total_cost` bigint(20) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','ordered','received','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_restocks_supplier_id_foreign` (`supplier_id`),
  KEY `request_restocks_product_id_foreign` (`product_id`),
  KEY `request_restocks_warehouse_id_foreign` (`warehouse_id`),
  KEY `request_restocks_requested_by_foreign` (`requested_by`),
  KEY `request_restocks_approved_by_foreign` (`approved_by`),
  KEY `request_restocks_code_index` (`code`),
  CONSTRAINT `request_restocks_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `request_restocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `request_restocks_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `request_restocks_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `request_restocks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `request_restocks`
--

LOCK TABLES `request_restocks` WRITE;
/*!40000 ALTER TABLE `request_restocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `request_restocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restock_receipt_photos`
--

DROP TABLE IF EXISTS `restock_receipt_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restock_receipt_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `type` enum('good','damaged') NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `restock_receipt_photos_receipt_id_foreign` (`receipt_id`),
  CONSTRAINT `restock_receipt_photos_receipt_id_foreign` FOREIGN KEY (`receipt_id`) REFERENCES `restock_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restock_receipt_photos`
--

LOCK TABLES `restock_receipt_photos` WRITE;
/*!40000 ALTER TABLE `restock_receipt_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `restock_receipt_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restock_receipts`
--

DROP TABLE IF EXISTS `restock_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restock_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `gr_type` enum('po','request_stock','gr_transfer','gr_return') NOT NULL DEFAULT 'po',
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `request_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `qty_requested` bigint(20) NOT NULL DEFAULT 0,
  `qty_good` bigint(20) NOT NULL DEFAULT 0,
  `qty_damaged` bigint(20) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `restock_receipts_request_id_index` (`request_id`),
  KEY `restock_receipts_warehouse_id_index` (`warehouse_id`),
  KEY `restock_receipts_supplier_id_index` (`supplier_id`),
  KEY `restock_receipts_product_id_index` (`product_id`),
  KEY `restock_receipts_purchase_order_id_index` (`purchase_order_id`),
  KEY `restock_receipts_gr_type_index` (`gr_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restock_receipts`
--

LOCK TABLES `restock_receipts` WRITE;
/*!40000 ALTER TABLE `restock_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `restock_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_user` (
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES (1,1),(2,3),(3,3),(4,6),(5,6),(6,4),(7,5),(8,3),(9,3),(10,3),(11,3),(12,3),(13,3),(14,3),(15,3),(16,3),(17,3),(18,6),(19,6),(20,1),(21,6),(22,6),(23,6),(24,6),(25,6),(26,6),(27,6),(28,6),(29,6),(30,6),(31,6),(32,6),(33,6),(34,6),(35,6),(36,6),(37,6),(38,6),(39,6),(40,6),(41,6),(42,6),(43,6),(44,6),(45,6),(46,6),(47,6),(48,6),(49,6),(50,6),(51,6),(52,6),(53,6),(54,6),(55,6),(56,6),(57,6),(58,6),(59,6),(60,6),(61,6),(62,6),(63,6),(64,6),(65,6),(66,6),(67,6),(68,6),(69,6),(70,6),(71,6),(72,6),(73,6),(74,6),(75,6),(76,6),(77,6),(78,6),(79,6);
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `home_route` varchar(255) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `menu_keys` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`menu_keys`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','superadmin','admin.dashboard','[\"products.view\",\"products.create\",\"products.update\",\"products.delete\",\"category.view\",\"category.create\",\"category.update\",\"category.delete\",\"stock_adjustments.view\",\"stock_adjustments.create\",\"stock_adjustments.export\",\"supplier.view\",\"supplier.create\",\"supplier.update\",\"supplier.delete\",\"po.view\",\"po.create\",\"po.update\",\"po.delete\",\"packages.view\",\"packages.create\",\"packages.update\",\"packages.delete\",\"company.view\",\"company.create\",\"company.update\",\"company.delete\",\"users.view\",\"users.create\",\"users.update\",\"users.delete\",\"users.bulk_delete\",\"users.export\",\"roles.view\",\"roles.create\",\"roles.update\",\"roles.delete\",\"bom.view\",\"bom.create\",\"bom.update\",\"bom.delete\",\"bom.produce\",\"warehouse.view\",\"warehouse.create\",\"warehouse.update\",\"warehouse.delete\"]','[\"products\",\"categories\",\"stock_adjustments\",\"approval_stock_damage\",\"suppliers\",\"wh_restock\",\"restock_request_ap\",\"po\",\"packages\",\"company\",\"users\",\"roles\",\"bom\",\"warehouses\",\"wh_stocklevel\",\"goodreceived\",\"wh_issue\",\"wh_reconcile\",\"wh_direct_sales\",\"wh_sales_reports\",\"wh_transfers\",\"sales_return_approval\",\"wh_damaged_stocks\",\"sales_request_approval\",\"wh_settlements\",\"sales_daily\",\"sales_otp\",\"sales_return\",\"sales-handover-otp\",\"sales_request\",\"finance_settlements\"]','2026-06-22 02:56:17','2026-06-22 03:56:01'),(2,'Admin','admin','admin.dashboard','[\"products.view\",\"products.create\",\"products.update\",\"products.delete\",\"category.view\",\"category.create\",\"category.update\",\"category.delete\",\"stock_adjustments.view\",\"stock_adjustments.create\",\"stock_adjustments.export\",\"supplier.view\",\"supplier.create\",\"supplier.update\",\"supplier.delete\",\"po.view\",\"po.create\",\"po.update\",\"po.delete\",\"packages.view\",\"packages.create\",\"packages.update\",\"packages.delete\",\"company.view\",\"company.create\",\"company.update\",\"company.delete\",\"users.view\",\"users.create\",\"users.update\",\"users.delete\",\"users.bulk_delete\",\"users.export\",\"roles.view\",\"roles.create\",\"roles.update\",\"roles.delete\",\"bom.view\",\"bom.create\",\"bom.update\",\"bom.delete\",\"bom.produce\",\"warehouse.view\",\"warehouse.create\",\"warehouse.update\",\"warehouse.delete\"]','[\"products\",\"categories\",\"stock_adjustments\",\"approval_stock_damage\",\"suppliers\",\"wh_restock\",\"restock_request_ap\",\"po\",\"packages\",\"company\",\"users\",\"roles\",\"bom\",\"warehouses\",\"wh_stocklevel\",\"goodreceived\",\"wh_issue\",\"wh_reconcile\",\"wh_direct_sales\",\"wh_sales_reports\",\"wh_transfers\",\"sales_return_approval\",\"wh_damaged_stocks\",\"sales_request_approval\",\"wh_settlements\",\"sales_daily\",\"sales_otp\",\"sales_return\",\"sales-handover-otp\",\"sales_request\",\"finance_settlements\"]','2026-06-22 02:56:17','2026-06-22 03:55:39'),(3,'Warehouse','warehouse','warehouse.dashboard','[]','[\"warehouses\",\"wh_stocklevel\",\"goodreceived\",\"wh_issue\",\"wh_reconcile\",\"wh_direct_sales\",\"wh_sales_reports\",\"wh_transfers\",\"sales_return_approval\",\"wh_damaged_stocks\",\"sales_request_approval\",\"wh_settlements\",\"wh_restock\",\"users\"]','2026-06-22 02:56:17','2026-06-22 02:56:17'),(4,'Procurement','procurement','admin.dashboard','[]','[\"products\",\"packages\",\"categories\",\"suppliers\",\"warehouses\",\"wh_restock\",\"restock_request_ap\",\"po\",\"company\"]','2026-06-22 02:56:17','2026-06-22 02:56:17'),(5,'CEO','ceo','admin.dashboard','[]','[\"wh_sales_reports\",\"po\",\"company\"]','2026-06-22 02:56:17','2026-06-22 02:56:17'),(6,'Sales','sales','sales.dashboard','[]','[\"sales_daily\",\"sales_otp\",\"sales_return\",\"sales-handover-otp\",\"sales_request\"]','2026-06-22 02:56:17','2026-06-22 02:56:17');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_handover_items`
--

DROP TABLE IF EXISTS `sales_handover_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_handover_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `handover_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `qty_start` bigint(20) NOT NULL,
  `qty_returned` bigint(20) NOT NULL DEFAULT 0,
  `qty_sold` bigint(20) NOT NULL DEFAULT 0,
  `unit_price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `line_total_start` bigint(20) unsigned NOT NULL DEFAULT 0,
  `line_total_sold` bigint(20) unsigned NOT NULL DEFAULT 0,
  `payment_qty` bigint(20) NOT NULL DEFAULT 0,
  `payment_cash_qty` bigint(20) NOT NULL DEFAULT 0,
  `payment_cash_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `payment_transfer_qty` bigint(20) NOT NULL DEFAULT 0,
  `payment_transfer_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `payment_method` enum('cash','transfer') DEFAULT NULL,
  `payment_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `payment_transfer_proof_path` varchar(255) DEFAULT NULL,
  `payment_transfer_proof_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_transfer_proof_paths`)),
  `payment_status` enum('pending','approved','rejected') DEFAULT NULL,
  `payment_reject_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `discount_per_unit` bigint(20) unsigned NOT NULL DEFAULT 0,
  `discount_mode` varchar(20) NOT NULL DEFAULT 'unit',
  `discount_fixed_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `discount_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `unit_price_after_discount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `line_total_after_discount` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_handover_items_handover_id_product_id_unique` (`handover_id`,`product_id`),
  KEY `sales_handover_items_product_id_foreign` (`product_id`),
  KEY `sales_handover_items_payment_status_index` (`payment_status`),
  CONSTRAINT `sales_handover_items_handover_id_foreign` FOREIGN KEY (`handover_id`) REFERENCES `sales_handovers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_handover_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_handover_items`
--

LOCK TABLES `sales_handover_items` WRITE;
/*!40000 ALTER TABLE `sales_handover_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_handover_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_handover_payments`
--

DROP TABLE IF EXISTS `sales_handover_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_handover_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_handover_id` bigint(20) unsigned NOT NULL,
  `amount` bigint(20) NOT NULL DEFAULT 0,
  `payment_method` varchar(255) NOT NULL,
  `transfer_proof_path` text DEFAULT NULL,
  `payment_date` date NOT NULL,
  `received_by` bigint(20) unsigned NOT NULL,
  `settlement_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_handover_payments_sales_handover_id_foreign` (`sales_handover_id`),
  KEY `sales_handover_payments_received_by_foreign` (`received_by`),
  KEY `sales_handover_payments_settlement_id_foreign` (`settlement_id`),
  CONSTRAINT `sales_handover_payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_handover_payments_sales_handover_id_foreign` FOREIGN KEY (`sales_handover_id`) REFERENCES `sales_handovers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_handover_payments_settlement_id_foreign` FOREIGN KEY (`settlement_id`) REFERENCES `warehouse_settlements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_handover_payments`
--

LOCK TABLES `sales_handover_payments` WRITE;
/*!40000 ALTER TABLE `sales_handover_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_handover_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_handovers`
--

DROP TABLE IF EXISTS `sales_handovers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_handovers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `sales_id` bigint(20) unsigned NOT NULL,
  `handover_date` date NOT NULL,
  `status` enum('draft','waiting_morning_otp','on_sales','waiting_evening_otp','closed','cancelled') NOT NULL DEFAULT 'draft',
  `is_direct_sale` tinyint(1) NOT NULL DEFAULT 0,
  `buyer_type` varchar(20) NOT NULL DEFAULT 'sales',
  `customer_name` varchar(255) DEFAULT NULL,
  `pareto_id` bigint(20) unsigned DEFAULT NULL,
  `issued_by` bigint(20) unsigned NOT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `total_dispatched_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `total_sold_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `evening_filled_by_sales` tinyint(1) NOT NULL DEFAULT 0,
  `evening_filled_at` timestamp NULL DEFAULT NULL,
  `cash_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `transfer_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `transfer_proof_path` text DEFAULT NULL,
  `settlement_id` bigint(20) unsigned DEFAULT NULL,
  `morning_otp_hash` varchar(255) DEFAULT NULL,
  `morning_otp_sent_at` timestamp NULL DEFAULT NULL,
  `morning_otp_verified_at` timestamp NULL DEFAULT NULL,
  `evening_otp_hash` varchar(255) DEFAULT NULL,
  `evening_otp_sent_at` timestamp NULL DEFAULT NULL,
  `evening_otp_verified_at` timestamp NULL DEFAULT NULL,
  `discount_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `grand_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `discount_set_by` bigint(20) unsigned DEFAULT NULL,
  `discount_set_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_handovers_code_unique` (`code`),
  KEY `sales_handovers_warehouse_id_foreign` (`warehouse_id`),
  KEY `sales_handovers_sales_id_foreign` (`sales_id`),
  KEY `sales_handovers_issued_by_foreign` (`issued_by`),
  KEY `sales_handovers_closed_by_foreign` (`closed_by`),
  KEY `sales_handovers_discount_set_by_foreign` (`discount_set_by`),
  KEY `sales_handovers_handover_date_index` (`handover_date`),
  KEY `sales_handovers_status_index` (`status`),
  KEY `sales_handovers_is_direct_sale_index` (`is_direct_sale`),
  KEY `sales_handovers_buyer_type_index` (`buyer_type`),
  KEY `sales_handovers_customer_name_index` (`customer_name`),
  KEY `sales_handovers_settlement_id_foreign` (`settlement_id`),
  CONSTRAINT `sales_handovers_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_handovers_discount_set_by_foreign` FOREIGN KEY (`discount_set_by`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_handovers_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_handovers_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_handovers_settlement_id_foreign` FOREIGN KEY (`settlement_id`) REFERENCES `warehouse_settlements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_handovers_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_handovers`
--

LOCK TABLES `sales_handovers` WRITE;
/*!40000 ALTER TABLE `sales_handovers` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_handovers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_reports`
--

DROP TABLE IF EXISTS `sales_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `total_sold` bigint(20) NOT NULL DEFAULT 0,
  `total_revenue` bigint(20) NOT NULL DEFAULT 0,
  `stock_remaining` bigint(20) NOT NULL DEFAULT 0,
  `damaged_goods` bigint(20) NOT NULL DEFAULT 0,
  `goods_returned` bigint(20) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','verified') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_reports_sales_id_foreign` (`sales_id`),
  KEY `sales_reports_warehouse_id_foreign` (`warehouse_id`),
  KEY `sales_reports_approved_by_foreign` (`approved_by`),
  CONSTRAINT `sales_reports_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_reports_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_reports_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_reports`
--

LOCK TABLES `sales_reports` WRITE;
/*!40000 ALTER TABLE `sales_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_returns`
--

DROP TABLE IF EXISTS `sales_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `handover_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` bigint(20) NOT NULL,
  `condition` enum('good','damaged','expired') NOT NULL DEFAULT 'good',
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','received') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_returns_warehouse_id_foreign` (`warehouse_id`),
  KEY `sales_returns_handover_id_foreign` (`handover_id`),
  KEY `sales_returns_product_id_foreign` (`product_id`),
  KEY `sales_returns_approved_by_foreign` (`approved_by`),
  KEY `sales_returns_sales_id_warehouse_id_index` (`sales_id`,`warehouse_id`),
  CONSTRAINT `sales_returns_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_returns_handover_id_foreign` FOREIGN KEY (`handover_id`) REFERENCES `sales_handovers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_returns_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_returns_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_returns_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_returns`
--

LOCK TABLES `sales_returns` WRITE;
/*!40000 ALTER TABLE `sales_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustment_items`
--

DROP TABLE IF EXISTS `stock_adjustment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustment_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `qty_before` bigint(20) NOT NULL,
  `qty_after` bigint(20) NOT NULL,
  `qty_diff` bigint(20) NOT NULL,
  `purchase_price_before` bigint(20) DEFAULT NULL,
  `purchase_price_after` bigint(20) DEFAULT NULL,
  `selling_price_before` bigint(20) DEFAULT NULL,
  `selling_price_after` bigint(20) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_adjustment_items_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  KEY `stock_adjustment_items_product_id_index` (`product_id`),
  CONSTRAINT `stock_adjustment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustment_items`
--

LOCK TABLES `stock_adjustment_items` WRITE;
/*!40000 ALTER TABLE `stock_adjustment_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_adjustment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `adj_code` varchar(255) NOT NULL,
  `stock_scope_mode` varchar(20) NOT NULL DEFAULT 'single',
  `price_update_mode` varchar(30) NOT NULL DEFAULT 'none',
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `adj_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_adjustments_adj_code_unique` (`adj_code`),
  KEY `stock_adjustments_warehouse_id_foreign` (`warehouse_id`),
  KEY `stock_adjustments_created_by_foreign` (`created_by`),
  CONSTRAINT `stock_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `stock_adjustments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_levels`
--

DROP TABLE IF EXISTS `stock_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_levels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_type` enum('pusat','warehouse','sales') NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_levels_product_id_foreign` (`product_id`),
  CONSTRAINT `stock_levels_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_levels`
--

LOCK TABLES `stock_levels` WRITE;
/*!40000 ALTER TABLE `stock_levels` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `from_type` enum('supplier','pusat','warehouse','sales') DEFAULT NULL,
  `from_id` bigint(20) unsigned DEFAULT NULL,
  `to_type` enum('pusat','warehouse','sales') DEFAULT NULL,
  `to_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` bigint(20) NOT NULL,
  `status` enum('pending','approved','completed','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_product_id_foreign` (`product_id`),
  KEY `stock_movements_approved_by_foreign` (`approved_by`),
  CONSTRAINT `stock_movements_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_requests`
--

DROP TABLE IF EXISTS `stock_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity_requested` bigint(20) NOT NULL,
  `quantity_approved` bigint(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `sales_handover_id` bigint(20) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_requests_product_id_foreign` (`product_id`),
  KEY `stock_requests_approved_by_foreign` (`approved_by`),
  KEY `stock_requests_sales_handover_id_foreign` (`sales_handover_id`),
  KEY `stock_requests_status_index` (`status`),
  KEY `stock_requests_user_id_index` (`user_id`),
  KEY `stock_requests_warehouse_id_index` (`warehouse_id`),
  CONSTRAINT `stock_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_requests_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_requests_sales_handover_id_foreign` FOREIGN KEY (`sales_handover_id`) REFERENCES `sales_handovers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_requests_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_requests`
--

LOCK TABLES `stock_requests` WRITE;
/*!40000 ALTER TABLE `stock_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_supplier_code_unique` (`supplier_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'SUP-0001','PT Indosat Ooredoo Hutchison Tbk (IOH)','Jln Raya Medan,Jakarta','085632351489','Supplier utama (IOH)','BCA','2026345','2026-06-22 02:56:17','2026-06-22 02:56:17');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  KEY `users_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `users_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Super Admin','admin','admin@local','081200000001',NULL,'ImageAsset/1.jpg','$2y$12$ppKlEsp.8yckS0J4kB88cuTkfCoNj6QU88bWw1MgXcyXkIqCX6FGW',NULL,'active',NULL,'2026-06-22 02:56:18','2026-06-22 03:56:30'),(2,'Admin DEPO Bukittinggi','wh_bukittinggi','wh_bukittinggi@local','081200000010','Warehouse Admin','ImageAsset/3.jpg','$2y$12$y09Pr8hF/q2DkaMesQ104OmMstkLDdW4zjjwnSO464PpUpz7RcX9m',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(3,'Admin DEPO Padang','wh_padang','wh_padang@local','081200000011','Warehouse Admin','ImageAsset/3.jpg','$2y$12$JXubvgsfsUtDM.pVScfmoucGCDWxmaDxD2ILBzOpVDwZsR1j/Y.3W',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(4,'Sales DEPO Padang','Spadang','sales_padang@local','081200000013','Sales','ImageAsset/5.jpg','$2y$12$tm1GKKsjQqsfVvLEFH/HHeIBCyuUCCsqrw/M109IJCEvfb7lps5.O',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(5,'Sales Padang Rudi','Rudi','sales_padang@loscal','081200000014','Sales','ImageAsset/5.jpg','$2y$12$RNP5v/qAY25Beyfy5ugfUOWZX3YtrWL5Yv7SgP29ypJIzwHHj3J0S',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(6,'User Procurement','procurement','procurement@local','081200000015','Procurement','ImageAsset/4.jpg','$2y$12$MG.QktEfVG6uPW09iqKEc.d0G0UkqRlt4ciuqWckJh6zG.KbkbkaK',NULL,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(7,'Chief Executive Officer','ceo','ceo@local','081200000016','CEO','ImageAsset/1.jpg','$2y$12$lpEEmc1kQ59plJUkZWraJ.yxtDqeM.pVSx2NIjX3LYAoEIO9FsFz6',NULL,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(8,'Siti Chotijah','Dharmasraya','sitichotijah.mp3@gmail.com','0895401157050','Admin WH','signatures/DLbdeOeggE7SkaD4WLzu7XT1YWSeVgGSiSXFEUjf.jpg','$2y$12$dkFL14aBNKEJws3bgLbkcOb3AnwJUF2LxkaQFyRAaxSeTOQ3W4qum',3,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(9,'Ika Reskian Tomi','Solok','admnsolok@gmail.com','0895405405293','admin wh',NULL,'$2y$12$LH0G7Qy3UnJzL2BX4flO0eUPr8Qm7WLsGHvzwnsjiKXoE216n21Jm',8,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(10,'Negal Neglas Sari','Tanah_Datar','negalneglasari@gmail.com','085278057526','Admin Wh',NULL,'$2y$12$mVrYCXESO9//AaKblFQXv.Oz51CZTYmlASOE1rnVqfj5Hk9PgILDC',9,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(11,'Selvi Tryen Ningsih','Pasaman_Barat','selvitryenningsih.mp3@gmail.com','089505569181','Admin Wh',NULL,'$2y$12$1l/XACDmlYNSD1n55dFHW.PvjZ/y/ahjipboRYp3JQvYkE14d8TWu',5,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(12,'Indah Agustin','Payakumbuh','indahagstn.28@gmail.com','089530847959','Admin Wh',NULL,'$2y$12$R3CwF4aGcTctxCmLXIjSU.CDh/Rrt2XumYZpilNH7Xg409yFGeeJG',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(13,'Friska Diani Sakinah','Bukittinggi','friskadiani.mp3@gmail.com','085265635659','Admin Wh',NULL,'$2y$12$prARBdDgJmMySRk7/Xod9OMrp8PQ04MFWjmLVWicgPNWvf5fYkF.a',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(14,'YORRI HASMI PERTIWI','Padang_A','yorrihasmi22@gmail.com','089672310041','Admin Wh','signatures/S3a6sMg8Z0AQnoojOLvxa0Dr9oiAxtaTHFHj9sRg.jpg','$2y$12$JuI6g9hV0JfxYxC/OW3r4eCbRQOjkNU.MlFtI8M/nBl8i86bDpc9e',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(15,'Rahmawati','Padang_B','rahmawati1005@gmail.com','0895320808288','Admin Wh',NULL,'$2y$12$kWzPew5frPveA1LD1brD7uekyOtwiYDyYTzuttVIeR0nzsRzuYge.',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(16,'Sarmila','Pariaman','sarmila1.mp3@gmail.com','089529943710','Admin Wh',NULL,'$2y$12$HQuG9O0wgum/RJzzn3Njh.OqTLt/pC4n.qWVdf3L9RMgBjr0CsPoa',4,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(17,'Painan','Painan','Painan@mandau.id','098765437','Admin Wh',NULL,'$2y$12$fcZdASVm.pE1a1ovBr.Vn.qPS8Asbv5nAYhNpb67UBV25SkVJbCPO',11,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(18,'NIKO IRAWAN','Niko_Irawan','nikoirawan00@gmail.com','089521202061','Sales Area Sijunjung','signatures/SNzebi6a4UDuSe6h9I39OphZsL0gX6oe6EdvdCe2.jpg','$2y$12$9EhiA1mCftWKWLS78O0VQ.KPGGY6QQQSMLI5zP9WkR6ydfxKUPwFy',3,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(19,'DICKI FEBERIADI','Dicki_Feberiadi','dickifeberiadi@gmail.com','0895352993977','Sales Area Koto Baru','signatures/eAHxEPjVmxyobStjZ8tl1im7wlHegfQNyV0JpqDV.jpg','$2y$12$wrvqieotjLYl4BpjSNnzDeovGXgoMg1dYqe6C3wYbbR./MoVAgCWq',3,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(20,'Chenthia Budiharto','Chenthia_Budiharto','chenthia.budiharto@mandau.id',NULL,NULL,NULL,'$2y$12$Wb9vimgsb.rjN7E.bu.rKehgvECJW.6wL86gfIgtXALx1Qgng9gHO',NULL,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(21,'Andi','Andi_sales','Andi_sales@gmail.com','085765328975','Sales','signatures/SNzebi6a4UDuSe6h9I39OphZsL0gX6oe6EdvdCe2.jpg','$2y$12$svYzH6G3mzow/WwIJxvGTeWTykd7jeT6FzZBWpPYQR9d1.EgsDKK2',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(22,'Ajif Agustian','Sales_Mandiangin','ajifagustian3@gmail.com','089521202052','Sales',NULL,'$2y$12$qJMMgHZn2BxsTAMboLkBUupwK1qMm1dBaCYaiUjAWjKvcASCnB26K',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(23,'Hendra Marpaung','Sales_AmpekAngkek','fahimna11889@gmail.com','08974551415','Sales',NULL,'$2y$12$W1jD5zlEVq/kJsB03VvjduYRCu1rD/1jJSULOTIOSipxC92SkwJW6',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(24,'Arif Febri Hendi','Sales_Tigobaleh','ariffebrihendi@gmail.com','089517965930','Sales',NULL,'$2y$12$Jr2wfWax6dm60BrSH4RyPOAPtAp51mp50aOjQ9lVvX.GG61ESl.Qm',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(25,'Randi Gunawan','Sales_GuguakPanjang','randigun280294@gmail.com','089521202048','Sales',NULL,'$2y$12$dNEAy3xcmlkTrByiUXcze.gxyfFItxiWaQDd23fseFoKo60JziRD2',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(26,'Ilham Pasundan','Sales_Banuhampu','Ilhampas07@gmail.com','089523006007','Sales',NULL,'$2y$12$k3yc23NsCBYcKKpChSgnYea88ip9aLsZbq/wQ/JNdFkSAXwlbqP9m',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(27,'Rio Marlatif','Sales_SungaiPua','riomarlatif7@gmail.com','08974551416','Sales',NULL,'$2y$12$f1hF9Ye7ElCLGjplHwk1BeFRZzblyfJkwI7SNXZd50suvQZZTyluS',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(28,'Rizky Prima Saputra','Sales_Bonjol','rizkyprima32@gmail.com','08982607512','Sales',NULL,'$2y$12$qhb.WYAhIbasFUQPFrciqegix/xdPEMqklKbf/dzXWk5maQfN9muq',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(29,'Yoga Athalauza','Sales_TilatangKamang','yogaathalauza56@gmail.com','0895329873050','Sales',NULL,'$2y$12$l9ctPIlXeC8RTh1ev0fETud2MRMR6uKbaFSGkgcCLOWKkqmUL.BwW',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(30,'Andri Refriandi','Sales_Baso','andrirefriandi219@gmail.com','089639058585','Sales',NULL,'$2y$12$/c9JG2UWWrr0dEBuxYPnHePR5pC2Cz35oiELedLUxUCjYh/WASZF6',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(31,'Budi Setiawan','Sales_KamangMagek','bs136515@gmail.com','089639060001','Sales',NULL,'$2y$12$QCyGLfvdZGA69alb1bHCdep.J9Q3WE0yS20X/KZuToNlzIoSCrc3G',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(32,'zidan','zidan_sales','zidan_sales@gmail.com',NULL,'Sales',NULL,'$2y$12$pZX6fq4fbz3O0ZcMguyGs.a3q3rn0puCXnSd5lY2QvR11FyZ.IyfC',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(33,'DARMAN ATIFAH','SE_ANDALAS','darmanatifah@gmail.com','08982607516','SALES',NULL,'$2y$12$3vb6odGcoF6kLE4B9EcoB.bOkM2ga8eINk/eSzjW7QjYzv95Xy.Xy',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(34,'ARLAN NUARI','SE_BATASKOTA','arlan.nuari1101@gmail.com','089517965939','Sales',NULL,'$2y$12$f24tpzsA36lbwI5qkZ6yleT21GfvD7DP5VFmaZQns7URglQeR5/iK',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(35,'BULFI KAHFI','SE_BUNGUS','bulfikahfi166@gmail.com','089517965943','SALES',NULL,'$2y$12$VdFqHWKVIWkNAjwZv3JFMuActSBAs2bENufuxCsyBXz3BwQJMSxq6',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(36,'TEDDY ERLANGGA','SE_MATAAIR','teddyerlangga87@gmail.com','08982607519','SALES',NULL,'$2y$12$HKywGqatwJFQXdXXTHUWbetb2obdJDEGSPEzWBS.tTPtyOW.gWZO2',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(37,'RONAL AGUSTO','SE_PENGAMBIRAN','ronalagusto29@gmail.com','089521202068','SALES',NULL,'$2y$12$B7y9GU1uE8t6m6Y4WYzBbO27YrQtTrwHVbhq0t5FznWmYWuj1wp.y',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(38,'YOGIE DWI SYAHPUTRA','SE_PONDOK','yogiedwisyahputra0060@gmail.com','089521202069','SALES',NULL,'$2y$12$HxdkwyPR.K0nHsjXdSCHh.LNw4i.lvZsx1qYE4i29/HexwS4xd5Ui',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(39,'Agestya Heryan Pramana','Sales_PayakumbuhTimur','agestyaherian91@gmail.com','089521202054','Sales',NULL,'$2y$12$AicV0tKmHfuqlmtwkN7PXOhypDqnkX0adzF7kNyiJlIkrC.w3z7k.',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(40,'Diko Aditya Jufri','Sales_PayakumbuhUtara','dikoaditya410@gmail.com','08974551412','Sales',NULL,'$2y$12$aAX0ApTxuRN3rCQ39NesXe9BV6SlYvMokRGRsSC7Aj8TRTI0u2riq',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(41,'Muhammad Arsil','Sales_Lampasi','acilcilsilia@gmail.com','089521202049','Sales',NULL,'$2y$12$N1vdLXnn1qD5hwbvsKHkr.LAuihwGV5oyB3DjNWqG9vPkc1I997dy',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(42,'Edoni Eka Putra','Sales_BukitBarisan','dhonivivo1992@gmail.com','089679397939','Sales',NULL,'$2y$12$leCJVamS21dc9zJBmMCqUOh0WowK.XMiO0jlYRdWYt0dYTj81Qotu',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(43,'Fitra','Sales_Situjuah','fitrambs7588@gmail.com','089512126969','Sales',NULL,'$2y$12$BrQG5U6lxpeNt2sJgXqQOuBBArnKtOGFId/pIosEiTY39p3a8RF3a',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(44,'Wesqarni','Sales_BatangTabik','wwesqarni@gmail.com','089521202046','Sales',NULL,'$2y$12$sB/0T5WEc2cAU/bDJy8HP.g23sXJDr2sOSOTu76GoobwtzJV6ypI2',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(45,'Rayes Syah Eko Putra','Sales_GuguakMungka','rayes9687@gmail.com','089617965929','Sales',NULL,'$2y$12$q5jqk5nSAw75lwpx9nyNAu/VHmzKMg7RFea4OLtvGW/DuyJi570UG',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(46,'Agus Purnomo','Sales_PayakumbuhBarat','agustpurnomo99@gmail.com','08974551413','Sales',NULL,'$2y$12$CAfvqdNEbc4lP7EV98at/u4mRs/tDZ8TSfAllyu1yiefTnWWCbBUm',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(47,'Ardian Taurus Tio','Sales_Akabiluru','ardiantaurustio@gmail.com','08997481577','Sales',NULL,'$2y$12$JmJLv7Y1MpPW06yxBXiQ..qz4uIQQI3CsmVi0AbpoipLdg0T9sG9S',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(48,'Ramadhatul Fauzi','Sales_Pangkalan','ezi.changcut@gmail.com','089521202058','Sales',NULL,'$2y$12$z5jGwUj2m7/2Gaz8uQCHnOvGvAtP88nyOVZ5dHBra/JBRwvHSd97O',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(49,'DEVID AGUSTIN','SE_PASARBARU','devid.agustin22@gmail.com','089521202067','SALES',NULL,'$2y$12$BXtNhkNuHQoyQk9uan.Qs.Hx4rTzVctape5DGzrUrRK6AnVmg9nHm',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(50,'RANDY DWITA PUTRA','SE_BANDARBUAT','randydp8@gmail.com','08982607518','SALES',NULL,'$2y$12$5sCbkITyJlb3nRNIYiLVE.C6/x6ZtzjD/zOMhV4EVlLCfpekrAm9q',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(51,'RANDIKA CHANDRA PUTRA','SE_LUBUKLINTAH','randikachandra1996@gmail.com','089521202064','SALES',NULL,'$2y$12$z95ay9A3oJeBeuHt2JRgLOJ9oeBDue.TU8gdWt95KVZjNDDSxLPcO',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(52,'Agung Adytia Pratama','Dse_Pasaman','agungadytiapratama08@gmail.com','08991280904','Sales',NULL,'$2y$12$OA84Dgc9geF84SNv8aHOgOFTg72VZ.jbC5iYaYwwAm3NKBvqlDnle',5,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(53,'Pratama Yoga Ardi','Dse_Kinali','yogadesi62@gmail.com','089673497759','Sales',NULL,'$2y$12$s4Vd1wO05nkEn.QhwPkYf.bw5F6ct0KAzI.dQGpysAiDS.kIyV1EO',5,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(54,'Bastian Ramadhan','Dse_LuhakNanDuo','Bstianrmdhn@gmail.com','0895324918883','Sales',NULL,'$2y$12$GMO8by76Plq6KV6a5LISxeQ2.dXZr3miv9WEP/1GHh4ORVYl0gDrq',5,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(55,'Lukman Hakim','Dse_GunungTuleh','lh607434@gmail.com','085274782873','Sales',NULL,'$2y$12$mEopZFK48UeORQpgBrbL0e4DBIWfZyz6EaeQ/YnrVqHHTV23Xxlqa',5,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(56,'GUNTUR CANTA','SE_LAPAI','caniagoguntur@gmail.com','08982607514','SALES',NULL,'$2y$12$TK41yMzriSSWs5llzUC4wOvG6ng0P2vVmtiWUH.3TknoPBuqZJ5ra',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(57,'RIDWAN EFENDI','SE_DADOK','eridwan40@gmail.com','0895405394425','SALES',NULL,'$2y$12$8FPlTqZAJzapfrlfMlRcNuyf2.EaKDtAQaDF/jlTGGiIXOOOahsfK',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(58,'EKO SAPUTRA','SE_KALAWI','saputraeko69019@gmail.com','089521202066','SALES',NULL,'$2y$12$DOnMvc3XEyAbecR4bsEf3.RExUZj5srKgbbQXcHs16TdYYZSg51py',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(59,'KHEVIN PRANIZAL ILLAHI','SE_KALUMPANG','alexsaander229@gmail.com','089517965940','SALES',NULL,'$2y$12$r.QurjNPOual39exBAYp..XzHpHCe4EHqnFWPBDsj.AnQMcsQzxnu',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(60,'BAYU SAPUTRA','SE_TARUKO','aziusaputra4@gmail.com','089521202071','SALES',NULL,'$2y$12$LYNgWyk21GVB7oKvI5Ke.uUQFtMWf19NmPv06xgzHaTfELkcPRV82',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(61,'REVI RESTIADI','SE_LUMIN','seivercogah@gmail.com','089517965941','SALES',NULL,'$2y$12$Y6OtwHFq5XCUhYls4ffzCuzVVLSpoOT3JRlb40F9emGn2LtxF38WK',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(62,'M. QHODRI','SE_AIRTAWAR','qhodri234@gmail.com','0895386884522','SALES',NULL,'$2y$12$t4vTeBxSpa4f2YUmJ6D31eoDdGdPZoddme67/yKUqjSuDTipQgaQG',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(63,'KOKOH MUHARDI','SE_BELIMBING','kokohmuhardi@gmail.com','08982607513','SALES',NULL,'$2y$12$5iQuzLO62yLlYp9KA.kbgu9fBHLmGjQ2T.4ytqHPU7WMOMjTRUcmi',10,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(64,'Difo Gunawan','Difo_Gunawan','difodege@gmail.com','08974551418','Sales Lima Kaum',NULL,'$2y$12$TydmW/90R/BFwj9u9U00ROxA2dXHlp0DBEtRZBHYfjNv5Xvjj8XWO',9,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(65,'Ilham Wahyudi','Ilham_Wahyudi','ilhamwahyudi0474@gmail.com','08982370600','Sales Salimpaung',NULL,'$2y$12$CrbHO8NfmH0RVgRpVdHQj.oXgRTDxGM/cIP6Nho2ZK4nYmjWKPZHO',9,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(66,'Arif Rahman','Arif_Rahman','rahmanarif0429@gmail.com','08982455554','Sales Sungai Tarab',NULL,'$2y$12$Evjdkb.vcpZsPoQ8pDdIteQdWwm5IropWtHOqJ3quezHckp1CC8Vm',9,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(67,'Rindi Oktavianus','Rindi_Oktavianus','rindioktavianus7@gmail.com','089521202083','Sales Batipuh',NULL,'$2y$12$62Df35a84sO5hv7anJwIYOpsvRz/DI3QG6SSkIjbAWX6.PHjacT/2',9,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(68,'Adri Nofrizal','Adri_Nofrizal','anofrizal99@gmail.com','08974551417','Sales Padang Panjang Barat',NULL,'$2y$12$aQzMbJaw5nzWpzZj.d71GuVRJyBm3zd1QUNDyNEuGNy4jJSt/UGlC',9,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(69,'Jefri','Jefri_','jefri8142@gmail.com','0889521202085','Sales Padang Panjang Timur',NULL,'$2y$12$mBSfw/PD7/H73dgJPQeAout5mPOK6xeptWSrvGK47wx5rrmTxhVNW',9,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(70,'RISKI GISFA RIANTO','Riski_Gisfa','riskigisfarianto@gmail.com','089519294518','Sales Area Pulau Punjung','signatures/TAph6SZyS1D0xPANk0fdXuP5lNH8CqV5r7njYiCB.jpg','$2y$12$4fJAOSlP5aZrBNdv9grDI.IiXXES9vycHw0XhxROOv3AskNPkrQqq',3,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(71,'Sales internal Dharmasraya','sales_dhamasraya','SalesinternalDharmasraya@gmail.com','081200000099','Sales',NULL,'$2y$12$67JEJJpggQqLk9baeZx6QenkG6MvS7.JUCRTToa.AVgIEudMEGVMO',3,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(72,'Sales internal Solok','sales_solok','SalesinternalSolok@gmail.com','081200000098','Sales',NULL,'$2y$12$BtJsX5iBJX5817JswHQTjeGQELCsemE57VSxPGde2JlU2x1qP8Gbe',8,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(73,'Sales internal Tanah Datar','sales_tanahdatar','SalesinternalTanahDatar@gmail.com','081200000097','Sales',NULL,'$2y$12$pRF4fOkFNpZNmu0x8tLRQuZhBPzty66OHoUC/mIW5gywf7D3/C1S2',9,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(74,'Sales internal Pasaman Barat','sales_pasamanbarat','SalesinternalPasamanBarat@gmail.com','081200000096','Sales',NULL,'$2y$12$d28YJu.ZObNHzwZGlcO/mOFId4m3qgQVMt7wmNhlCOqK7pnpo1wdO',5,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(75,'Sales internal Payakumbuh','sales_payakumbuh','SalesinternalPayakumbuh@gmail.com','081200000095','Sales',NULL,'$2y$12$y74ts/NRL9l6DqtfoBAVOuGJqnI/.woK0A1O3QN7pxHWjchB9I6jm',6,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(76,'Sales internal Bukittinggi','sales_bukittinggi','SalesinternalBukittinggi@gmail.com','081200000094','Sales',NULL,'$2y$12$2sl3sEPoo84lFAjQ2C5qCO.IsD7.8KM1cDqphEQrrwoO2EneWXu5.',1,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(77,'Sales internal Padang','sales_padang12','SalesinternalPadang@gmail.com','081200000093','Sales',NULL,'$2y$12$.FVOWssLtKSWxjvqjxiMseKe3dGeYwgV5HWxyVWEOwoVTuuZHjDki',2,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(78,'Sales internal Pariaman','sales_pariaman','SalesinternalParlaman@gmail.com','081200000092','Sales',NULL,'$2y$12$JmoAXjHdOj5NXPb1dFEOBuqcqBDHauSQ6aT4xznr7YkzbSdJZ5aGy',4,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(79,'Sales internal Painan','sales_painan','SalesinternalPainan@gmail.com','081200000091','Sales',NULL,'$2y$12$YpJJOY4Bf3FoatDrdDrWy.z3987nwT78OVgS5N4Zc0GiByLp0aGCG',11,'active',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_settlements`
--

DROP TABLE IF EXISTS `warehouse_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `admin_id` bigint(20) unsigned NOT NULL,
  `settlement_date` date NOT NULL,
  `total_cash_amount` bigint(20) NOT NULL DEFAULT 0,
  `total_transfer_amount` bigint(20) NOT NULL DEFAULT 0,
  `proof_path` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_settlements_warehouse_id_foreign` (`warehouse_id`),
  KEY `warehouse_settlements_admin_id_foreign` (`admin_id`),
  CONSTRAINT `warehouse_settlements_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `warehouse_settlements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_settlements`
--

LOCK TABLES `warehouse_settlements` WRITE;
/*!40000 ALTER TABLE `warehouse_settlements` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_transfer_items`
--

DROP TABLE IF EXISTS `warehouse_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_transfer_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_transfer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `qty_transfer` bigint(20) NOT NULL,
  `qty_good` bigint(20) DEFAULT NULL,
  `qty_damaged` bigint(20) DEFAULT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `subtotal_cost` decimal(15,2) NOT NULL,
  `photo_good` varchar(255) DEFAULT NULL,
  `photo_damaged` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_transfer_items_warehouse_transfer_id_foreign` (`warehouse_transfer_id`),
  KEY `warehouse_transfer_items_product_id_foreign` (`product_id`),
  CONSTRAINT `warehouse_transfer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `warehouse_transfer_items_warehouse_transfer_id_foreign` FOREIGN KEY (`warehouse_transfer_id`) REFERENCES `warehouse_transfers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_transfer_items`
--

LOCK TABLES `warehouse_transfer_items` WRITE;
/*!40000 ALTER TABLE `warehouse_transfer_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_transfer_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_transfer_logs`
--

DROP TABLE IF EXISTS `warehouse_transfer_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_transfer_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_transfer_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `performed_by` bigint(20) unsigned NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_transfer_logs_warehouse_transfer_id_foreign` (`warehouse_transfer_id`),
  KEY `warehouse_transfer_logs_performed_by_foreign` (`performed_by`),
  CONSTRAINT `warehouse_transfer_logs_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `warehouse_transfer_logs_warehouse_transfer_id_foreign` FOREIGN KEY (`warehouse_transfer_id`) REFERENCES `warehouse_transfers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_transfer_logs`
--

LOCK TABLES `warehouse_transfer_logs` WRITE;
/*!40000 ALTER TABLE `warehouse_transfer_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_transfer_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_transfers`
--

DROP TABLE IF EXISTS `warehouse_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_code` varchar(255) NOT NULL,
  `source_warehouse_id` bigint(20) unsigned NOT NULL,
  `destination_warehouse_id` bigint(20) unsigned NOT NULL,
  `status` enum('draft','pending_destination','approved','gr_source','completed','rejected','canceled') NOT NULL DEFAULT 'draft',
  `total_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned NOT NULL,
  `approved_destination_by` bigint(20) unsigned DEFAULT NULL,
  `approved_destination_at` timestamp NULL DEFAULT NULL,
  `gr_source_by` bigint(20) unsigned DEFAULT NULL,
  `gr_source_at` timestamp NULL DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_transfers_transfer_code_unique` (`transfer_code`),
  KEY `warehouse_transfers_source_warehouse_id_foreign` (`source_warehouse_id`),
  KEY `warehouse_transfers_destination_warehouse_id_foreign` (`destination_warehouse_id`),
  KEY `warehouse_transfers_created_by_foreign` (`created_by`),
  KEY `warehouse_transfers_approved_destination_by_foreign` (`approved_destination_by`),
  KEY `warehouse_transfers_gr_source_by_foreign` (`gr_source_by`),
  CONSTRAINT `warehouse_transfers_approved_destination_by_foreign` FOREIGN KEY (`approved_destination_by`) REFERENCES `users` (`id`),
  CONSTRAINT `warehouse_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `warehouse_transfers_destination_warehouse_id_foreign` FOREIGN KEY (`destination_warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `warehouse_transfers_gr_source_by_foreign` FOREIGN KEY (`gr_source_by`) REFERENCES `users` (`id`),
  CONSTRAINT `warehouse_transfers_source_warehouse_id_foreign` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_transfers`
--

LOCK TABLES `warehouse_transfers` WRITE;
/*!40000 ALTER TABLE `warehouse_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_code` varchar(50) NOT NULL,
  `warehouse_name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_warehouse_code_unique` (`warehouse_code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,'DEPO-BUKITTINGGI','DEPO BUKITTINGGI','DEPO BUKITTINGGI','Cabang DEPO BUKITTINGGI','2026-06-22 02:56:17','2026-06-22 02:56:17'),(2,'DEPO-PADANG-A','DEPO PADANG A','DEPO PADANG A','DEPO PADANG A','2026-06-22 02:56:17','2026-06-22 02:56:17'),(3,'DEPO-DHARMASRAYA','DEPO DHARMASRAYA','DEPO DHARMASRAYA','Cabang DEPO DHARMASRAYA','2026-06-22 02:56:17','2026-06-22 02:56:17'),(4,'DEPO-PARIAMAN','DEPO PARIAMAN','DEPO PARIAMAN','Cabang DEPO PARIAMAN','2026-06-22 02:56:18','2026-06-22 02:56:18'),(5,'DEPO-PASAMAN-BARAT','DEPO PASAMAN BARAT','DEPO PASAMAN BARAT','Cabang DEPO PASAMAN BARAT','2026-06-22 02:56:18','2026-06-22 02:56:18'),(6,'DEPO-PAYAKUMBUH','DEPO PAYAKUMBUH','DEPO PAYAKUMBUH','Cabang DEPO PAYAKUMBUH','2026-06-22 02:56:18','2026-06-22 02:56:18'),(7,'DEPO-PESISIR-SELATAN','DEPO PESISIR SELATAN','DEPO PESISIR SELATAN','Cabang DEPO PESISIR SELATAN','2026-06-22 02:56:18','2026-06-22 02:56:18'),(8,'DEPO-SOLOK','DEPO SOLOK','DEPO SOLOK','Cabang DEPO SOLOK','2026-06-22 02:56:18','2026-06-22 02:56:18'),(9,'DEPO-TANAH-DATAR','DEPO TANAH DATAR','DEPO TANAH DATAR','Cabang DEPO TANAH DATAR','2026-06-22 02:56:18','2026-06-22 02:56:18'),(10,'DEPO-PADANG-B','DEPO PADANG B','DEPO PADANG B','','2026-06-22 02:56:18','2026-06-22 02:56:18'),(11,'DEPO-PAINAN','DEPO PAINAN','DEPO PAINAN','','2026-06-22 02:56:18','2026-06-22 02:56:18');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-22 12:22:05
