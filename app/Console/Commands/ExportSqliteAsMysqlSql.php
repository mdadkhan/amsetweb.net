<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Produces a single .sql file (MySQL-compatible CREATE TABLE + INSERT statements) from the
 * local SQLite database, so it can be imported manually via phpMyAdmin/cPanel without ever
 * needing a reachable MySQL server on this machine.
 */
class ExportSqliteAsMysqlSql extends Command
{
    protected $signature = 'amset:export-mysql-sql {--path=storage/app/private/hostgator-import.sql}';

    protected $description = 'Export the SQLite database as a MySQL-compatible .sql dump (schema + data)';

    /** @var array<int, string> Dependency order: parents before children. */
    private const TABLES = [
        'migrations', 'users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs',
        'pages', 'posts', 'contact_submissions',
        'initiatives', 'people', 'timeline_events', 'conferences', 'galleries', 'gallery_items',
        'membership_plans', 'members', 'events', 'event_tickets', 'event_registrations',
        'donation_campaigns', 'donations', 'payments',
    ];

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $sql = "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n".$this->schemaSql();

        foreach (self::TABLES as $table) {
            if (! DB::connection('sqlite')->getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $sql .= $this->tableDataSql($table);
        }

        $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

        file_put_contents($path, $sql);

        $this->components->info("Wrote MySQL dump to {$path}");

        return self::SUCCESS;
    }

    private function tableDataSql(string $table): string
    {
        $rows = DB::connection('sqlite')->table($table)->get();

        if ($rows->isEmpty()) {
            return '';
        }

        $columns = array_keys((array) $rows->first());
        $columnList = implode('`, `', $columns);
        $sql = "\n-- Data for table `{$table}`\n";

        foreach ($rows->chunk(200) as $chunk) {
            $values = $chunk->map(function ($row) use ($columns) {
                $row = (array) $row;
                $escaped = array_map([$this, 'quote'], array_map(fn ($column) => $row[$column], $columns));

                return '('.implode(', ', $escaped).')';
            })->implode(",\n");

            $sql .= "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n{$values};\n";
        }

        return $sql;
    }

