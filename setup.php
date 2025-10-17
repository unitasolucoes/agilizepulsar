<?php

define('PLUGIN_AGILIZEPULSAR_VERSION', '1.0.0');
define('PLUGIN_AGILIZEPULSAR_MIN_GLPI_VERSION', '10.0.0');
define('PLUGIN_AGILIZEPULSAR_MAX_GLPI_VERSION', '10.0.99');

function plugin_init_agilizepulsar() {
    global $PLUGIN_HOOKS;
    
    $PLUGIN_HOOKS['csrf_compliant']['agilizepulsar'] = true;
    
    Plugin::registerClass('PluginAgilizepulsarConfig');
    Plugin::registerClass('PluginAgilizepulsarMenu');
    Plugin::registerClass('PluginAgilizepulsarTicket');
    Plugin::registerClass('PluginAgilizepulsarLike');
    Plugin::registerClass('PluginAgilizepulsarComment');
    Plugin::registerClass('PluginAgilizepulsarUserPoints');
    Plugin::registerClass('PluginAgilizepulsarPointsHistory');
    Plugin::registerClass('PluginAgilizepulsarRankingConfig');
    Plugin::registerClass('PluginAgilizepulsarObjective');
    Plugin::registerClass('PluginAgilizepulsarFastReply');
    Plugin::registerClass('PluginAgilizepulsarApproval');
    Plugin::registerClass('PluginAgilizepulsarLog');
    Plugin::registerClass('PluginAgilizepulsarView');
    Plugin::registerClass('PluginAgilizepulsarTicketTab', ['addtabon' => 'Ticket']);
    Plugin::registerClass('PluginAgilizepulsarDashboard');
    
    $plugin = new Plugin();
    if ($plugin->isInstalled('agilizepulsar') && $plugin->isActivated('agilizepulsar')) {
        
        if (Session::haveRight('ticket', READ)) {
            $PLUGIN_HOOKS['menu_toadd']['agilizepulsar'] = ['tools' => 'PluginAgilizepulsarMenu'];
        }
        
        $PLUGIN_HOOKS['add_css']['agilizepulsar'][] = 'css/pulsar.css';
        $PLUGIN_HOOKS['add_javascript']['agilizepulsar'][] = 'js/pulsar.js';
        $PLUGIN_HOOKS['dashboard_cards']['agilizepulsar'] = ['PluginAgilizepulsarDashboard', 'dashboardCards'];
    }
}

function plugin_version_agilizepulsar() {
    return [
        'name'           => 'Unitá - Campanhas e Ideias',
        'version'        => PLUGIN_AGILIZEPULSAR_VERSION,
        'author'         => 'Unitá Soluções Digitais',
        'license'        => 'Comercial',
        'homepage'       => 'https://unitasolucoes.com.br',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_AGILIZEPULSAR_MIN_GLPI_VERSION,
                'max' => PLUGIN_AGILIZEPULSAR_MAX_GLPI_VERSION,
            ]
        ]
    ];
}

function plugin_agilizepulsar_check_prerequisites() {
    if (version_compare(GLPI_VERSION, PLUGIN_AGILIZEPULSAR_MIN_GLPI_VERSION, 'lt')
        || version_compare(GLPI_VERSION, PLUGIN_AGILIZEPULSAR_MAX_GLPI_VERSION, 'ge')) {
        echo "GLPI version not compatible. Requires " . PLUGIN_AGILIZEPULSAR_MIN_GLPI_VERSION . " to " . PLUGIN_AGILIZEPULSAR_MAX_GLPI_VERSION;
        return false;
    }
    return true;
}

function plugin_agilizepulsar_check_config($verbose = false) {
    return true;
}