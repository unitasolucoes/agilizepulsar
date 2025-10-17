<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarConfig extends CommonDBTM {
    static $rightname = 'config';

    public static function getConfig() {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'LIMIT' => 1
        ]);

        if (count($iterator) > 0) {
            return $iterator->current();
        }

        return [
            'menu_name'            => 'Pulsar',
            'campaign_category_id' => 152,
            'idea_category_id'     => 153,
            'idea_form_url'        => '/marketplace/formcreator/front/formdisplay.php?id=121',
            'view_profile_ids'     => json_encode([]),
            'like_profile_ids'     => json_encode([]),
            'admin_profile_ids'    => json_encode([])
        ];
    }

    public static function updateConfig($data) {
        $config = new self();
        $existing = self::getConfig();

        if (isset($existing['id'])) {
            $data['id'] = $existing['id'];
            return $config->update($data);
        }

        return $config->add($data);
    }

    public static function canView($user_profile_id = null) {
        if ($user_profile_id === null) {
            return parent::canView();
        }

        $config  = self::getConfig();
        $allowed = json_decode($config['view_profile_ids'] ?? '[]', true) ?: [];

        return empty($allowed) || in_array($user_profile_id, $allowed);
    }

    public static function canLike($user_profile_id) {
        $config  = self::getConfig();
        $allowed = json_decode($config['like_profile_ids'] ?? '[]', true) ?: [];

        return empty($allowed) || in_array($user_profile_id, $allowed);
    }

    public static function canAdmin($user_profile_id) {
        $config  = self::getConfig();
        $allowed = json_decode($config['admin_profile_ids'] ?? '[]', true) ?: [];

        return empty($allowed) || in_array($user_profile_id, $allowed);
    }

    /**
     * Gera iniciais padronizadas do usuário para avatar
     * 
     * @param string $firstname Nome do usuário
     * @param string $realname Sobrenome do usuário
     * @return string Iniciais (2 caracteres em maiúsculo)
     */
    public static function getUserInitials($firstname = '', $realname = '') {
        $firstname = trim($firstname ?? '');
        $realname = trim($realname ?? '');
        
        // Se tiver primeiro nome e sobrenome
        if (!empty($firstname) && !empty($realname)) {
            return strtoupper(substr($firstname, 0, 1) . substr($realname, 0, 1));
        }
        
        // Se tiver apenas sobrenome
        if (!empty($realname)) {
            return strtoupper(substr($realname, 0, 2));
        }
        
        // Se tiver apenas primeiro nome
        if (!empty($firstname)) {
            return strtoupper(substr($firstname, 0, 2));
        }
        
        // Fallback
        return '??';
    }
}

