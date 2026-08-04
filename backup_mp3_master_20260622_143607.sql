-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: mp3_master
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2024_01_01_000000_create_master_tables',1),(2,'2025_09_30_000002_create_roles_table',2),(3,'2025_09_30_000003_create_role_user_table',3),(4,'2014_10_12_100000_create_password_reset_tokens_table',4),(5,'2019_08_19_000000_create_failed_jobs_table',5),(6,'2019_12_14_000001_create_personal_access_tokens_table',6);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
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
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `db_name` varchar(255) NOT NULL,
  `db_host` varchar(255) NOT NULL DEFAULT '127.0.0.1',
  `db_port` int(11) NOT NULL DEFAULT 3306,
  `db_username` varchar(255) NOT NULL DEFAULT 'root',
  `db_password` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_db_name_unique` (`db_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'Mandau','mandau_db','127.0.0.1',3306,'root','',1,'2026-06-22 05:24:17','2026-06-22 05:24:17');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
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
INSERT INTO `role_user` VALUES (1,1);
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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','admin',NULL,NULL,NULL);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_projects`
--

DROP TABLE IF EXISTS `user_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_projects_user_id_project_id_unique` (`user_id`,`project_id`),
  KEY `user_projects_project_id_foreign` (`project_id`),
  CONSTRAINT `user_projects_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_projects`
--

