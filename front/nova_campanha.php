<?php

include('../../../inc/includes.php');
require_once __DIR__ . '/../inc/campanha.view.php';

Session::checkLoginUser();

$profileId = $_SESSION['glpiactiveprofile']['id'] ?? 0;
if (!PluginAgilizepulsarConfig::canAdmin($profileId)) {
    Html::displayRightError();
    exit;
}

$config = PluginAgilizepulsarConfig::getConfig();
$menuName = $config['menu_name'] ?? 'Pulsar';
$campaignCategoryId = (int) ($config['campaign_category_id'] ?? 152);

$campanhas = [];
try {
    global $DB;
    $iterator = $DB->request([
        'SELECT' => ['id', 'name'],
        'FROM'   => 'glpi_tickets',
        'WHERE'  => [
            'itilcategories_id' => $campaignCategoryId,
            'is_deleted'        => 0
        ],
        'ORDER'  => 'name ASC'
    ]);

    foreach ($iterator as $row) {
        $campanhas[] = $row;
    }
} catch (Throwable $exception) {
    error_log('Plugin Agilizepulsar - Erro ao buscar campanhas pai: ' . $exception->getMessage());
}

$areasPadrao = [
    'Administrativo',
    'Comercial',
    'Comunicação',
    'Financeiro',
    'Marketing',
    'Operações',
    'Recursos Humanos',
    'Tecnologia da Informação',
    'Relacionamento com o Cliente',
    'Jurídico'
];

$title = sprintf(__('%s – Nova Campanha', 'agilizepulsar'), $menuName);
if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
    Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}

$csrf = Session::getNewCSRFToken();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<?php
$pluginWeb = Plugin::getWebDir('agilizepulsar');
plugin_agilizepulsar_render_campanha_form($areasPadrao, $campanhas, $csrf);
?>

<script src="<?php echo $pluginWeb; ?>/js/campanha.form.js"></script>
<?php
if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpFooter();
} else {
    Html::footer();
}
