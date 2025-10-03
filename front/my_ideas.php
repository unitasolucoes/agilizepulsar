<?php

include('../../../inc/includes.php');
Session::checkLoginUser();

$user_id = Session::getLoginUserID();
$ideas = PluginAgilizepulsarTicket::getIdeas(['users_id' => $user_id]);

$title = __('Pulsar – Minhas Ideias');
if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
   Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

<div id="pulsar-demo" class="pulsar-wrap">

  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1>Pulsar</h1>
      <p class="pulsar-muted">Acompanhe suas contribuições e o status de cada ideia.</p>
    </div>
    <div class="pulsar-actions">
      <a href="/marketplace/formcreator/front/formdisplay.php?id=121" class="btn-u primary"><i class="fa-solid fa-plus"></i> Nova Ideia</a>
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
    <a class="topnav-item" href="settings.php">
      <i class="fa-solid fa-gear"></i>
      <span>Configurações</span>
    </a>
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
      ?>
      <article class="idea-card card-u" data-status="<?php echo $idea['status']; ?>">
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
          <p><?php echo htmlspecialchars(substr($idea['content'], 0, 200)); ?>...</p>
        </div>
        
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
        </div>
      </article>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="card-u text-center py-5">
        <div class="empty">
          <div class="empty-icon">
            <i class="fa-solid fa-bulb-off" style="font-size: 4rem; color: #ccc;"></i>
          </div>
          <p class="empty-title h3">Nenhuma ideia encontrada</p>
          <p class="empty-subtitle text-muted">Você ainda não enviou nenhuma ideia.</p>
          <div class="empty-action">
            <a href="/marketplace/formcreator/front/formdisplay.php?id=121" class="btn-u primary">
              <i class="fa-solid fa-plus"></i> Enviar primeira ideia
            </a>
          </div>
        </div>
      </div>
    <?php endif; ?>
    
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
    --u-dark: #004e4c;
    --u-danger: #dc2626;
    --u-implemented: #4d7c0f;
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
    background:linear-gradient(135deg,#f7f7f7 0%,#eef3fb 100%);
    padding:20px;
  }
  .pulsar-hero h1{margin:0 0 6px 0;font-size:26px;color:var(--u-dark)}
  .pulsar-actions{display:flex;gap:12px;flex-wrap:wrap}
  .btn-u{border:0;border-radius:10px;padding:10px 16px;font-weight:600;cursor:pointer;transition:all 0.2s ease;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
  .btn-u.primary{background:var(--u-primary);color:#fff}
  .btn-u.primary:hover{background:var(--u-primary-hover);transform:translateY(-1px);box-shadow:0 2px 4px rgba(0,0,0,0.1)}
  .btn-u.ghost{background:#fff;border:1px solid var(--u-border)}
  .btn-u.ghost:hover{background:var(--u-chip);border-color:var(--u-primary)}

  .pulsar-filters{
    display:flex;
    gap:16px;
    flex-wrap:wrap;
    align-items:center;
    margin-bottom:16px;
    padding:12px 16px;
  }
  .filter-group{
    display:flex;
    align-items:center;
    gap:8px;
  }
  .filter-group label{
    font-size:14px;
    color:var(--u-dark);
    font-weight:500;
  }
  .filter-select{
    padding:8px 12px;
    border:1px solid var(--u-border);
    border-radius:8px;
    background:#fff;
    font-size:14px;
    min-width:180px;
  }

  .pulsar-ideas-list{
    display:flex;
    flex-direction:column;
    gap:16px;
  }
  .idea-card{
    transition:transform 0.2s ease, box-shadow 0.2s ease;
  }
  .idea-card:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 8px rgba(0,0,0,0.1);
  }
  .idea-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    margin-bottom:12px;
  }
  .idea-header h2{
    margin:0;
    color:var(--u-dark);
    font-size:18px;
    flex-grow:1;
  }
  .idea-meta{
    display:flex;
    flex-direction:column;
    align-items:flex-end;
    gap:4px;
  }
  .idea-content p{
    margin:12px 0;
    color:var(--u-dark);
    line-height:1.5;
  }
  .idea-stats{
    display:flex;
    gap:16px;
    margin:12px 0;
    flex-wrap:wrap;
  }
  .stat-item{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:14px;
  }
  .stat-item i{
    color:var(--u-primary);
  }
  .idea-actions{
    display:flex;
    gap:12px;
    margin-top:16px;
    flex-wrap:wrap;
  }
  .btn-outline{
    background:transparent;
    border:1px solid var(--u-primary);
    color:var(--u-primary);
    border-radius:8px;
    padding:8px 12px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.2s ease;
    font-size:14px;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:8px;
  }
  .btn-outline:hover{
    background:rgba(0,153,93,0.05);
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
  .badge.success{
    background:#dff7ea;
    color:#166534;
  }
  .badge.info{
    background:#e6efff;
    color:#1e40af;
  }
  .badge.warn{
    background:#fff4e5;
    color:#b45309;
  }
  .badge.danger{
    background:#fee2e2;
    color:#b91c1c;
  }
  .badge.implemented{
    background:#ecfccb;
    color:var(--u-implemented);
  }

  .empty {
    text-align: center;
    padding: 2rem;
  }
  .empty-icon {
    margin-bottom: 1rem;
  }
  .empty-title {
    color: #495057;
    margin-bottom: 0.5rem;
  }
  .empty-subtitle {
    margin-bottom: 1.5rem;
  }
</style>

<?php
Html::footer();
?>