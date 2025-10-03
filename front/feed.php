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
      <article class="campanha card-u highlight" data-title="<?php echo htmlspecialchars($campaign['name']); ?>">
        <div class="campanha-header">
          <h2><i class="fa-solid fa-flag"></i> <?php echo htmlspecialchars($campaign['name']); ?></h2>
          <button class="share" title="Compartilhar"><i class="fa-solid fa-share-nodes"></i></button>
        </div>
        <p class="pulsar-muted"><?php echo htmlspecialchars(substr($campaign['content'], 0, 150)); ?>...</p>
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

<style>
  .pulsar-wrap *{box-sizing:border-box;margin:0;padding:0}
  .pulsar-muted{color:#667085}
  .pulsar-wrap{padding:16px}

  :root{
    --u-primary: #00995d;
    --u-primary-hover: #008552;
    --u-border: #d1d5db;
    --u-chip: #e1e1e1;
    --u-soft: #e1e1e1;
    --u-accent1: #b1d34b;
    --u-accent2: #f47920;
    --u-dark: #004e4c;
    --u-bg-campanha: #f7f2ecff;
    --u-bg-highlight: #f5f0ea;
  }

  .card-u{
    background:#fff;
    border:1px solid var(--u-border);
    border-radius:12px;
    padding:16px;
    box-shadow:0 1px 2px rgba(0,0,0,0.05);
    margin-bottom:16px;
  }

  .pulsar-topnav{
    display:flex;gap:8px;align-items:center;margin-bottom:16px;
    background:linear-gradient(180deg,#fff, #fbfcff);
    padding:12px 16px;
  }
  .topnav-item{
    display:inline-flex;gap:8px;align-items:center;
    padding:10px 16px;border:1px solid var(--u-border);border-radius:10px;background:#fff;cursor:pointer;
    font-weight:600; color:var(--u-dark);transition:all 0.2s ease;text-decoration:none;
  }
  .topnav-item:hover {
    background:var(--u-chip);
    border-color:var(--u-primary);
  }
  .topnav-item.is-active{
    background:var(--u-primary);
    color:#fff;
    border-color:var(--u-primary);
  }
  .topnav-item.is-active i{color:#fff}

  .pulsar-hero{
    display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;
    background:linear-gradient(135deg,var(--u-soft) 0%,#eef3fb 100%);
    padding:20px;
  }
  .pulsar-hero h1{margin:0 0 6px 0;font-size:26px;color:var(--u-dark)}
  .pulsar-actions{display:flex;gap:12px;flex-wrap:wrap}
  .btn-u{border:0;border-radius:10px;padding:10px 16px;font-weight:600;cursor:pointer;transition:all 0.2s ease;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
  .btn-u.primary{background:var(--u-primary);color:#fff}
  .btn-u.primary:hover{background:var(--u-primary-hover);transform:translateY(-1px);box-shadow:0 2px 4px rgba(0,0,0,0.1)}
  .btn-u.ghost{background:#fff;border:1px solid var(--u-border)}
  .btn-u.ghost:hover{background:var(--u-chip);border-color:var(--u-primary)}
  .btn-link{background:none;border:none;color:var(--u-primary);cursor:pointer;font-weight:600;padding:4px 0;text-decoration:none;}

  .pulsar-search{
    position:relative;
    margin:16px 0;
    display:flex;
    align-items:center;
  }
  .pulsar-search input{
    width:100%;
    padding:12px 16px 12px 40px;
    border:1px solid var(--u-border);
    border-radius:10px;
    background:#fff;
    font-size:14px;
    transition:all 0.2s ease;
  }
  .pulsar-search input:focus{
    outline:none;
    border-color:var(--u-primary);
    box-shadow:0 0 0 2px rgba(0,153,93,0.1);
  }
  .pulsar-search input::placeholder{
    color:#9aa3af;
  }
  .search-clear{
    position:absolute;
    right:12px;
    background:none;
    border:none;
    color:#9aa3af;
    cursor:pointer;
    opacity:0;
    transition:opacity 0.2s ease;
  }
  .pulsar-search input:not(:placeholder-shown) + .search-clear{
    opacity:1;
  }

  .pulsar-grid{
    display:grid;
    grid-template-columns:1fr 320px;
    gap:20px;
  }
  @media (max-width:1100px){
    .pulsar-grid{
      grid-template-columns:1fr;
    }
  }

  .campanha{
    position:relative;
    margin-bottom:16px;
    transition:transform 0.2s ease, box-shadow 0.2s ease;
  }
  .campanha.highlight{
    background-color:var(--u-bg-campanha);
    border-left:4px solid var(--u-primary);
  }
  .campanha:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 8px rgba(0,0,0,0.05);
  }
  .campanha-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:8px;
  }
  .campanha-header h2{
    margin:0;
    font-size:18px;
    display:flex;
    align-items:center;
    gap:8px;
    color:var(--u-dark);
  }
  .kpis{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin:12px 0;
  }
  .chip{
    background:var(--u-chip);
    border-radius:999px;
    padding:6px 12px;
    font-size:13px;
    display:inline-flex;
    align-items:center;
    gap:6px;
  }
  .chip.warn{
    background: #ffe3b3ff;
    color: #b45309;
  }
  .camp-buttons{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
  }
  .btn-green{
    background:var(--u-primary);
    color:#fff;
    border:0;
    border-radius:8px;
    padding:10px 16px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.2s ease;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:8px;
  }
  .btn-green:hover{
    background:var(--u-primary-hover);
    transform:translateY(-1px);
  }
  .btn-outline{
    background:transparent;
    border:1px solid var(--u-primary);
    color:var(--u-primary);
    border-radius:8px;
    padding:10px 16px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.2s ease;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:8px;
  }
  .btn-outline:hover{
    background:rgba(0,153,93,0.05);
  }
  .share{
    border:1px solid var(--u-border);
    background:#fff;
    border-radius:8px;
    width:36px;
    height:36px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:all 0.2s ease;
  }
  .share:hover{
    background:var(--u-chip);
    color:var(--u-primary);
  }

  .ideas{
    margin-top:24px;
  }
  .section-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:12px;
  }
  .ideas h3{
    margin:0;
    color:var(--u-dark);
    font-size:18px;
    display:flex;
    align-items:center;
    gap:10px;
  }
  .idea-card{
    margin-bottom:12px;
    padding:16px;
    transition:transform 0.2s ease;
  }
  .idea-card:hover{
    transform:translateX(4px);
  }
  .idea-title{
    font-weight:700;
    margin-bottom:6px;
    color:var(--u-dark);
    font-size:15px;
  }
  .idea-meta{
    margin-bottom:12px;
    font-size:13px;
  }
  .idea-foot{
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
    font-size:13px;
  }
  .idea-foot i{
    margin-right:4px;
  }
  .badge{
    border-radius:999px;
    padding:4px 10px;
    font-size:12px;
    font-weight:600;
    display:inline-flex;
    align-items:center;
    gap:6px;
  }
  .badge.info{
    background:#e6efff;
    color:#1e40af;
  }
  .badge.success{
    background:#dff7ea;
    color:#166534;
  }
  .badge.warn{
    background:#fff4e5;
    color:#b45309;
  }

  .ranking{
    list-style:none;
    margin:0;
    padding:0;
  }
  .ranking-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:12px 0;
    border-bottom:1px dashed var(--u-border);
    transition:background 0.2s ease;
  }
  .ranking-item:hover{
    background:var(--u-chip);
  }
  .ranking-item:last-child{
    border-bottom:0;
  }
  .rank-left{
    display:flex;
    align-items:center;
    gap:12px;
  }
  .avatar{
    width:36px;
    height:36px;
    border-radius:999px;
    background:#e9eef6;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    color:#334155;
    flex-shrink:0;
  }
  .avatar.gold{
    background:linear-gradient(135deg,#fcd34d,#fbbf24);
    color:#92400e;
  }
  .avatar.silver{
    background:linear-gradient(135deg,#e5e7eb,#d1d5db);
    color:#374151;
  }
  .avatar.bronze{
    background:linear-gradient(135deg,#fca5a5,#f87171);
    color:#991b1b;
  }
  .who .name{
    font-weight:600;
    color:var(--u-dark);
    margin-bottom:2px;
  }
  .department{
    font-size:12px;
  }
  .points{
    background:var(--u-chip);
    border-radius:999px;
    padding:6px 12px;
    font-weight:700;
    font-size:13px;
    color:var(--u-dark);
  }
</style>

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