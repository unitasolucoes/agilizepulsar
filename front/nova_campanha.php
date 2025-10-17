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
$pluginWeb = Plugin::getWebDir('agilizepulsar');

?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-wxM6d1YjIxtBlsYxJ3aXHCMGN28AL/fOHnqd7qV3CyMfCVxYvBy06SnVAk0nnBYnCTsRmRykGGBqBP1ZiZ8Ykg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
<link rel="stylesheet" href="<?php echo $pluginWeb; ?>/css/pulsar.css" />
<link rel="stylesheet" href="<?php echo $pluginWeb; ?>/css/forms.css" />

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<?php
plugin_agilizepulsar_render_campanha_form($campanhas, $areasPadrao, $csrf);
?>

<script src="<?php echo $pluginWeb; ?>/js/campanha.form.js"></script>
<?php
if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpFooter();
} else {
    Html::footer();
}
