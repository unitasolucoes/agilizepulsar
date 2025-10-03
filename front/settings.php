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

$view_profiles  = json_decode($config['view_profile_ids'] ?? '[]', true) ?: [];
$like_profiles  = json_decode($config['like_profile_ids'] ?? '[]', true) ?: [];
$admin_profiles = json_decode($config['admin_profile_ids'] ?? '[]', true) ?: [];

$campaign_category_id = (int)$config['campaign_category_id'];
$idea_category_id     = (int)$config['idea_category_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (method_exists('Session', 'checkCSRF')) {
        Session::checkCSRF();
    }

    $menu_name_post = trim($_POST['menu_name'] ?? '');
    $campaign_post  = (int)($_POST['campaign_category_id'] ?? 0);
    $idea_post      = (int)($_POST['idea_category_id'] ?? 0);
    $view_post      = isset($_POST['view_profile_ids']) ? array_map('intval', (array)$_POST['view_profile_ids']) : [];
    $like_post      = isset($_POST['like_profile_ids']) ? array_map('intval', (array)$_POST['like_profile_ids']) : [];
    $admin_post     = isset($_POST['admin_profile_ids']) ? array_map('intval', (array)$_POST['admin_profile_ids']) : [];

    $data = [
        'menu_name'            => $menu_name_post !== '' ? $menu_name_post : 'Pulsar',
        'campaign_category_id' => $campaign_post ?: $campaign_category_id,
        'idea_category_id'     => $idea_post ?: $idea_category_id,
        'view_profile_ids'     => json_encode(array_values(array_unique($view_post))),
        'like_profile_ids'     => json_encode(array_values(array_unique($like_post))),
        'admin_profile_ids'    => json_encode(array_values(array_unique($admin_post)))
    ];

    if (PluginAgilizepulsarConfig::updateConfig($data)) {
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

<div id="pulsar-demo" class="pulsar-wrap">

  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1><?php echo htmlspecialchars($menu_name); ?></h1>
      <p class="pulsar-muted">Configure as categorias e permissões do Pulsar.</p>
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

    <div class="form-actions">
      <button type="submit" class="btn-u primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
      <a href="feed.php" class="btn-u ghost"><i class="fa-solid fa-xmark"></i> Cancelar</a>
    </div>
  </form>
</div>

<style>
  .pulsar-wrap *{box-sizing:border-box;margin:0;padding:0}
  .pulsar-muted{color:#667085}
  .pulsar-wrap{padding:16px}
  :root{
    --u-primary:#00995d;--u-primary-hover:#008552;--u-border:#d1d5db;
    --u-chip:#e1e1e1;--u-dark:#004e4c;--u-success:#10b981;
  }
  .card-u{background:#fff;border:1px solid var(--u-border);border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.05);margin-bottom:16px}
  .pulsar-hero{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;background:linear-gradient(135deg,#f7f7f7 0%,#eef3fb 100%);padding:20px}
  .pulsar-hero h1{margin:0 0 6px 0;font-size:26px;color:var(--u-dark)}
  .pulsar-actions{display:flex;gap:12px;flex-wrap:wrap}
  .btn-u{border:0;border-radius:10px;padding:10px 16px;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
  .btn-u.primary{background:var(--u-primary);color:#fff}
  .btn-u.primary:hover{background:var(--u-primary-hover);transform:translateY(-1px);box-shadow:0 2px 4px rgba(0,0,0,.1)}
  .btn-u.ghost{background:#fff;border:1px solid var(--u-border)}
  .btn-u.ghost:hover{background:var(--u-chip);border-color:var(--u-primary)}
  .pulsar-topnav{display:flex;gap:8px;align-items:center;margin-bottom:16px;background:linear-gradient(180deg,#fff,#fbfcff);padding:12px 16px}
  .topnav-item{display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;color:#1f2933;text-decoration:none;font-weight:600}
  .topnav-item.is-active,.topnav-item:hover{background:rgba(0,153,93,.12);color:#00995d}
  .settings-form{display:flex;flex-direction:column;gap:16px}
  .form-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
  .form-group{display:flex;flex-direction:column;gap:8px}
  .form-group label{font-weight:600;color:#1f2933}
  .form-group select,
  .form-group input{border:1px solid var(--u-border);border-radius:8px;padding:10px;font-size:1rem}
  .form-group select[multiple]{min-height:160px}
  .form-actions{display:flex;gap:12px;justify-content:flex-end;padding:8px 0}
</style>
<?php
Html::footer();
