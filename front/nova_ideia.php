<?php

include('../../../inc/includes.php');
require_once __DIR__ . '/../inc/ideia.view.php';
require_once __DIR__ . '/../inc/ticket.class.php';

Session::checkLoginUser();

$profileId = $_SESSION['glpiactiveprofile']['id'] ?? 0;
if (!PluginAgilizepulsarConfig::canView($profileId)) {
    Html::displayRightError();
    exit;
}

$config = PluginAgilizepulsarConfig::getConfig();
$menuName = $config['menu_name'] ?? 'Pulsar';
$campaignCategoryId = (int) ($config['campaign_category_id'] ?? 152);

$activeStatuses = [Ticket::INCOMING, Ticket::ASSIGNED, Ticket::PLANNED, Ticket::WAITING];

$campanhas = [];
try {
    global $DB;
    $iterator = $DB->request([
        'SELECT' => ['id', 'name', 'time_to_resolve'],
        'FROM'   => 'glpi_tickets',
        'WHERE'  => [
            'itilcategories_id' => $campaignCategoryId,
            'is_deleted'        => 0,
            'status'            => $activeStatuses
        ],
        'ORDER'  => 'name ASC'
    ]);

    foreach ($iterator as $row) {
        $campanhas[] = $row;
    }
} catch (Throwable $exception) {
    error_log('Plugin Agilizepulsar - Erro ao buscar campanhas: ' . $exception->getMessage());
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

$autorNome = __('Usuário', 'agilizepulsar');
$user = new User();
if ($user->getFromDB(Session::getLoginUserID())) {
    $first = trim($user->fields['firstname'] ?? '');
    $last = trim($user->fields['realname'] ?? '');
    $login = trim($user->fields['name'] ?? '');

    $full = trim($first . ' ' . $last);
    if ($full !== '') {
        $autorNome = $full;
    } elseif ($last !== '') {
        $autorNome = $last;
    } elseif ($login !== '') {
        $autorNome = $login;
    }
}

$title = sprintf(__('%s – Nova Ideia', 'agilizepulsar'), $menuName);
if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
    Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}

$csrf = Session::getNewCSRFToken();
$pluginWeb = Plugin::getWebDir('agilizepulsar');

?>
<link rel="stylesheet" href="<?php echo $pluginWeb; ?>/css/forms.css">
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

<?php
plugin_agilizepulsar_render_ideia_form($campanhas, $areasPadrao, $csrf, $autorNome);
?>

<script src="<?php echo $pluginWeb; ?>/js/ideia.form.js"></script>
<?php
if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpFooter();
} else {
    Html::footer();
}
