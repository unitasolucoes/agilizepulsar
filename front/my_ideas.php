<?php

include('../../../inc/includes.php');
Session::checkLoginUser();

$user_profile = $_SESSION['glpiactiveprofile']['id'] ?? 0;

if (!PluginAgilizepulsarConfig::canView($user_profile)) {
    Html::displayRightError();
    exit;
}

$config = PluginAgilizepulsarConfig::getConfig();
$menu_name = $config['menu_name'];
$idea_form_url = $config['idea_form_url'] ?? '/marketplace/formcreator/front/formdisplay.php?id=121';
$plugin_web = Plugin::getWebDir('agilizepulsar');

$user_id = Session::getLoginUserID();
$ideas = PluginAgilizepulsarTicket::getIdeas(['users_id' => $user_id]);

$title = sprintf(__('%s – Minhas Ideias', 'agilizepulsar'), $menu_name);
if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
   Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}
$can_admin = PluginAgilizepulsarConfig::canAdmin($user_profile);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="<?php echo $plugin_web; ?>/css/pulsar.css"/>

<div id="pulsar-demo" class="pulsar-wrap">

  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1><?php echo htmlspecialchars($menu_name); ?></h1>
      <p class="pulsar-muted">Acompanhe suas contribuições e o status de cada ideia.</p>
    </div>
    <div class="pulsar-actions">
      <a href="<?php echo htmlspecialchars($idea_form_url); ?>" class="btn-u primary"><i class="fa-solid fa-plus"></i> Nova Ideia</a>
    </div>
  </section>

  <nav class="pulsar-topnav card-u" aria-label="Navegação do Pulsar">
    <a class="topnav-item" href="feed.php">
      <i class="fa-solid fa-bolt"></i>
      <span>Feed</span>
    </a>
    <a class="topnav-item is-active" href="my_ideas.php">
      <i class="fa-solid fa-lightbulb"></i>
      <span>Minhas Ideias</span>
    </a>
    <a class="topnav-item" href="dashboard.php">
      <i class="fa-solid fa-chart-bar"></i>
      <span>Dashboard</span>
    </a>    
    <?php if ($can_admin): ?>
    <a class="topnav-item" href="settings.php">
      <i class="fa-solid fa-gear"></i>
      <span>Configurações</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="pulsar-filters card-u">
    <div class="filter-group">
      <label>Status:</label>
      <select class="filter-select" id="filter-status">
        <option value="">Todas</option>
        <?php
        $statuses = Ticket::getAllStatusArray();
        foreach ($statuses as $key => $value) {
            echo "<option value='$key'>" . htmlspecialchars($value) . "</option>";
        }
        ?>
      </select>
    </div>
    
    <div class="filter-group">
      <label>Ordenar por:</label>
      <select class="filter-select" id="filter-order">
        <option value="recent">Mais recentes</option>
        <option value="oldest">Mais antigas</option>
        <option value="popular">Mais populares</option>
      </select>
    </div>
  </div>

  <main class="pulsar-ideas-list">
  
    <?php if (count($ideas) > 0): ?>
    <?php foreach ($ideas as $idea): 
      $user = new User();
      $user->getFromDB($idea['users_id_recipient']);
      
      $content_preview = $idea['content'];
      $content_preview = html_entity_decode($content_preview, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $content_preview = strip_tags($content_preview);
      $content_preview = preg_replace('/\s+/', ' ', $content_preview);
      $content_preview = substr(trim($content_preview), 0, 200);
    ?>
    <article class="idea-card card-u"
             data-status="<?php echo $idea['status']; ?>"
             data-campaign="<?php echo $idea['campaign_id'] ? (int) $idea['campaign_id'] : ''; ?>">
      <div class="idea-header">
        <h2><?php echo htmlspecialchars($idea['name']); ?></h2>
        <div class="idea-meta">
          <span class="badge <?php 
            if ($idea['status'] == Ticket::SOLVED) echo 'success';
            elseif ($idea['status'] == Ticket::CLOSED) echo 'implemented';
            elseif ($idea['status'] == Ticket::ASSIGNED) echo 'info';
            else echo 'warn';
          ?>">
            <i class="fa-solid fa-circle-<?php echo ($idea['status'] == Ticket::SOLVED ? 'check' : 'info'); ?>"></i> 
            <?php echo Ticket::getStatus($idea['status']); ?>
          </span>
        </div>
      </div>
      
      <div class="idea-content">
        <p><?php echo htmlspecialchars($content_preview); ?>...</p>
      </div>

      <?php if (!empty($idea['campaign_id'])): ?>
      <div class="idea-campaign-chip" title="Campanha vinculada">
        <i class="fa-solid fa-flag"></i>
        <span><?php echo htmlspecialchars($idea['campaign_name']); ?></span>
      </div>
      <?php endif; ?>

      <div class="idea-stats">
        <div class="stat-item">
          <i class="fa-solid fa-heart"></i>
          <span><?php echo $idea['likes_count']; ?> curtidas</span>
        </div>
        <div class="stat-item">
          <i class="fa-solid fa-comment"></i>
          <span><?php echo $idea['comments_count']; ?> comentários</span>
        </div>
        <div class="stat-item">
          <i class="fa-solid fa-calendar"></i>
          <span>Enviada em: <?php echo Html::convDate($idea['date']); ?></span>
        </div>
      </div>
      
      <div class="idea-actions">
        <a href="idea.php?id=<?php echo $idea['id']; ?>" class="btn-outline"><i class="fa-solid fa-eye"></i> Ver detalhes</a>
        <button class="btn-outline" onclick="navigator.clipboard.writeText('<?php echo $CFG_GLPI['url_base']; ?>/plugins/agilizepulsar/front/idea.php?id=<?php echo $idea['id']; ?>')"><i class="fa-solid fa-share-nodes"></i> Compartilhar</button>
        <button class="btn-outline link-campaign-btn"
                type="button"
                data-idea-id="<?php echo $idea['id']; ?>"
                data-campaign-id="<?php echo $idea['campaign_id'] ? (int) $idea['campaign_id'] : ''; ?>">
          <i class="fa-solid fa-flag"></i>
          <?php echo !empty($idea['campaign_id']) ? 'Alterar Campanha' : 'Vincular à Campanha'; ?>
        </button>
      </div>
    </article>
    <?php endforeach; ?>
    <?php else: ?>
      <div class="card-u empty-state">
        <i class="fa-solid fa-lightbulb"></i>
        <p class="empty-title">Nenhuma ideia encontrada</p>
        <p class="empty-subtitle">Você ainda não enviou nenhuma ideia.</p>
        <a href="<?php echo htmlspecialchars($idea_form_url); ?>" class="btn-u primary">
          <i class="fa-solid fa-plus"></i> Enviar primeira ideia
        </a>
      </div>
    <?php endif; ?>
    
  </main>
  
</div>


<script src="<?php echo $CFG_GLPI['root_doc']; ?>/plugins/agilizepulsar/js/pulsar.js"></script>
<script>
(function() {
  if (typeof PulsarCampaign === 'undefined') {
    return;
  }

  document.querySelectorAll('.link-campaign-btn').forEach(function(button) {
    button.addEventListener('click', function(event) {
      event.preventDefault();

      var ideaId = this.getAttribute('data-idea-id');
      var campaignId = this.getAttribute('data-campaign-id') || '';

      PulsarCampaign.openModal(ideaId, campaignId);
    });
  });
})();
</script>

<script src="<?php echo $CFG_GLPI['root_doc']; ?>/plugins/agilizepulsar/js/pulsar.js"></script>
<script>
(function() {
  if (typeof PulsarCampaign === 'undefined') {
    return;
  }

  document.querySelectorAll('.link-campaign-btn').forEach(function(button) {
    button.addEventListener('click', function(event) {
      event.preventDefault();

      var ideaId = this.getAttribute('data-idea-id');
      var campaignId = this.getAttribute('data-campaign-id') || '';

      PulsarCampaign.openModal(ideaId, campaignId);
    });
  });
})();
</script>

<?php
Html::footer();
?>