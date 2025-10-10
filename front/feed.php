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

$title = sprintf(__('%s – Feed', 'agilizepulsar'), $menu_name);
if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
   Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}

$campaigns = PluginAgilizepulsarTicket::getCampaigns(['is_active' => true]);
$ideas = PluginAgilizepulsarTicket::getIdeas();
$ideas = array_slice($ideas, 0, 3);
$ranking = PluginAgilizepulsarUserPoints::getRanking('total', 4);
$can_admin = PluginAgilizepulsarConfig::canAdmin($user_profile);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="<?php echo $plugin_web; ?>/css/pulsar.css"/>

<div id="pulsar-demo" class="pulsar-wrap">

  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1><?php echo htmlspecialchars($menu_name); ?></h1>
      <p class="pulsar-muted">Acompanhe campanhas ativas, engaje o time e transforme ideias em resultado.</p>
    </div>
    <div class="pulsar-actions">
      <a href="<?php echo htmlspecialchars($idea_form_url); ?>" class="btn-u primary"><i class="fa-solid fa-lightbulb"></i> Nova Ideia</a>
    </div>
  </section>

  <nav class="pulsar-topnav card-u" aria-label="Navegação do Pulsar">
    <a class="topnav-item is-active" href="feed.php" aria-selected="true">
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
    <?php if ($can_admin): ?>
    <a class="topnav-item" href="settings.php">
      <i class="fa-solid fa-gear"></i>
      <span>Configurações</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="pulsar-search">
    <input id="buscar-campanhas" type="text" placeholder="&#xf002; Buscar Campanhas...">
    <button class="search-clear" type="button" title="Limpar busca"><i class="fa-solid fa-times"></i></button>
  </div>

  <main class="pulsar-grid">

    <section class="main-content">

<?php foreach ($campaigns as $campaign): ?>
<?php
  // Processar conteúdo para preview
  $content_preview = $campaign['content'];
  
  // Decodificar entidades HTML primeiro
  $content_preview = html_entity_decode($content_preview, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  
  // Remover todas as tags HTML
  $content_preview = strip_tags($content_preview);
  
  // Remover múltiplos espaços e quebras de linhapmy_ideas.php
  $content_preview = preg_replace('/\s+/', ' ', $content_preview);
  
  // Pegar primeiros 150 caracteres
  $content_preview = substr(trim($content_preview), 0, 150);
?>
<article class="campanha card-u highlight" data-title="<?php echo htmlspecialchars($campaign['name']); ?>">
  <div class="campanha-header">
    <h2><i class="fa-solid fa-flag"></i> <?php echo htmlspecialchars($campaign['name']); ?></h2>
    <button class="share" title="Compartilhar"><i class="fa-solid fa-share-nodes"></i></button>
  </div>
  <p class="pulsar-muted"><?php echo htmlspecialchars($content_preview); ?>...</p>
  <div class="kpis">
    <span class="chip"><i class="fa-solid fa-calendar-days"></i> Prazo: <?php echo Html::convDateTime($campaign['time_to_resolve']); ?></span>
    <span class="chip"><i class="fa-solid fa-lightbulb"></i> <?php echo count(PluginAgilizepulsarTicket::getIdeasByCampaign($campaign['id'])); ?> ideias</span>
  </div>
  <div class="camp-buttons">
    <a href="campaign.php?id=<?php echo $campaign['id']; ?>" class="btn-green"><i class="fa-solid fa-circle-info"></i> Detalhes</a>
    <a href="<?php echo htmlspecialchars($idea_form_url); ?>" class="btn-outline"><i class="fa-solid fa-plus"></i> Participar</a>
  </div>
</article>
<?php endforeach; ?>

      <section class="ideas">
        <div class="section-header">
          <h3><i class="fa-solid fa-lightbulb"></i> Ideias Recentes</h3>
          <a href="ideas_all.php" class="btn-link">Ver todas</a>
        </div>

        <?php foreach ($ideas as $idea): ?>
        <div class="idea-card card-u">
          <div class="idea-title"><?php echo htmlspecialchars($idea['name']); ?></div>
          <div class="idea-meta pulsar-muted">por <?php 
            $user = new User();
            $user->getFromDB($idea['users_id_recipient']);
            echo $user->getFriendlyName();
          ?> • <?php echo Html::convDate($idea['date']); ?></div>
          <div class="idea-foot">
            <span class="badge info"><i class="fa-solid fa-circle-info"></i> <?php echo Ticket::getStatus($idea['status']); ?></span>
            <span><i class="fa-solid fa-heart"></i> <?php echo $idea['likes_count']; ?></span>
            <span><i class="fa-solid fa-comment"></i> <?php echo $idea['comments_count']; ?></span>
            <span class="pulsar-muted"><?php echo Html::convDate($idea['date']); ?></span>
          </div>
        </div>
        <?php endforeach; ?>

      </section>
    </section>

    <aside class="sidebar-pulsar">
      <div class="card-u">
        <div class="section-header">
          <h3><i class="fa-solid fa-ranking-star"></i> Ranking Geral</h3>
        </div>
        <ul class="ranking">
          <?php foreach ($ranking as $rank): ?>
          <li class="ranking-item">
            <div class="rank-left">
              <div class="avatar <?php 
                if ($rank['position'] == 1) echo 'gold';
                elseif ($rank['position'] == 2) echo 'silver';
                elseif ($rank['position'] == 3) echo 'bronze';
              ?>"><?php echo $rank['position']; ?></div>
              <div class="who">
                <div class="name"><?php echo htmlspecialchars($rank['user_name']); ?></div>
              </div>
            </div>
            <div class="points"><?php echo $rank['points']; ?> pts</div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </aside>
  </main>
</div>


<script>
  (function(){
    const input = document.getElementById('buscar-campanhas');
    const cards = Array.from(document.querySelectorAll('.campanha'));
    const clearBtn = document.querySelector('.search-clear');
    
    input.addEventListener('input', function(){
      const q = this.value.toLowerCase().trim();
      cards.forEach(c => {
        const t = (c.dataset.title || '').toLowerCase();
        c.style.display = t.includes(q) ? '' : 'none';
      });
    });
    
    clearBtn.addEventListener('click', function(){
      input.value = '';
      input.dispatchEvent(new Event('input'));
      input.focus();
    });
  })();
</script>

<?php
Html::footer();
?>