    private function quote(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value)."'";
    }

    private function schemaSql(): string
    {
        return <<<'SQL'
        CREATE TABLE IF NOT EXISTS `migrations` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `migration` varchar(255) NOT NULL,
          `batch` int NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `users` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `email` varchar(255) NOT NULL,
          `email_verified_at` timestamp NULL DEFAULT NULL,
          `password` varchar(255) NOT NULL,
          `remember_token` varchar(100) DEFAULT NULL,
          `is_admin` tinyint(1) NOT NULL DEFAULT '0',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `users_email_unique` (`email`),
          KEY `users_is_admin_index` (`is_admin`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
          `email` varchar(255) NOT NULL,
          `token` varchar(255) NOT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `sessions` (
          `id` varchar(255) NOT NULL,
          `user_id` bigint unsigned DEFAULT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `user_agent` text,
          `payload` longtext NOT NULL,
          `last_activity` int NOT NULL,
          PRIMARY KEY (`id`),
          KEY `sessions_user_id_index` (`user_id`),
          KEY `sessions_last_activity_index` (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `cache` (
          `key` varchar(255) NOT NULL,
          `value` mediumtext NOT NULL,
          `expiration` bigint NOT NULL,
          PRIMARY KEY (`key`),
          KEY `cache_expiration_index` (`expiration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `cache_locks` (
          `key` varchar(255) NOT NULL,
          `owner` varchar(255) NOT NULL,
          `expiration` bigint NOT NULL,
          PRIMARY KEY (`key`),
          KEY `cache_locks_expiration_index` (`expiration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `jobs` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `queue` varchar(255) NOT NULL,
          `payload` longtext NOT NULL,
          `attempts` smallint unsigned NOT NULL,
          `reserved_at` int unsigned DEFAULT NULL,
          `available_at` int unsigned NOT NULL,
          `created_at` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          KEY `jobs_queue_index` (`queue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `job_batches` (
          `id` varchar(255) NOT NULL,
          `name` varchar(255) NOT NULL,
          `total_jobs` int NOT NULL,
          `pending_jobs` int NOT NULL,
          `failed_jobs` int NOT NULL,
          `failed_job_ids` longtext NOT NULL,
          `options` mediumtext,
          `cancelled_at` int DEFAULT NULL,
          `created_at` int NOT NULL,
          `finished_at` int DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `failed_jobs` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `uuid` varchar(255) NOT NULL,
          `connection` varchar(255) NOT NULL,
          `queue` varchar(255) NOT NULL,
          `payload` longtext NOT NULL,
          `exception` longtext NOT NULL,
          `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
          KEY `failed_jobs_connection_queue_failed_at_index` (`connection`, `queue`, `failed_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `pages` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `summary` text,
          `body` longtext,
          `featured_image` varchar(255) DEFAULT NULL,
          `meta` json DEFAULT NULL,
          `is_published` tinyint(1) NOT NULL DEFAULT '0',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `pages_slug_unique` (`slug`),
          KEY `pages_is_published_index` (`is_published`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `posts` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `excerpt` text,
          `body` longtext,
          `featured_image` varchar(255) DEFAULT NULL,
          `category` varchar(255) NOT NULL DEFAULT 'news',
          `published_at` timestamp NULL DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `posts_slug_unique` (`slug`),
          KEY `posts_category_index` (`category`),
          KEY `posts_published_at_index` (`published_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `contact_submissions` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `inquiry_type` varchar(255) NOT NULL,
          `name` varchar(255) NOT NULL,
          `email` varchar(255) NOT NULL,
          `phone` varchar(255) DEFAULT NULL,
          `organization` varchar(255) DEFAULT NULL,
          `message` text NOT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `contact_submissions_inquiry_type_index` (`inquiry_type`),
          KEY `contact_submissions_email_index` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `initiatives` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `eyebrow` varchar(255) DEFAULT NULL,
          `description` text,
          `link` varchar(255) DEFAULT NULL,
          `icon` varchar(255) DEFAULT NULL,
          `sort_order` int unsigned NOT NULL DEFAULT '0',
          `is_published` tinyint(1) NOT NULL DEFAULT '1',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `initiatives_slug_unique` (`slug`),
          KEY `initiatives_is_published_index` (`is_published`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `people` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `role` varchar(255) DEFAULT NULL,
          `institution` varchar(255) DEFAULT NULL,
          `expertise` varchar(255) DEFAULT NULL,
          `biography` text,
          `photo` varchar(255) DEFAULT NULL,
          `type` varchar(255) NOT NULL DEFAULT 'scientist',
          `sort_order` int unsigned NOT NULL DEFAULT '0',
          `is_published` tinyint(1) NOT NULL DEFAULT '1',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `people_slug_unique` (`slug`),
          KEY `people_type_index` (`type`),
          KEY `people_is_published_index` (`is_published`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `timeline_events` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `year` varchar(20) NOT NULL,
          `title` varchar(255) NOT NULL,
          `description` text NOT NULL,
          `sort_order` int unsigned NOT NULL DEFAULT '0',
          `is_published` tinyint(1) NOT NULL DEFAULT '1',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `timeline_events_is_published_index` (`is_published`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `conferences` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `starts_at` date DEFAULT NULL,
          `ends_at` date DEFAULT NULL,
          `location` varchar(255) DEFAULT NULL,
          `summary` text,
          `body` longtext,
          `program_url` varchar(255) DEFAULT NULL,
          `is_published` tinyint(1) NOT NULL DEFAULT '1',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `conferences_slug_unique` (`slug`),
          KEY `conferences_starts_at_index` (`starts_at`),
          KEY `conferences_is_published_index` (`is_published`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `galleries` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `description` text,
          `event_date` date DEFAULT NULL,
          `is_published` tinyint(1) NOT NULL DEFAULT '1',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `galleries_slug_unique` (`slug`),
          KEY `galleries_event_date_index` (`event_date`),
          KEY `galleries_is_published_index` (`is_published`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `gallery_items` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `gallery_id` bigint unsigned NOT NULL,
          `image` varchar(255) NOT NULL,
          `caption` varchar(255) DEFAULT NULL,
          `sort_order` int unsigned NOT NULL DEFAULT '0',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `gallery_items_gallery_id_foreign` (`gallery_id`),
          CONSTRAINT `gallery_items_gallery_id_foreign` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `membership_plans` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `description` text,
          `price` decimal(10,2) NOT NULL DEFAULT '0.00',
          `billing_interval` enum('monthly','yearly','lifetime') NOT NULL DEFAULT 'yearly',
          `is_active` tinyint(1) NOT NULL DEFAULT '1',
          `sort_order` int unsigned NOT NULL DEFAULT '0',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `membership_plans_slug_unique` (`slug`),
          KEY `membership_plans_is_active_index` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `members` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `user_id` bigint unsigned DEFAULT NULL,
          `membership_plan_id` bigint unsigned DEFAULT NULL,
          `membership_number` varchar(255) NOT NULL,
          `first_name` varchar(255) NOT NULL,
          `last_name` varchar(255) NOT NULL,
          `email` varchar(255) NOT NULL,
          `phone` varchar(255) DEFAULT NULL,
          `organization` varchar(255) DEFAULT NULL,
          `address_line1` varchar(255) DEFAULT NULL,
          `address_line2` varchar(255) DEFAULT NULL,
          `city` varchar(255) DEFAULT NULL,
          `state` varchar(255) DEFAULT NULL,
          `postal_code` varchar(255) DEFAULT NULL,
          `country` varchar(255) DEFAULT NULL,
          `status` enum('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending',
          `joined_at` date DEFAULT NULL,
          `expires_at` date DEFAULT NULL,
          `last_renewed_at` date DEFAULT NULL,
          `renewal_reminder_sent` tinyint(1) NOT NULL DEFAULT '0',
          `notes` text,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `members_membership_number_unique` (`membership_number`),
          UNIQUE KEY `members_email_unique` (`email`),
          KEY `members_status_index` (`status`),
          KEY `members_expires_at_index` (`expires_at`),
          KEY `members_user_id_foreign` (`user_id`),
          KEY `members_membership_plan_id_foreign` (`membership_plan_id`),
          CONSTRAINT `members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
          CONSTRAINT `members_membership_plan_id_foreign` FOREIGN KEY (`membership_plan_id`) REFERENCES `membership_plans` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `events` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `summary` text,
          `body` longtext,
          `location` varchar(255) DEFAULT NULL,
          `starts_at` datetime NOT NULL,
          `ends_at` datetime DEFAULT NULL,
          `registration_deadline` datetime DEFAULT NULL,
          `capacity` int unsigned DEFAULT NULL,
          `is_published` tinyint(1) NOT NULL DEFAULT '1',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `events_slug_unique` (`slug`),
          KEY `events_starts_at_index` (`starts_at`),
          KEY `events_is_published_index` (`is_published`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `event_tickets` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `event_id` bigint unsigned NOT NULL,
          `name` varchar(255) NOT NULL,
          `price` decimal(10,2) NOT NULL DEFAULT '0.00',
          `quantity_available` int unsigned DEFAULT NULL,
          `sort_order` int unsigned NOT NULL DEFAULT '0',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `event_tickets_event_id_foreign` (`event_id`),
          CONSTRAINT `event_tickets_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `event_registrations` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `event_id` bigint unsigned NOT NULL,
          `event_ticket_id` bigint unsigned DEFAULT NULL,
          `member_id` bigint unsigned DEFAULT NULL,
          `confirmation_code` varchar(255) NOT NULL,
          `name` varchar(255) NOT NULL,
          `email` varchar(255) NOT NULL,
          `phone` varchar(255) DEFAULT NULL,
          `quantity` int unsigned NOT NULL DEFAULT '1',
          `amount_due` decimal(10,2) NOT NULL DEFAULT '0.00',
          `status` enum('pending','registered','checked_in','cancelled') NOT NULL DEFAULT 'pending',
          `checked_in_at` datetime DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `event_registrations_confirmation_code_unique` (`confirmation_code`),
          KEY `event_registrations_status_index` (`status`),
          KEY `event_registrations_event_id_foreign` (`event_id`),
          KEY `event_registrations_event_ticket_id_foreign` (`event_ticket_id`),
          KEY `event_registrations_member_id_foreign` (`member_id`),
          CONSTRAINT `event_registrations_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
          CONSTRAINT `event_registrations_event_ticket_id_foreign` FOREIGN KEY (`event_ticket_id`) REFERENCES `event_tickets` (`id`) ON DELETE SET NULL,
          CONSTRAINT `event_registrations_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `donation_campaigns` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `description` text,
          `goal_amount` decimal(12,2) DEFAULT NULL,
          `raised_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
          `starts_at` date DEFAULT NULL,
          `ends_at` date DEFAULT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT '1',
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `donation_campaigns_slug_unique` (`slug`),
          KEY `donation_campaigns_is_active_index` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `donations` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `donation_campaign_id` bigint unsigned DEFAULT NULL,
          `donor_name` varchar(255) NOT NULL,
          `donor_email` varchar(255) NOT NULL,
          `amount` decimal(10,2) NOT NULL,
          `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
          `frequency` enum('one_time','weekly','monthly','yearly') NOT NULL DEFAULT 'one_time',
          `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
          `gateway_subscription_id` varchar(255) DEFAULT NULL,
          `next_charge_at` date DEFAULT NULL,
          `receipt_number` varchar(255) DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `donations_receipt_number_unique` (`receipt_number`),
          KEY `donations_status_index` (`status`),
          KEY `donations_donation_campaign_id_foreign` (`donation_campaign_id`),
          CONSTRAINT `donations_donation_campaign_id_foreign` FOREIGN KEY (`donation_campaign_id`) REFERENCES `donation_campaigns` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `payments` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `payable_type` varchar(255) NOT NULL,
          `payable_id` bigint unsigned NOT NULL,
          `gateway` enum('stripe','paypal') DEFAULT NULL,
          `gateway_reference` varchar(255) DEFAULT NULL,
          `amount` decimal(10,2) NOT NULL,
          `currency` varchar(3) NOT NULL DEFAULT 'usd',
          `status` enum('pending','succeeded','failed','refunded') NOT NULL DEFAULT 'pending',
          `payment_method` varchar(255) DEFAULT NULL,
          `paid_at` datetime DEFAULT NULL,
          `meta` json DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `payments_gateway_reference_index` (`gateway_reference`),
          KEY `payments_status_index` (`status`),
          KEY `payments_payable_type_payable_id_index` (`payable_type`, `payable_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


        SQL;
    }
}
