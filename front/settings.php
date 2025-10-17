<?php

include('../../../inc/includes.php');
Session::checkLoginUser();

$user_profile = $_SESSION['glpiactiveprofile']['id'] ?? 0;

if (!PluginAgilizepulsarConfig::canView($user_profile)) {
    Html::displayRightError();
    exit;
}

if (!PluginAgilizepulsarConfig::canAdmin($user_profile)) {
    Html::displayRightError();
    exit;
}

$config = PluginAgilizepulsarConfig::getConfig();
$menu_name = $config['menu_name'];
$plugin_web = Plugin::getWebDir('agilizepulsar');
$ranking_configs = PluginAgilizepulsarRankingConfig::getAllConfig();

$ranking_actions = [
    'submitted_idea'   => __('Ideia enviada', 'agilizepulsar'),
    'approved_idea'    => __('Ideia aprovada', 'agilizepulsar'),
    'implemented_idea' => __('Ideia implementada', 'agilizepulsar'),
    'like'             => __('Curtida registrada', 'agilizepulsar'),
    'comment'          => __('Comentário publicado', 'agilizepulsar')
];

$view_profiles  = json_decode($config['view_profile_ids'] ?? '[]', true) ?: [];
$like_profiles  = json_decode($config['like_profile_ids'] ?? '[]', true) ?: [];
$admin_profiles = json_decode($config['admin_profile_ids'] ?? '[]', true) ?: [];

$campaign_category_id = (int)$config['campaign_category_id'];
$idea_category_id     = (int)$config['idea_category_id'];
$formcreator_form_id_idea = (int)($config['formcreator_form_id_idea'] ?? 0);
$formcreator_form_id_campaign = (int)($config['formcreator_form_id_campaign'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (method_exists('Session', 'checkCSRF')) {
        Session::checkCSRF();
    }

    $menu_name_post = trim($_POST['menu_name'] ?? '');
    $campaign_post  = (int)($_POST['campaign_category_id'] ?? 0);
    $idea_post      = (int)($_POST['idea_category_id'] ?? 0);
    $idea_form_post = trim($_POST['idea_form_url'] ?? '');
    $formcreator_idea_post = (int)($_POST['formcreator_form_id_idea'] ?? 0);
    $formcreator_campaign_post = (int)($_POST['formcreator_form_id_campaign'] ?? 0);
    $view_post      = isset($_POST['view_profile_ids']) ? array_map('intval', (array)$_POST['view_profile_ids']) : [];
    $like_post      = isset($_POST['like_profile_ids']) ? array_map('intval', (array)$_POST['like_profile_ids']) : [];
    $admin_post     = isset($_POST['admin_profile_ids']) ? array_map('intval', (array)$_POST['admin_profile_ids']) : [];

    $data = [
        'menu_name'            => $menu_name_post !== '' ? $menu_name_post : 'Pulsar',
        'campaign_category_id' => $campaign_post ?: $campaign_category_id,
        'idea_category_id'     => $idea_post ?: $idea_category_id,
        'idea_form_url'        => $idea_form_post !== '' ? $idea_form_post : ($config['idea_form_url'] ?? '/marketplace/formcreator/front/formdisplay.php?id=121'),
        'formcreator_form_id_idea'     => max(0, $formcreator_idea_post),
        'formcreator_form_id_campaign' => max(0, $formcreator_campaign_post),
        'view_profile_ids'     => json_encode(array_values(array_unique($view_post))),
        'like_profile_ids'     => json_encode(array_values(array_unique($like_post))),
        'admin_profile_ids'    => json_encode(array_values(array_unique($admin_post)))
    ];

    $update_success = PluginAgilizepulsarConfig::updateConfig($data);

  $ranking_post = $_POST['ranking'] ?? [];
  foreach ($ranking_actions as $action_key => $action_label) {
      if (isset($ranking_post[$action_key]['points_value'])) {
          $raw_points = $ranking_post[$action_key]['points_value'];
          if (is_array($raw_points)) {
              $raw_points = reset($raw_points);
          }
          $points_value = max(0, (int)$raw_points);
      } else {
          $points_value = isset($ranking_configs[$action_key]['points_value']) 
              ? (int)$ranking_configs[$action_key]['points_value'] 
              : 0;
      }

      $update_success = PluginAgilizepulsarRankingConfig::updatePointsValue($action_key, $points_value) && $update_success;
  }

    if ($update_success) {
        Session::addMessageAfterRedirect(__('Configurações atualizadas com sucesso.', 'agilizepulsar'), true, INFO);
    } else {
        Session::addMessageAfterRedirect(__('Não foi possível atualizar as configurações.', 'agilizepulsar'), true, ERROR);
    }

    Html::redirect($_SERVER['REQUEST_URI']);
    exit;
}

$categories = [];
$category_iterator = $DB->request([
    'SELECT' => ['id', 'completename'],
    'FROM'   => 'glpi_itilcategories',
    'ORDER'  => 'completename'
]);
foreach ($category_iterator as $row) {
    $categories[] = $row;
}

$profiles = [];
$profile_iterator = $DB->request([
    'SELECT' => ['id', 'name'],
    'FROM'   => 'glpi_profiles',
    'ORDER'  => 'name'
]);
foreach ($profile_iterator as $row) {
    $profiles[] = $row;
}

$title = sprintf(__('%s – Configurações', 'agilizepulsar'), $menu_name);
if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
   Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}