LOCK TABLES `user_projects` WRITE;
/*!40000 ALTER TABLE `user_projects` DISABLE KEYS */;
INSERT INTO `user_projects` VALUES (1,41,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(2,1,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(3,9,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(4,39,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(5,52,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(6,46,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(7,22,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(8,59,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(9,21,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(10,30,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(11,68,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(12,47,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(13,24,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(14,34,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(15,60,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(16,31,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(17,54,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(18,35,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(19,56,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(20,7,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(21,20,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(22,33,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(23,49,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(24,42,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(25,19,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(26,64,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(27,40,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(28,57,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(29,48,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(30,23,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(31,43,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(32,13,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(33,26,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(34,65,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(35,12,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(36,69,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(37,63,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(38,55,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(39,10,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(40,18,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(41,17,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(42,6,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(43,62,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(44,66,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(45,15,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(46,25,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(47,51,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(48,50,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(49,45,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(50,67,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(51,27,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(52,70,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(53,28,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(54,37,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(55,4,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(56,5,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(57,76,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(58,71,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(59,77,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(60,79,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(61,78,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(62,74,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(63,75,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(64,72,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(65,73,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(66,58,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(67,16,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(68,61,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(69,11,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(70,8,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(71,36,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(72,2,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(73,3,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(74,44,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(75,29,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(76,53,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(77,38,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(78,14,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25'),(79,32,1,'admin','2026-06-22 05:24:25','2026-06-22 05:24:25');
/*!40000 ALTER TABLE `user_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_sessions_user_id_foreign` (`user_id`),
  KEY `user_sessions_project_id_foreign` (`project_id`),
  CONSTRAINT `user_sessions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
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
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Super Admin','admin','admin@local','081200000001',NULL,'ImageAsset/1.jpg',NULL,'active',NULL,'$2y$12$7iq7RA6J2NMp62erObVzpOOIXbMq.0yexwCTUz5STuU5vXezQw2Wi',NULL,'2026-06-22 02:56:18','2026-06-22 03:56:30'),(2,'Admin DEPO Bukittinggi','wh_bukittinggi','wh_bukittinggi@local','081200000010','Warehouse Admin','ImageAsset/3.jpg',1,'active',NULL,'$2y$12$y09Pr8hF/q2DkaMesQ104OmMstkLDdW4zjjwnSO464PpUpz7RcX9m',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(3,'Admin DEPO Padang','wh_padang','wh_padang@local','081200000011','Warehouse Admin','ImageAsset/3.jpg',2,'active',NULL,'$2y$12$JXubvgsfsUtDM.pVScfmoucGCDWxmaDxD2ILBzOpVDwZsR1j/Y.3W',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(4,'Sales DEPO Padang','Spadang','sales_padang@local','081200000013','Sales','ImageAsset/5.jpg',2,'active',NULL,'$2y$12$tm1GKKsjQqsfVvLEFH/HHeIBCyuUCCsqrw/M109IJCEvfb7lps5.O',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(5,'Sales Padang Rudi','Rudi','sales_padang@loscal','081200000014','Sales','ImageAsset/5.jpg',2,'active',NULL,'$2y$12$RNP5v/qAY25Beyfy5ugfUOWZX3YtrWL5Yv7SgP29ypJIzwHHj3J0S',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(6,'User Procurement','procurement','procurement@local','081200000015','Procurement','ImageAsset/4.jpg',NULL,'active',NULL,'$2y$12$MG.QktEfVG6uPW09iqKEc.d0G0UkqRlt4ciuqWckJh6zG.KbkbkaK',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(7,'Chief Executive Officer','ceo','ceo@local','081200000016','CEO','ImageAsset/1.jpg',NULL,'active',NULL,'$2y$12$lpEEmc1kQ59plJUkZWraJ.yxtDqeM.pVSx2NIjX3LYAoEIO9FsFz6',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(8,'Siti Chotijah','Dharmasraya','sitichotijah.mp3@gmail.com','0895401157050','Admin WH','signatures/DLbdeOeggE7SkaD4WLzu7XT1YWSeVgGSiSXFEUjf.jpg',3,'active',NULL,'$2y$12$dkFL14aBNKEJws3bgLbkcOb3AnwJUF2LxkaQFyRAaxSeTOQ3W4qum',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(9,'Ika Reskian Tomi','Solok','admnsolok@gmail.com','0895405405293','admin wh',NULL,8,'active',NULL,'$2y$12$LH0G7Qy3UnJzL2BX4flO0eUPr8Qm7WLsGHvzwnsjiKXoE216n21Jm',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(10,'Negal Neglas Sari','Tanah_Datar','negalneglasari@gmail.com','085278057526','Admin Wh',NULL,9,'active',NULL,'$2y$12$mVrYCXESO9//AaKblFQXv.Oz51CZTYmlASOE1rnVqfj5Hk9PgILDC',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(11,'Selvi Tryen Ningsih','Pasaman_Barat','selvitryenningsih.mp3@gmail.com','089505569181','Admin Wh',NULL,5,'active',NULL,'$2y$12$1l/XACDmlYNSD1n55dFHW.PvjZ/y/ahjipboRYp3JQvYkE14d8TWu',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(12,'Indah Agustin','Payakumbuh','indahagstn.28@gmail.com','089530847959','Admin Wh',NULL,6,'active',NULL,'$2y$12$R3CwF4aGcTctxCmLXIjSU.CDh/Rrt2XumYZpilNH7Xg409yFGeeJG',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(13,'Friska Diani Sakinah','Bukittinggi','friskadiani.mp3@gmail.com','085265635659','Admin Wh',NULL,1,'active',NULL,'$2y$12$prARBdDgJmMySRk7/Xod9OMrp8PQ04MFWjmLVWicgPNWvf5fYkF.a',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(14,'YORRI HASMI PERTIWI','Padang_A','yorrihasmi22@gmail.com','089672310041','Admin Wh','signatures/S3a6sMg8Z0AQnoojOLvxa0Dr9oiAxtaTHFHj9sRg.jpg',2,'active',NULL,'$2y$12$JuI6g9hV0JfxYxC/OW3r4eCbRQOjkNU.MlFtI8M/nBl8i86bDpc9e',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(15,'Rahmawati','Padang_B','rahmawati1005@gmail.com','0895320808288','Admin Wh',NULL,10,'active',NULL,'$2y$12$kWzPew5frPveA1LD1brD7uekyOtwiYDyYTzuttVIeR0nzsRzuYge.',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(16,'Sarmila','Pariaman','sarmila1.mp3@gmail.com','089529943710','Admin Wh',NULL,4,'active',NULL,'$2y$12$HQuG9O0wgum/RJzzn3Njh.OqTLt/pC4n.qWVdf3L9RMgBjr0CsPoa',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(17,'Painan','Painan','Painan@mandau.id','098765437','Admin Wh',NULL,11,'active',NULL,'$2y$12$fcZdASVm.pE1a1ovBr.Vn.qPS8Asbv5nAYhNpb67UBV25SkVJbCPO',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(18,'NIKO IRAWAN','Niko_Irawan','nikoirawan00@gmail.com','089521202061','Sales Area Sijunjung','signatures/SNzebi6a4UDuSe6h9I39OphZsL0gX6oe6EdvdCe2.jpg',3,'active',NULL,'$2y$12$9EhiA1mCftWKWLS78O0VQ.KPGGY6QQQSMLI5zP9WkR6ydfxKUPwFy',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(19,'DICKI FEBERIADI','Dicki_Feberiadi','dickifeberiadi@gmail.com','0895352993977','Sales Area Koto Baru','signatures/eAHxEPjVmxyobStjZ8tl1im7wlHegfQNyV0JpqDV.jpg',3,'active',NULL,'$2y$12$wrvqieotjLYl4BpjSNnzDeovGXgoMg1dYqe6C3wYbbR./MoVAgCWq',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(20,'Chenthia Budiharto','Chenthia_Budiharto','chenthia.budiharto@mandau.id',NULL,NULL,NULL,NULL,'active',NULL,'$2y$12$Wb9vimgsb.rjN7E.bu.rKehgvECJW.6wL86gfIgtXALx1Qgng9gHO',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(21,'Andi','Andi_sales','Andi_sales@gmail.com','085765328975','Sales','signatures/SNzebi6a4UDuSe6h9I39OphZsL0gX6oe6EdvdCe2.jpg',1,'active',NULL,'$2y$12$svYzH6G3mzow/WwIJxvGTeWTykd7jeT6FzZBWpPYQR9d1.EgsDKK2',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(22,'Ajif Agustian','Sales_Mandiangin','ajifagustian3@gmail.com','089521202052','Sales',NULL,1,'active',NULL,'$2y$12$qJMMgHZn2BxsTAMboLkBUupwK1qMm1dBaCYaiUjAWjKvcASCnB26K',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(23,'Hendra Marpaung','Sales_AmpekAngkek','fahimna11889@gmail.com','08974551415','Sales',NULL,1,'active',NULL,'$2y$12$W1jD5zlEVq/kJsB03VvjduYRCu1rD/1jJSULOTIOSipxC92SkwJW6',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(24,'Arif Febri Hendi','Sales_Tigobaleh','ariffebrihendi@gmail.com','089517965930','Sales',NULL,1,'active',NULL,'$2y$12$Jr2wfWax6dm60BrSH4RyPOAPtAp51mp50aOjQ9lVvX.GG61ESl.Qm',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(25,'Randi Gunawan','Sales_GuguakPanjang','randigun280294@gmail.com','089521202048','Sales',NULL,1,'active',NULL,'$2y$12$dNEAy3xcmlkTrByiUXcze.gxyfFItxiWaQDd23fseFoKo60JziRD2',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(26,'Ilham Pasundan','Sales_Banuhampu','Ilhampas07@gmail.com','089523006007','Sales',NULL,1,'active',NULL,'$2y$12$k3yc23NsCBYcKKpChSgnYea88ip9aLsZbq/wQ/JNdFkSAXwlbqP9m',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(27,'Rio Marlatif','Sales_SungaiPua','riomarlatif7@gmail.com','08974551416','Sales',NULL,1,'active',NULL,'$2y$12$f1hF9Ye7ElCLGjplHwk1BeFRZzblyfJkwI7SNXZd50suvQZZTyluS',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(28,'Rizky Prima Saputra','Sales_Bonjol','rizkyprima32@gmail.com','08982607512','Sales',NULL,1,'active',NULL,'$2y$12$qhb.WYAhIbasFUQPFrciqegix/xdPEMqklKbf/dzXWk5maQfN9muq',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(29,'Yoga Athalauza','Sales_TilatangKamang','yogaathalauza56@gmail.com','0895329873050','Sales',NULL,1,'active',NULL,'$2y$12$l9ctPIlXeC8RTh1ev0fETud2MRMR6uKbaFSGkgcCLOWKkqmUL.BwW',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(30,'Andri Refriandi','Sales_Baso','andrirefriandi219@gmail.com','089639058585','Sales',NULL,1,'active',NULL,'$2y$12$/c9JG2UWWrr0dEBuxYPnHePR5pC2Cz35oiELedLUxUCjYh/WASZF6',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(31,'Budi Setiawan','Sales_KamangMagek','bs136515@gmail.com','089639060001','Sales',NULL,1,'active',NULL,'$2y$12$QCyGLfvdZGA69alb1bHCdep.J9Q3WE0yS20X/KZuToNlzIoSCrc3G',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(32,'zidan','zidan_sales','zidan_sales@gmail.com',NULL,'Sales',NULL,1,'active',NULL,'$2y$12$pZX6fq4fbz3O0ZcMguyGs.a3q3rn0puCXnSd5lY2QvR11FyZ.IyfC',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(33,'DARMAN ATIFAH','SE_ANDALAS','darmanatifah@gmail.com','08982607516','SALES',NULL,2,'active',NULL,'$2y$12$3vb6odGcoF6kLE4B9EcoB.bOkM2ga8eINk/eSzjW7QjYzv95Xy.Xy',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(34,'ARLAN NUARI','SE_BATASKOTA','arlan.nuari1101@gmail.com','089517965939','Sales',NULL,10,'active',NULL,'$2y$12$f24tpzsA36lbwI5qkZ6yleT21GfvD7DP5VFmaZQns7URglQeR5/iK',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(35,'BULFI KAHFI','SE_BUNGUS','bulfikahfi166@gmail.com','089517965943','SALES',NULL,2,'active',NULL,'$2y$12$VdFqHWKVIWkNAjwZv3JFMuActSBAs2bENufuxCsyBXz3BwQJMSxq6',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(36,'TEDDY ERLANGGA','SE_MATAAIR','teddyerlangga87@gmail.com','08982607519','SALES',NULL,2,'active',NULL,'$2y$12$HKywGqatwJFQXdXXTHUWbetb2obdJDEGSPEzWBS.tTPtyOW.gWZO2',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(37,'RONAL AGUSTO','SE_PENGAMBIRAN','ronalagusto29@gmail.com','089521202068','SALES',NULL,2,'active',NULL,'$2y$12$B7y9GU1uE8t6m6Y4WYzBbO27YrQtTrwHVbhq0t5FznWmYWuj1wp.y',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(38,'YOGIE DWI SYAHPUTRA','SE_PONDOK','yogiedwisyahputra0060@gmail.com','089521202069','SALES',NULL,2,'active',NULL,'$2y$12$HxdkwyPR.K0nHsjXdSCHh.LNw4i.lvZsx1qYE4i29/HexwS4xd5Ui',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(39,'Agestya Heryan Pramana','Sales_PayakumbuhTimur','agestyaherian91@gmail.com','089521202054','Sales',NULL,6,'active',NULL,'$2y$12$AicV0tKmHfuqlmtwkN7PXOhypDqnkX0adzF7kNyiJlIkrC.w3z7k.',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(40,'Diko Aditya Jufri','Sales_PayakumbuhUtara','dikoaditya410@gmail.com','08974551412','Sales',NULL,6,'active',NULL,'$2y$12$aAX0ApTxuRN3rCQ39NesXe9BV6SlYvMokRGRsSC7Aj8TRTI0u2riq',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(41,'Muhammad Arsil','Sales_Lampasi','acilcilsilia@gmail.com','089521202049','Sales',NULL,6,'active',NULL,'$2y$12$N1vdLXnn1qD5hwbvsKHkr.LAuihwGV5oyB3DjNWqG9vPkc1I997dy',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(42,'Edoni Eka Putra','Sales_BukitBarisan','dhonivivo1992@gmail.com','089679397939','Sales',NULL,6,'active',NULL,'$2y$12$leCJVamS21dc9zJBmMCqUOh0WowK.XMiO0jlYRdWYt0dYTj81Qotu',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(43,'Fitra','Sales_Situjuah','fitrambs7588@gmail.com','089512126969','Sales',NULL,6,'active',NULL,'$2y$12$BrQG5U6lxpeNt2sJgXqQOuBBArnKtOGFId/pIosEiTY39p3a8RF3a',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(44,'Wesqarni','Sales_BatangTabik','wwesqarni@gmail.com','089521202046','Sales',NULL,6,'active',NULL,'$2y$12$sB/0T5WEc2cAU/bDJy8HP.g23sXJDr2sOSOTu76GoobwtzJV6ypI2',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(45,'Rayes Syah Eko Putra','Sales_GuguakMungka','rayes9687@gmail.com','089617965929','Sales',NULL,6,'active',NULL,'$2y$12$q5jqk5nSAw75lwpx9nyNAu/VHmzKMg7RFea4OLtvGW/DuyJi570UG',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(46,'Agus Purnomo','Sales_PayakumbuhBarat','agustpurnomo99@gmail.com','08974551413','Sales',NULL,6,'active',NULL,'$2y$12$CAfvqdNEbc4lP7EV98at/u4mRs/tDZ8TSfAllyu1yiefTnWWCbBUm',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(47,'Ardian Taurus Tio','Sales_Akabiluru','ardiantaurustio@gmail.com','08997481577','Sales',NULL,6,'active',NULL,'$2y$12$JmJLv7Y1MpPW06yxBXiQ..qz4uIQQI3CsmVi0AbpoipLdg0T9sG9S',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(48,'Ramadhatul Fauzi','Sales_Pangkalan','ezi.changcut@gmail.com','089521202058','Sales',NULL,6,'active',NULL,'$2y$12$z5jGwUj2m7/2Gaz8uQCHnOvGvAtP88nyOVZ5dHBra/JBRwvHSd97O',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(49,'DEVID AGUSTIN','SE_PASARBARU','devid.agustin22@gmail.com','089521202067','SALES',NULL,2,'active',NULL,'$2y$12$BXtNhkNuHQoyQk9uan.Qs.Hx4rTzVctape5DGzrUrRK6AnVmg9nHm',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(50,'RANDY DWITA PUTRA','SE_BANDARBUAT','randydp8@gmail.com','08982607518','SALES',NULL,2,'active',NULL,'$2y$12$5sCbkITyJlb3nRNIYiLVE.C6/x6ZtzjD/zOMhV4EVlLCfpekrAm9q',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(51,'RANDIKA CHANDRA PUTRA','SE_LUBUKLINTAH','randikachandra1996@gmail.com','089521202064','SALES',NULL,2,'active',NULL,'$2y$12$z95ay9A3oJeBeuHt2JRgLOJ9oeBDue.TU8gdWt95KVZjNDDSxLPcO',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(52,'Agung Adytia Pratama','Dse_Pasaman','agungadytiapratama08@gmail.com','08991280904','Sales',NULL,5,'active',NULL,'$2y$12$OA84Dgc9geF84SNv8aHOgOFTg72VZ.jbC5iYaYwwAm3NKBvqlDnle',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(53,'Pratama Yoga Ardi','Dse_Kinali','yogadesi62@gmail.com','089673497759','Sales',NULL,5,'active',NULL,'$2y$12$s4Vd1wO05nkEn.QhwPkYf.bw5F6ct0KAzI.dQGpysAiDS.kIyV1EO',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(54,'Bastian Ramadhan','Dse_LuhakNanDuo','Bstianrmdhn@gmail.com','0895324918883','Sales',NULL,5,'active',NULL,'$2y$12$GMO8by76Plq6KV6a5LISxeQ2.dXZr3miv9WEP/1GHh4ORVYl0gDrq',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(55,'Lukman Hakim','Dse_GunungTuleh','lh607434@gmail.com','085274782873','Sales',NULL,5,'active',NULL,'$2y$12$mEopZFK48UeORQpgBrbL0e4DBIWfZyz6EaeQ/YnrVqHHTV23Xxlqa',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(56,'GUNTUR CANTA','SE_LAPAI','caniagoguntur@gmail.com','08982607514','SALES',NULL,10,'active',NULL,'$2y$12$TK41yMzriSSWs5llzUC4wOvG6ng0P2vVmtiWUH.3TknoPBuqZJ5ra',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(57,'RIDWAN EFENDI','SE_DADOK','eridwan40@gmail.com','0895405394425','SALES',NULL,10,'active',NULL,'$2y$12$8FPlTqZAJzapfrlfMlRcNuyf2.EaKDtAQaDF/jlTGGiIXOOOahsfK',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(58,'EKO SAPUTRA','SE_KALAWI','saputraeko69019@gmail.com','089521202066','SALES',NULL,10,'active',NULL,'$2y$12$DOnMvc3XEyAbecR4bsEf3.RExUZj5srKgbbQXcHs16TdYYZSg51py',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(59,'KHEVIN PRANIZAL ILLAHI','SE_KALUMPANG','alexsaander229@gmail.com','089517965940','SALES',NULL,10,'active',NULL,'$2y$12$r.QurjNPOual39exBAYp..XzHpHCe4EHqnFWPBDsj.AnQMcsQzxnu',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(60,'BAYU SAPUTRA','SE_TARUKO','aziusaputra4@gmail.com','089521202071','SALES',NULL,10,'active',NULL,'$2y$12$LYNgWyk21GVB7oKvI5Ke.uUQFtMWf19NmPv06xgzHaTfELkcPRV82',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(61,'REVI RESTIADI','SE_LUMIN','seivercogah@gmail.com','089517965941','SALES',NULL,10,'active',NULL,'$2y$12$Y6OtwHFq5XCUhYls4ffzCuzVVLSpoOT3JRlb40F9emGn2LtxF38WK',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(62,'M. QHODRI','SE_AIRTAWAR','qhodri234@gmail.com','0895386884522','SALES',NULL,10,'active',NULL,'$2y$12$t4vTeBxSpa4f2YUmJ6D31eoDdGdPZoddme67/yKUqjSuDTipQgaQG',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(63,'KOKOH MUHARDI','SE_BELIMBING','kokohmuhardi@gmail.com','08982607513','SALES',NULL,10,'active',NULL,'$2y$12$5iQuzLO62yLlYp9KA.kbgu9fBHLmGjQ2T.4ytqHPU7WMOMjTRUcmi',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(64,'Difo Gunawan','Difo_Gunawan','difodege@gmail.com','08974551418','Sales Lima Kaum',NULL,9,'active',NULL,'$2y$12$TydmW/90R/BFwj9u9U00ROxA2dXHlp0DBEtRZBHYfjNv5Xvjj8XWO',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(65,'Ilham Wahyudi','Ilham_Wahyudi','ilhamwahyudi0474@gmail.com','08982370600','Sales Salimpaung',NULL,9,'active',NULL,'$2y$12$CrbHO8NfmH0RVgRpVdHQj.oXgRTDxGM/cIP6Nho2ZK4nYmjWKPZHO',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(66,'Arif Rahman','Arif_Rahman','rahmanarif0429@gmail.com','08982455554','Sales Sungai Tarab',NULL,9,'active',NULL,'$2y$12$Evjdkb.vcpZsPoQ8pDdIteQdWwm5IropWtHOqJ3quezHckp1CC8Vm',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(67,'Rindi Oktavianus','Rindi_Oktavianus','rindioktavianus7@gmail.com','089521202083','Sales Batipuh',NULL,9,'active',NULL,'$2y$12$62Df35a84sO5hv7anJwIYOpsvRz/DI3QG6SSkIjbAWX6.PHjacT/2',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(68,'Adri Nofrizal','Adri_Nofrizal','anofrizal99@gmail.com','08974551417','Sales Padang Panjang Barat',NULL,9,'active',NULL,'$2y$12$aQzMbJaw5nzWpzZj.d71GuVRJyBm3zd1QUNDyNEuGNy4jJSt/UGlC',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(69,'Jefri','Jefri_','jefri8142@gmail.com','0889521202085','Sales Padang Panjang Timur',NULL,9,'active',NULL,'$2y$12$mBSfw/PD7/H73dgJPQeAout5mPOK6xeptWSrvGK47wx5rrmTxhVNW',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(70,'RISKI GISFA RIANTO','Riski_Gisfa','riskigisfarianto@gmail.com','089519294518','Sales Area Pulau Punjung','signatures/TAph6SZyS1D0xPANk0fdXuP5lNH8CqV5r7njYiCB.jpg',3,'active',NULL,'$2y$12$4fJAOSlP5aZrBNdv9grDI.IiXXES9vycHw0XhxROOv3AskNPkrQqq',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(71,'Sales internal Dharmasraya','sales_dhamasraya','SalesinternalDharmasraya@gmail.com','081200000099','Sales',NULL,3,'active',NULL,'$2y$12$67JEJJpggQqLk9baeZx6QenkG6MvS7.JUCRTToa.AVgIEudMEGVMO',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(72,'Sales internal Solok','sales_solok','SalesinternalSolok@gmail.com','081200000098','Sales',NULL,8,'active',NULL,'$2y$12$BtJsX5iBJX5817JswHQTjeGQELCsemE57VSxPGde2JlU2x1qP8Gbe',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(73,'Sales internal Tanah Datar','sales_tanahdatar','SalesinternalTanahDatar@gmail.com','081200000097','Sales',NULL,9,'active',NULL,'$2y$12$pRF4fOkFNpZNmu0x8tLRQuZhBPzty66OHoUC/mIW5gywf7D3/C1S2',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(74,'Sales internal Pasaman Barat','sales_pasamanbarat','SalesinternalPasamanBarat@gmail.com','081200000096','Sales',NULL,5,'active',NULL,'$2y$12$d28YJu.ZObNHzwZGlcO/mOFId4m3qgQVMt7wmNhlCOqK7pnpo1wdO',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(75,'Sales internal Payakumbuh','sales_payakumbuh','SalesinternalPayakumbuh@gmail.com','081200000095','Sales',NULL,6,'active',NULL,'$2y$12$y74ts/NRL9l6DqtfoBAVOuGJqnI/.woK0A1O3QN7pxHWjchB9I6jm',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(76,'Sales internal Bukittinggi','sales_bukittinggi','SalesinternalBukittinggi@gmail.com','081200000094','Sales',NULL,1,'active',NULL,'$2y$12$2sl3sEPoo84lFAjQ2C5qCO.IsD7.8KM1cDqphEQrrwoO2EneWXu5.',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(77,'Sales internal Padang','sales_padang12','SalesinternalPadang@gmail.com','081200000093','Sales',NULL,2,'active',NULL,'$2y$12$.FVOWssLtKSWxjvqjxiMseKe3dGeYwgV5HWxyVWEOwoVTuuZHjDki',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(78,'Sales internal Pariaman','sales_pariaman','SalesinternalParlaman@gmail.com','081200000092','Sales',NULL,4,'active',NULL,'$2y$12$JmoAXjHdOj5NXPb1dFEOBuqcqBDHauSQ6aT4xznr7YkzbSdJZ5aGy',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18'),(79,'Sales internal Painan','sales_painan','SalesinternalPainan@gmail.com','081200000091','Sales',NULL,11,'active',NULL,'$2y$12$YpJJOY4Bf3FoatDrdDrWy.z3987nwT78OVgS5N4Zc0GiByLp0aGCG',NULL,'2026-06-22 02:56:18','2026-06-22 02:56:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-22 14:36:07
