/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_roles` json DEFAULT NULL,
  `recipient_user_ids` json DEFAULT NULL,
  `call_to_action_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `call_to_action_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_sender_id_foreign` (`sender_id`),
  CONSTRAINT `announcements_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `authentication_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authentication_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `authenticatable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authenticatable_id` bigint unsigned NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_at` timestamp NULL DEFAULT NULL,
  `login_successful` tinyint(1) NOT NULL DEFAULT '0',
  `logout_at` timestamp NULL DEFAULT NULL,
  `cleared_by_user` tinyint(1) NOT NULL DEFAULT '0',
  `location` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `authentication_log_authenticatable_type_authenticatable_id_index` (`authenticatable_type`,`authenticatable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `schedule_template_id` bigint unsigned DEFAULT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `concierge_id` bigint unsigned NOT NULL,
  `partner_concierge_id` bigint unsigned DEFAULT NULL,
  `partner_restaurant_id` bigint unsigned DEFAULT NULL,
  `guest_first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_at` datetime NOT NULL DEFAULT '2024-07-31 21:08:26',
  `guest_count` int NOT NULL,
  `total_fee` int NOT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'confirmed',
  `is_prime` tinyint(1) NOT NULL DEFAULT '0',
  `no_show` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stripe_charge_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_charge` json DEFAULT NULL,
  `restaurant_earnings` int NOT NULL DEFAULT '0',
  `concierge_earnings` int NOT NULL DEFAULT '0',
  `charity_earnings` int NOT NULL DEFAULT '0',
  `platform_earnings` int NOT NULL DEFAULT '0',
  `partner_concierge_fee` int unsigned NOT NULL DEFAULT '0',
  `partner_restaurant_fee` int unsigned NOT NULL DEFAULT '0',
  `clicked_at` datetime DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `concierge_referral_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `restaurant_confirmed_at` timestamp NULL DEFAULT NULL,
  `resent_restaurant_confirmation_at` timestamp NULL DEFAULT NULL,
  `tax_amount_in_cents` int DEFAULT NULL,
  `tax` double DEFAULT NULL,
  `total_with_tax_in_cents` int DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `bookings_concierg_id_foreign` (`concierge_id`),
  KEY `bookings_partner_concierge_id_foreign` (`partner_concierge_id`),
  KEY `bookings_partner_restaurant_id_foreign` (`partner_restaurant_id`),
  KEY `bookings_schedule_template_id_foreign` (`schedule_template_id`),
  CONSTRAINT `bookings_concierge_id_foreign` FOREIGN KEY (`concierge_id`) REFERENCES `concierges` (`id`),
  CONSTRAINT `bookings_partner_concierge_id_foreign` FOREIGN KEY (`partner_concierge_id`) REFERENCES `partners` (`id`),
  CONSTRAINT `bookings_partner_restaurant_id_foreign` FOREIGN KEY (`partner_restaurant_id`) REFERENCES `partners` (`id`),
  CONSTRAINT `bookings_schedule_template_id_foreign` FOREIGN KEY (`schedule_template_id`) REFERENCES `schedule_templates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `breezy_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `breezy_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `authenticatable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `authenticatable_id` bigint unsigned NOT NULL,
  `panel_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `concierges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `concierges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `hotel_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hotel_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `concierge_user_id_foreign` (`user_id`),
  KEY `concierge_hotel_name_index` (`hotel_name`),
  CONSTRAINT `concierge_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `devices_key_unique` (`key`),
  KEY `devices_user_id_index` (`user_id`),
  CONSTRAINT `devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `earning_errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `earning_errors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `error_message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `restaurant_earnings` int NOT NULL,
  `concierge_earnings` int NOT NULL,
  `concierge_referral_level_1_earnings` int NOT NULL,
  `concierge_referral_level_2_earnings` int NOT NULL,
  `restaurant_partner_earnings` int NOT NULL,
  `concierge_partner_earnings` int NOT NULL,
  `platform_earnings` int NOT NULL,
  `total_local` int NOT NULL,
  `total_fee` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `earning_errors_booking_id_foreign` (`booking_id`),
  CONSTRAINT `earning_errors_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `earnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `earnings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned NOT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int NOT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `percentage` int NOT NULL,
  `percentage_of` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `referral_earnings_user_id_foreign` (`user_id`),
  KEY `referral_earnings_booking_id_foreign` (`booking_id`),
  CONSTRAINT `referral_earnings_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referral_earnings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `completed_at` timestamp NULL DEFAULT NULL,
  `file_disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exporter` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processed_rows` int unsigned NOT NULL DEFAULT '0',
  `total_rows` int unsigned NOT NULL,
  `successful_rows` int unsigned NOT NULL DEFAULT '0',
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exports_user_id_foreign` (`user_id`),
  CONSTRAINT `exports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception_message` text COLLATE utf8mb4_unicode_ci,
  `exception_trace` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_user_id_foreign` (`user_id`),
  CONSTRAINT `feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `announcement_id` bigint unsigned NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_user_id_foreign` (`user_id`),
  KEY `messages_announcement_id_foreign` (`announcement_id`),
  CONSTRAINT `messages_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `percentage` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partners_user_id_foreign` (`user_id`),
  CONSTRAINT `partners_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `payment_id` bigint unsigned NOT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_items_user_id_foreign` (`user_id`),
  KEY `payment_items_payment_id_foreign` (`payment_id`),
  CONSTRAINT `payment_items_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  CONSTRAINT `payment_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int NOT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_aggregates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bucket` int unsigned NOT NULL,
  `period` mediumint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `aggregate` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(20,2) NOT NULL,
  `count` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_aggregates_bucket_period_type_aggregate_key_hash_unique` (`bucket`,`period`,`type`,`aggregate`,`key_hash`),
  KEY `pulse_aggregates_period_bucket_index` (`period`,`bucket`),
  KEY `pulse_aggregates_type_index` (`type`),
  KEY `pulse_aggregates_period_type_aggregate_bucket_index` (`period`,`type`,`aggregate`,`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pulse_entries_timestamp_index` (`timestamp`),
  KEY `pulse_entries_type_index` (`type`),
  KEY `pulse_entries_key_hash_index` (`key_hash`),
  KEY `pulse_entries_timestamp_type_key_hash_value_index` (`timestamp`,`type`,`key_hash`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_values_type_key_hash_unique` (`type`,`key_hash`),
  KEY `pulse_values_timestamp_index` (`timestamp`),
  KEY `pulse_values_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `referrals` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referrer_id` bigint unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `secured_at` datetime DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'concierge',
  `referrer_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'concierge',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `concierge_referrals_user_id_foreign` (`user_id`),
  KEY `concierge_referrals_referrer_id_foreign` (`referrer_id`),
  CONSTRAINT `concierge_referrals_referrer_id_foreign` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `concierge_referrals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `restaurant_time_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `restaurant_time_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `schedule_template_id` bigint unsigned NOT NULL,
  `booking_date` date NOT NULL,
  `prime_time` tinyint(1) NOT NULL,
  `prime_time_fee` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `restaurant_time_slots_schedule_template_id_foreign` (`schedule_template_id`),
  CONSTRAINT `restaurant_time_slots_schedule_template_id_foreign` FOREIGN KEY (`schedule_template_id`) REFERENCES `schedule_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `restaurants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `restaurants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `restaurant_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payout_restaurant` int NOT NULL DEFAULT '60',
  `primary_contact_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_fee` int NOT NULL DEFAULT '20000',
  `increment_fee` int NOT NULL DEFAULT '50',
  `non_prime_fee_per_head` int NOT NULL DEFAULT '10',
  `non_prime_type` enum('free','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `open_days` json DEFAULT NULL,
  `contacts` json DEFAULT NULL,
  `is_suspended` tinyint(1) NOT NULL DEFAULT '0',
  `non_prime_time` json DEFAULT NULL,
  `business_hours` json DEFAULT NULL,
  `party_sizes` json DEFAULT NULL,
  `minimum_spend` int DEFAULT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'miami',
  `restaurant_logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  PRIMARY KEY (`id`),
  UNIQUE KEY `restaurants_slug_unique` (`slug`),
  KEY `restaurant_user_id_foreign` (`user_id`),
  CONSTRAINT `restaurant_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `restaurants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedule_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `restaurant_id` bigint unsigned NOT NULL,
  `day_of_week` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL,
  `available_tables` int NOT NULL,
  `prime_time` tinyint(1) NOT NULL DEFAULT '0',
  `prime_time_fee` int NOT NULL DEFAULT '0',
  `party_size` int NOT NULL DEFAULT '2',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedule_templates_restaurant_id_foreign` (`restaurant_id`),
  CONSTRAINT `schedule_templates_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedule_with_bookings`;
/*!50001 DROP VIEW IF EXISTS `schedule_with_bookings`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `schedule_with_bookings` AS SELECT 
 1 AS `id`,
 1 AS `schedule_template_id`,
 1 AS `restaurant_id`,
 1 AS `day_of_week`,
 1 AS `start_time`,
 1 AS `end_time`,
 1 AS `is_available`,
 1 AS `available_tables`,
 1 AS `prime_time`,
 1 AS `prime_time_fee`,
 1 AS `party_size`,
 1 AS `booking_date`,
 1 AS `booking_at`,
 1 AS `schedule_start`,
 1 AS `schedule_end`,
 1 AS `remaining_tables`,
 1 AS `effective_fee`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `short_url_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `short_url_visits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `short_url_id` bigint unsigned NOT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operating_system` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operating_system_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visited_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `short_url_visits_short_url_id_foreign` (`short_url_id`),
  CONSTRAINT `short_url_visits_short_url_id_foreign` FOREIGN KEY (`short_url_id`) REFERENCES `short_urls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `short_urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `short_urls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `destination_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_short_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `single_use` tinyint(1) NOT NULL,
  `forward_query_params` tinyint(1) NOT NULL DEFAULT '0',
  `track_visits` tinyint(1) NOT NULL,
  `redirect_status_code` int NOT NULL DEFAULT '301',
  `track_ip_address` tinyint(1) NOT NULL DEFAULT '0',
  `track_operating_system` tinyint(1) NOT NULL DEFAULT '0',
  `track_operating_system_version` tinyint(1) NOT NULL DEFAULT '0',
  `track_browser` tinyint(1) NOT NULL DEFAULT '0',
  `track_browser_version` tinyint(1) NOT NULL DEFAULT '0',
  `track_referer_url` tinyint(1) NOT NULL DEFAULT '0',
  `track_device_type` tinyint(1) NOT NULL DEFAULT '0',
  `activated_at` timestamp NULL DEFAULT '2024-08-01 01:08:26',
  `deactivated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `short_urls_url_key_unique` (`url_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `response` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `special_pricing_restaurants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `special_pricing_restaurants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `restaurant_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `fee` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `special_pricing_restaurants_restaurant_id_foreign` (`restaurant_id`),
  CONSTRAINT `special_pricing_restaurants_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `special_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `special_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `restaurant_id` bigint unsigned NOT NULL,
  `concierge_id` bigint unsigned NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `party_size` int NOT NULL,
  `special_request` text COLLATE utf8mb4_unicode_ci,
  `customer_first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commission_requested_percentage` int NOT NULL DEFAULT '10',
  `minimum_spend` int NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `schedule_template_id` bigint unsigned DEFAULT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `restaurant_message` text COLLATE utf8mb4_unicode_ci,
  `conversations` json DEFAULT NULL,
  `meta` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `special_requests_uuid_unique` (`uuid`),
  KEY `special_requests_restaurant_id_foreign` (`restaurant_id`),
  KEY `special_requests_concierge_id_foreign` (`concierge_id`),
  KEY `special_requests_schedule_template_id_foreign` (`schedule_template_id`),
  CONSTRAINT `special_requests_concierge_id_foreign` FOREIGN KEY (`concierge_id`) REFERENCES `concierges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `special_requests_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `special_requests_schedule_template_id_foreign` FOREIGN KEY (`schedule_template_id`) REFERENCES `schedule_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_codes_user_id_index` (`user_id`),
  CONSTRAINT `user_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_team_id` bigint unsigned DEFAULT NULL,
  `profile_photo_path` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `secured_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stripe_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pm_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pm_last_four` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payout` json DEFAULT NULL,
  `charity_percentage` int NOT NULL DEFAULT '5',
  `partner_referral_id` bigint unsigned DEFAULT NULL,
  `concierge_referral_id` bigint unsigned DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/New_York',
  `address_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_stripe_id_index` (`stripe_id`),
  KEY `users_partner_referral_id_foreign` (`partner_referral_id`),
  KEY `users_concierge_referral_id_foreign` (`concierge_referral_id`),
  CONSTRAINT `users_concierge_referral_id_foreign` FOREIGN KEY (`concierge_referral_id`) REFERENCES `concierges` (`id`),
  CONSTRAINT `users_partner_referral_id_foreign` FOREIGN KEY (`partner_referral_id`) REFERENCES `partners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50001 DROP VIEW IF EXISTS `schedule_with_bookings`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `schedule_with_bookings` AS with recursive `date_range` as (select (curdate() - interval 1 day) AS `date` union all select (`date_range`.`date` + interval 1 day) AS `DATE_ADD(date, INTERVAL 1 DAY)` from `date_range` where (`date_range`.`date` < (curdate() + interval 30 day))) select `st`.`id` AS `id`,`st`.`id` AS `schedule_template_id`,`st`.`restaurant_id` AS `restaurant_id`,`st`.`day_of_week` AS `day_of_week`,`st`.`start_time` AS `start_time`,`st`.`end_time` AS `end_time`,`st`.`is_available` AS `is_available`,`st`.`available_tables` AS `available_tables`,coalesce(`rts`.`prime_time`,`st`.`prime_time`) AS `prime_time`,`st`.`prime_time_fee` AS `prime_time_fee`,`st`.`party_size` AS `party_size`,`dr`.`date` AS `booking_date`,date_format(cast(concat(date_format(`dr`.`date`,'%Y-%m-%d'),' ',time_format(`st`.`start_time`,'%H:%i:%s')) as datetime(6)),'%Y-%m-%d %H:%i:%s') AS `booking_at`,date_format(cast(concat(date_format(`dr`.`date`,'%Y-%m-%d'),' ',time_format(`st`.`start_time`,'%H:%i:%s')) as datetime(6)),'%Y-%m-%d %H:%i:%s') AS `schedule_start`,date_format(cast(concat(date_format(`dr`.`date`,'%Y-%m-%d'),' ',time_format(`st`.`end_time`,'%H:%i:%s')) as datetime(6)),'%Y-%m-%d %H:%i:%s') AS `schedule_end`,(`st`.`available_tables` - ifnull(`b`.`booked_count`,0)) AS `remaining_tables`,coalesce(`sp`.`fee`,`r`.`booking_fee`) AS `effective_fee` from (((((`date_range` `dr` join `schedule_templates` `st` on((dayname(`dr`.`date`) = `st`.`day_of_week`))) left join (select `bookings`.`schedule_template_id` AS `schedule_template_id`,count(0) AS `booked_count` from `bookings` where (`bookings`.`status` = 'confirmed') group by `bookings`.`schedule_template_id`) `b` on((`st`.`id` = `b`.`schedule_template_id`))) left join `special_pricing_restaurants` `sp` on(((`sp`.`restaurant_id` = `st`.`restaurant_id`) and (`sp`.`date` = `dr`.`date`)))) left join `restaurants` `r` on((`r`.`id` = `st`.`restaurant_id`))) left join `restaurant_time_slots` `rts` on(((`rts`.`schedule_template_id` = `st`.`id`) and (`rts`.`booking_date` = `dr`.`date`)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2014_10_12_100000_create_password_reset_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2014_10_12_200000_add_two_factor_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2019_05_03_000001_create_customer_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2019_05_03_000002_create_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2019_05_03_000003_create_subscription_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2020_05_21_100000_create_teams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2020_05_21_200000_create_team_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2020_05_21_300000_create_team_invitations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2024_01_10_140416_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2024_01_10_142318_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2024_01_10_144025_create_concierge_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2024_01_10_144047_create_restaurant_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2024_01_10_144057_create_customer_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2024_01_10_144116_create_reservations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2024_01_10_144158_create_bookings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2024_01_10_144217_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2024_01_10_144458_add_phone_to_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2024_01_11_001326_create_breezy_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2024_01_26_164141_remove_columns_from_restaurant_profiles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2024_02_06_132604_mod_restaurants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2024_02_09_003329_create_laragenie_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2024_02_09_010017_remove_and_add_columns_to_reservations',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2024_02_11_201051_rename_reservations_table_to_time_slots',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2024_02_11_202717_remove_payout_percentage_from_concierge_profiles',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2024_02_11_203330_modify_restaraunt_profiles',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2019_12_14_000001_create_personal_access_tokens_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2024_02_11_224354_add_fields_to_concierge_profiles',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2024_02_12_114436_rename_tables',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2024_02_12_121151_add_contact_names_to_restaurants',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2024_02_12_150712_remove_guest_id_from_bookings',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2024_02_12_152651_rename_restaurant_profile_id_to_restaurant_id_bookings',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2024_02_12_182005_add_is_available_to_time_slots',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2024_02_13_181011_add_schedule_to_restaurants',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2024_02_13_181624_remove_time_slot_id_from_bookings',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2024_02_13_191405_add_date_and_time_to_bookings',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2024_02_13_215241_create_schedules_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2019_12_22_015115_create_short_urls_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2019_12_22_015214_create_short_url_visits_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2020_02_11_224848_update_short_url_table_for_version_two_zero_zero',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2020_02_12_008432_update_short_url_visits_table_for_version_two_zero_zero',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2020_04_10_224546_update_short_url_table_for_version_three_zero_zero',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2020_04_20_009283_update_short_url_table_add_option_to_forward_query_params',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2023_06_07_000001_create_pulse_tables',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2024_02_15_215905_add_booking_fee_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2024_02_15_234635_add_open_days_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2024_02_16_192243_add_first_name_and_last_name_to_users',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2024_02_16_193555_add_payouts_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2024_02_16_223251_add_booking_date_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2024_02_17_002227_add_times_to_booking',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2024_02_21_224001_add_fields_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2024_02_23_101108_add_stripe_payment_id_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2024_03_01_183514_add_payout_to_users',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2024_03_06_190233_add_charity_percentage_to_users_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2024_03_06_190900_add_earnings_to_bookings_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2024_03_06_212349_remove_payouts_from_bookings_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2024_03_06_212406_remove_payouts_from_restaurants_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2024_03_06_221618_create_partners_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2024_03_06_221756_add_partner_referal_id_to_users',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2024_03_07_214618_add_partner_fee_to_bookings_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2024_03_07_215728_add_partner_id_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2024_03_10_190638_change_percentage_column_in_partners_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2024_03_20_193923_add_fields_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2024_03_21_181419_add_contacts_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2024_03_21_201656_add_restaurant_confirmed_at_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2024_03_21_214456_add_timezone_to_users',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2024_03_22_102512_add_resent_confirmation_to_restaurant_at_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2024_03_22_115320_add_secured_at_to_users',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2024_03_22_181756_add_tax_and_tax_amount_in_cents_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2024_03_23_155402_add_invoice_url_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2024_03_23_163139_remove_invoice_url_from_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2024_03_23_165123_set_auto_increment_for_id_to_be_332233_for_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2024_03_24_070629_create_special_pricing_restaurants_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2024_03_24_142016_suspend_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2024_03_24_201255_add_non_prime_time_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2024_03_27_152308_add_address_to_users',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2024_03_28_225514_add_concierge_referral_id_to_users',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2024_03_29_024650_create_cache_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2024_03_29_024734_create_jobs_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2024_03_29_205756_create_concierge_referrals_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2024_03_30_131242_add_user_id_to_concierge_referrals',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2024_03_30_172516_create_referral_earnings_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2024_03_30_195444_rename_referral_earnings_to_earnings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2024_03_30_213746_rename_referral_type_to_type_in_earnings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2024_03_31_013813_add_confirmed_at_to_earnings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2024_03_31_132922_drop_unused_tables',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2024_04_01_183948_add_business_hours_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2024_04_02_132855_add_day_of_week_to_schedules',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2024_04_03_105225_create_sms_response_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2024_04_08_224838_rename_concierge_id_to_referrer_id',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2024_04_09_162612_rename_table_concierge_referrals_to_referrals',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2024_04_12_115041_create_earning_errors_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2024_04_13_231643_add_prime_time_to_schedules',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2024_04_18_113532_add_party_size_and_booking_date_to_schedules',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2024_04_18_113729_add_party_sizes_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2024_04_19_020315_create_announcements_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2024_04_19_044515_create_messages_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2024_04_19_131722_create_schedule_templates_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2024_04_24_033002_add_schedule_template_id_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2024_04_24_211805_create_view_schedules_with_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2024_04_25_202712_add_published_at_to_annoucements',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2024_04_25_230144_add_first_name_and_last_name_to_invitations',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2024_04_25_235016_add_minimum_spent_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2024_04_25_235429_add_notes_to_booking',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2024_04_26_014211_update_schedules_view',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2024_04_30_134734_add_region_and_logo_path_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2024_05_01_020815_add_foreign_keys_to_restaurants_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2024_05_01_041532_drop_schedules_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2024_05_01_174359_show_non_available_schedules_in_schedules_with_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2024_05_02_235406_create_authentication_log_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2024_05_03_025331_create_feedback_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2024_05_03_172843_add_notified_at_column_to_referrals_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2024_05_07_041616_add_currency_to_earnings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2024_05_07_170929_modify_foreign_key_on_messages_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2024_05_07_193227_add_region_to_users',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2024_05_07_201419_add_region_to_announcements',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2024_05_08_113609_remove_tables_for_verbs',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2024_05_09_143808_create_devices_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2024_05_09_211039_create_user_codes_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2024_05_10_160314_add_increment_fee_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2024_05_10_161902_add_non_prime_fee_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2024_05_10_175404_add_no_show_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2024_05_13_141329_create_payments_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2024_05_13_141742_add_payment_id_to_earnings_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2024_05_13_200725_add_is_prime_to_bookings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2024_05_14_145601_create_job_batches_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2024_05_14_145608_create_notifications_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2024_05_14_145750_create_exports_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2024_05_17_123827_add_slug_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2024_05_22_123425_create_payment_items_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2024_05_22_160636_create_restaurant_time_slots_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2024_05_22_184916_update_schedule_with_bookings_view_to_incorporate_restaurant_time_slots',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2024_05_22_191952_update_schedule_with_bookings_view_to_incorporate_restaurant_time_slots_2',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2024_05_26_114829_create_special_requests_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2024_05_27_162210_add_fields_to_special_requests',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2024_05_29_144340_make_customer_email_nullable_on_special_requests',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2024_05_29_144923_add_preferences_column_to_users_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2024_06_05_131041_add_status_to_restaurants',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2024_06_05_205417_update_schedule_with_bookings_view_to_show_all_schedules',9);
