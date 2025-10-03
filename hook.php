<?php

function plugin_agilizepulsar_install() {
    global $DB;
    
    $migration = new Migration(PLUGIN_AGILIZEPULSAR_VERSION);
    
    if (!$DB->tableExists('glpi_plugin_agilizepulsar_config')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_config` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `menu_name` varchar(255) DEFAULT 'Pulsar',
            `campaign_category_id` int unsigned DEFAULT 152,
            `idea_category_id` int unsigned DEFAULT 153,
            `idea_form_url` varchar(255) DEFAULT '/marketplace/formcreator/front/formdisplay.php?id=121',
            `view_profile_ids` text,
            `like_profile_ids` text,
            `admin_profile_ids` text,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, $DB->error());

        $DB->insertOrDie('glpi_plugin_agilizepulsar_config', [
            'menu_name'             => 'Pulsar',
            'campaign_category_id'  => 152,
            'idea_category_id'      => 153,
            'idea_form_url'         => '/marketplace/formcreator/front/formdisplay.php?id=121',
            'view_profile_ids'      => json_encode([]),
            'like_profile_ids'      => json_encode([]),
            'admin_profile_ids'     => json_encode([])
        ]);
    }

    if ($DB->tableExists('glpi_plugin_agilizepulsar_config')
        && !$DB->fieldExists('glpi_plugin_agilizepulsar_config', 'idea_form_url')) {
        $migration->addField('glpi_plugin_agilizepulsar_config', 'idea_form_url', 'string');
        $migration->migrationOneTable('glpi_plugin_agilizepulsar_config');

        $DB->updateOrDie(
            'glpi_plugin_agilizepulsar_config',
            ['idea_form_url' => '/marketplace/formcreator/front/formdisplay.php?id=121'],
            []
        );
    }

    if (!$DB->tableExists('glpi_plugin_agilizepulsar_views')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_views` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `tickets_id` int unsigned NOT NULL DEFAULT '0',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `viewed_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tickets_id` (`tickets_id`),
            KEY `users_id` (`users_id`),
            KEY `ticket_date` (`tickets_id`, `viewed_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, $DB->error());
    }

    if (!$DB->tableExists('glpi_plugin_agilizepulsar_likes')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_likes` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `tickets_id` int unsigned NOT NULL DEFAULT '0',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ticket_user` (`tickets_id`, `users_id`),
            KEY `tickets_id` (`tickets_id`),
            KEY `users_id` (`users_id`),
            KEY `date_creation` (`date_creation`),
            KEY `ticket_date` (`tickets_id`, `date_creation`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->queryOrDie($query, $DB->error());
    }

    if (!$DB->tableExists('glpi_plugin_agilizepulsar_comments')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_comments` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `tickets_id` int unsigned NOT NULL DEFAULT '0',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `content` text,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tickets_id` (`tickets_id`),
            KEY `users_id` (`users_id`),
            KEY `date_creation` (`date_creation`),
            KEY `ticket_date` (`tickets_id`, `date_creation`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->queryOrDie($query, $DB->error());
    }

    $schema = $DB->dbdefault;

    if ($DB->tableExists('glpi_plugin_agilizepulsar_likes')) {
        $indexExists = $DB->request([
            'SELECT' => '1',
            'FROM'   => 'INFORMATION_SCHEMA.STATISTICS',
            'WHERE'  => [
                'TABLE_SCHEMA' => $schema,
                'TABLE_NAME'   => 'glpi_plugin_agilizepulsar_likes',
                'INDEX_NAME'   => 'ticket_date'
            ],
            'LIMIT'  => 1
        ]);

        if (count($indexExists) === 0) {
            $migration->addKey('glpi_plugin_agilizepulsar_likes', 'ticket_date', ['tickets_id', 'date_creation']);
            $migration->migrationOneTable('glpi_plugin_agilizepulsar_likes');
        }
    }

    if ($DB->tableExists('glpi_plugin_agilizepulsar_comments')) {
        $indexExists = $DB->request([
            'SELECT' => '1',
            'FROM'   => 'INFORMATION_SCHEMA.STATISTICS',
            'WHERE'  => [
                'TABLE_SCHEMA' => $schema,
                'TABLE_NAME'   => 'glpi_plugin_agilizepulsar_comments',
                'INDEX_NAME'   => 'ticket_date'
            ],
            'LIMIT'  => 1
        ]);

        if (count($indexExists) === 0) {
            $migration->addKey('glpi_plugin_agilizepulsar_comments', 'ticket_date', ['tickets_id', 'date_creation']);
            $migration->migrationOneTable('glpi_plugin_agilizepulsar_comments');
        }
    }

    if ($DB->tableExists('glpi_plugin_agilizepulsar_views')) {
        $indexExists = $DB->request([
            'SELECT' => '1',
            'FROM'   => 'INFORMATION_SCHEMA.STATISTICS',
            'WHERE'  => [
                'TABLE_SCHEMA' => $schema,
                'TABLE_NAME'   => 'glpi_plugin_agilizepulsar_views',
                'INDEX_NAME'   => 'ticket_date'
            ],
            'LIMIT'  => 1
        ]);

        if (count($indexExists) === 0) {
            $migration->addKey('glpi_plugin_agilizepulsar_views', 'ticket_date', ['tickets_id', 'viewed_at']);
            $migration->migrationOneTable('glpi_plugin_agilizepulsar_views');
        }
    }
    
    if (!$DB->tableExists('glpi_plugin_agilizepulsar_approvals')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_approvals` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `tickets_id` int unsigned NOT NULL DEFAULT '0',
            `step_number` tinyint NOT NULL DEFAULT '1',
            `groups_id` int unsigned NOT NULL DEFAULT '0',
            `users_id_validator` int unsigned NOT NULL DEFAULT '0',
            `status` tinyint NOT NULL DEFAULT '0',
            `comment` varchar(400) DEFAULT NULL,
            `date_validation` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tickets_id` (`tickets_id`),
            KEY `step_number` (`step_number`),
            KEY `groups_id` (`groups_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->queryOrDie($query, $DB->error());
    }
    
    if (!$DB->tableExists('glpi_plugin_agilizepulsar_userpoints')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_userpoints` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `points_total` int NOT NULL DEFAULT '0',
            `points_month` int NOT NULL DEFAULT '0',
            `points_year` int NOT NULL DEFAULT '0',
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `users_id` (`users_id`),
            KEY `points_total` (`points_total`),
            KEY `points_month` (`points_month`),
            KEY `points_year` (`points_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->queryOrDie($query, $DB->error());
    }
    
    if (!$DB->tableExists('glpi_plugin_agilizepulsar_pointshistory')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_pointshistory` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `action_type` varchar(50) NOT NULL,
            `points_earned` int NOT NULL DEFAULT '0',
            `reference_id` int unsigned NOT NULL DEFAULT '0',
            `reference_type` varchar(50) DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `users_id` (`users_id`),
            KEY `action_type` (`action_type`),
            KEY `date_creation` (`date_creation`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->queryOrDie($query, $DB->error());
    }
    
    if (!$DB->tableExists('glpi_plugin_agilizepulsar_rankingconfig')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_rankingconfig` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `action_type` varchar(50) NOT NULL,
            `points_value` int NOT NULL DEFAULT '0',
            `is_active` tinyint NOT NULL DEFAULT '1',
            PRIMARY KEY (`id`),
            UNIQUE KEY `action_type` (`action_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->queryOrDie($query, $DB->error());
        
        $defaults = [
            ['action_type' => 'submitted_idea', 'points_value' => 10],
            ['action_type' => 'approved_idea', 'points_value' => 50],
            ['action_type' => 'like', 'points_value' => 2],
            ['action_type' => 'comment', 'points_value' => 5],
            ['action_type' => 'implemented_idea', 'points_value' => 100]
        ];
        
        foreach ($defaults as $default) {
            $DB->insertOrDie('glpi_plugin_agilizepulsar_rankingconfig', $default);
        }
    }
    
    if (!$DB->tableExists('glpi_plugin_agilizepulsar_objectives')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_objectives` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `is_active` tinyint NOT NULL DEFAULT '1',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->queryOrDie($query, $DB->error());
    }
    
    if (!$DB->tableExists('glpi_plugin_agilizepulsar_fastreplies')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_fastreplies` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `content` text,
            `step_number` tinyint NOT NULL DEFAULT '1',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `step_number` (`step_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->queryOrDie($query, $DB->error());
    }
    
    if (!$DB->tableExists('glpi_plugin_agilizepulsar_logs')) {
        $query = "CREATE TABLE `glpi_plugin_agilizepulsar_logs` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `action` varchar(255) NOT NULL,
            `details` text,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `users_id` (`users_id`),
            KEY `action` (`action`),
            KEY `date_creation` (`date_creation`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->queryOrDie($query, $DB->error());
    }
    
    return true;
}

function plugin_agilizepulsar_uninstall() {
    global $DB;
    
    $tables = [
        'glpi_plugin_agilizepulsar_config',
        'glpi_plugin_agilizepulsar_views',
        'glpi_plugin_agilizepulsar_likes',
        'glpi_plugin_agilizepulsar_comments',
        'glpi_plugin_agilizepulsar_approvals',
        'glpi_plugin_agilizepulsar_userpoints',
        'glpi_plugin_agilizepulsar_pointshistory',
        'glpi_plugin_agilizepulsar_rankingconfig',
        'glpi_plugin_agilizepulsar_objectives',
        'glpi_plugin_agilizepulsar_fastreplies',
        'glpi_plugin_agilizepulsar_logs'
    ];
    
    foreach ($tables as $table) {
        $DB->queryOrDie("DROP TABLE IF EXISTS `$table`", $DB->error());
    }
    
    return true;
}