$csrf_token = Session::getNewCSRFToken();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="<?php echo $plugin_web; ?>/css/pulsar.css"/>

<div id="pulsar-demo" class="pulsar-wrap">

  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1><?php echo htmlspecialchars($menu_name); ?></h1>
      <p class="pulsar-muted"><?php echo __('Configure as categorias, permissões e pontuações do Pulsar.', 'agilizepulsar'); ?></p>
    </div>
    <div class="pulsar-actions">
      <a href="feed.php" class="btn-u ghost"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
    </div>
  </section>

  <nav class="pulsar-topnav card-u" aria-label="Navegação do Pulsar">
    <a class="topnav-item" href="feed.php">
      <i class="fa-solid fa-bolt"></i>
      <span>Feed</span>
    </a>
    <a class="topnav-item" href="my_ideas.php">
      <i class="fa-solid fa-lightbulb"></i>
      <span>Minhas Ideias</span>
    </a>
    <a class="topnav-item" href="dashboard.php">
      <i class="fa-solid fa-chart-bar"></i>
      <span>Dashboard</span>
    </a>
    <a class="topnav-item is-active" href="settings.php">
      <i class="fa-solid fa-gear"></i>
      <span>Configurações</span>
    </a>
  </nav>

  <form method="post" class="settings-form">
    <input type="hidden" name="_glpi_csrf_token" value="<?php echo $csrf_token; ?>">

    <section class="card-u">
      <h2><i class="fa-solid fa-sliders"></i> Configurações Gerais</h2>
      <div class="form-grid">
        <div class="form-group">
          <label for="menu_name">Nome do Menu</label>
          <input type="text" id="menu_name" name="menu_name" value="<?php echo htmlspecialchars($config['menu_name']); ?>" required>
        </div>
        <div class="form-group">
          <label for="campaign_category_id">Categoria de Campanhas</label>
          <select id="campaign_category_id" name="campaign_category_id" required>
            <?php foreach ($categories as $category): ?>
              <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $campaign_category_id) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($category['completename']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="idea_category_id">Categoria de Ideias</label>
          <select id="idea_category_id" name="idea_category_id" required>
            <?php foreach ($categories as $category): ?>
              <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $idea_category_id) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($category['completename']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="idea_form_url">URL do formulário de ideias</label>
          <input type="url" id="idea_form_url" name="idea_form_url" value="<?php echo htmlspecialchars($config['idea_form_url'] ?? '/marketplace/formcreator/front/formdisplay.php?id=121'); ?>" required>
          <small class="pulsar-muted">Defina o endereço do formulário FormCreator utilizado para registrar novas ideias.</small>
        </div>
      </div>
    </section>

    <section class="card-u">
      <h2><i class="fa-solid fa-user-lock"></i> Permissões</h2>
      <div class="form-grid">
        <div class="form-group">
          <label for="view_profile_ids">Perfis que podem visualizar</label>
          <select id="view_profile_ids" name="view_profile_ids[]" multiple>
            <?php foreach ($profiles as $profile): ?>
              <option value="<?php echo $profile['id']; ?>" <?php echo in_array($profile['id'], $view_profiles) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($profile['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="pulsar-muted">Vazio significa acesso liberado para todos.</small>
        </div>
        <div class="form-group">
          <label for="like_profile_ids">Perfis que podem curtir</label>
          <select id="like_profile_ids" name="like_profile_ids[]" multiple>
            <?php foreach ($profiles as $profile): ?>
              <option value="<?php echo $profile['id']; ?>" <?php echo in_array($profile['id'], $like_profiles) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($profile['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="pulsar-muted">Vazio significa acesso liberado para todos.</small>
        </div>
        <div class="form-group">
          <label for="admin_profile_ids">Perfis administradores</label>
          <select id="admin_profile_ids" name="admin_profile_ids[]" multiple>
            <?php foreach ($profiles as $profile): ?>
              <option value="<?php echo $profile['id']; ?>" <?php echo in_array($profile['id'], $admin_profiles) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($profile['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="pulsar-muted">Administradores têm acesso às configurações.</small>
        </div>
      </div>
    </section>

    <section class="card-u">
      <h2><i class="fa-solid fa-arrows-turn-right"></i> <?php echo __('Redirecionamento de formulários', 'agilizepulsar'); ?></h2>
      <div class="form-grid">
        <div class="form-group">
          <label for="formcreator_form_id_idea">ID do Formulário de Ideia (FormCreator)</label>
          <input type="number" id="formcreator_form_id_idea" name="formcreator_form_id_idea" min="0" value="<?php echo (int)$formcreator_form_id_idea; ?>">
          <small class="pulsar-muted"><?php echo __('Defina o ID do formulário do FormCreator que deve ser redirecionado para o formulário nativo de ideias. Use 0 para desabilitar.', 'agilizepulsar'); ?></small>
        </div>
        <div class="form-group">
          <label for="formcreator_form_id_campaign">ID do Formulário de Campanha (FormCreator)</label>
          <input type="number" id="formcreator_form_id_campaign" name="formcreator_form_id_campaign" min="0" value="<?php echo (int)$formcreator_form_id_campaign; ?>">
          <small class="pulsar-muted"><?php echo __('Defina o ID do formulário do FormCreator que deve ser redirecionado para o formulário nativo de campanhas. Use 0 para desabilitar.', 'agilizepulsar'); ?></small>
        </div>
      </div>
      <div class="info-box">
        <strong><?php echo __('Como descobrir o ID?', 'agilizepulsar'); ?></strong>
        <p class="pulsar-muted"><?php echo __('Acesse Assistência &gt; Formulários, abra o formulário desejado e confira o parâmetro <code>id</code> na URL.', 'agilizepulsar'); ?></p>
        <code>front/form.form.php?id=<strong>62</strong></code>
      </div>
    </section>

  <section class="card-u">
    <h2><i class="fa-solid fa-trophy"></i> <?php echo __('Pontuação', 'agilizepulsar'); ?></h2>
    <p class="pulsar-muted"><?php echo __('Configure a pontuação atribuída para cada ação do Pulsar. Use 0 para desativar.', 'agilizepulsar'); ?></p>
    <div class="table-responsive">
      <table class="table-u">
        <thead>
          <tr>
            <th><?php echo __('Ação', 'agilizepulsar'); ?></th>
            <th><?php echo __('Pontuação', 'agilizepulsar'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ranking_actions as $action_key => $action_label):
              $current_points = $ranking_configs[$action_key]['points_value'] ?? 0;
          ?>
            <tr>
              <td><?php echo htmlspecialchars($action_label); ?></td>
              <td>
                <input type="number"
                      id="ranking_<?php echo htmlspecialchars($action_key); ?>_points"
                      name="ranking[<?php echo htmlspecialchars($action_key); ?>][points_value]"
                      min="0"
                      step="1"
                      value="<?php echo (int)$current_points; ?>"
                      class="input-small">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

    <div class="form-actions">
      <button type="submit" class="btn-u primary"><i class="fa-solid fa-floppy-disk"></i> <?php echo __('Salvar configurações', 'agilizepulsar'); ?></button>
      <a href="feed.php" class="btn-u ghost"><i class="fa-solid fa-xmark"></i> <?php echo __('Cancelar', 'agilizepulsar'); ?></a>
    </div>
  </form>
</div>

<?php
Html::footer();
