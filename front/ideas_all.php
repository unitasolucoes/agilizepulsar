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

$title = sprintf(__('%s – Todas as Ideias', 'agilizepulsar'), $menu_name);
if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
   Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}

// Buscar todas as ideias
$ideas = PluginAgilizepulsarTicket::getIdeas();

// Buscar campanhas para o filtro
$campaigns = PluginAgilizepulsarTicket::getCampaigns();
$can_admin = PluginAgilizepulsarConfig::canAdmin($user_profile);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

<div id="pulsar-demo" class="pulsar-wrap">

  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1><?php echo htmlspecialchars($menu_name); ?></h1>
      <p class="pulsar-muted">Explore todas as ideias enviadas pela comunidade.</p>
    </div>
    <div class="pulsar-actions">
      <a href="<?php echo htmlspecialchars($idea_form_url); ?>" class="btn-u primary"><i class="fa-solid fa-plus"></i> Nova Ideia</a>
      <a href="feed.php" class="btn-u ghost"><i class="fa-solid fa-arrow-left"></i> Voltar ao Feed</a>
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
    <?php if ($can_admin): ?>
    <a class="topnav-item" href="settings.php">
      <i class="fa-solid fa-gear"></i>
      <span>Configurações</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="pulsar-filters-container card-u">
    <div class="pulsar-search-inline">
      <input id="buscar-ideias" type="text" placeholder="&#xf002; Buscar ideias por título ou conteúdo...">
      <button class="search-clear" type="button" title="Limpar busca"><i class="fa-solid fa-times"></i></button>
    </div>

    <div class="pulsar-filters">
      <div class="filter-group">
        <label>Status:</label>
        <select class="filter-select" id="filter-status">
          <option value="">Todos</option>
          <?php
          $statuses = Ticket::getAllStatusArray();
          foreach ($statuses as $key => $value) {
              echo "<option value='$key'>" . htmlspecialchars($value) . "</option>";
          }
          ?>
        </select>
      </div>
      
      <div class="filter-group">
        <label>Campanha:</label>
        <select class="filter-select" id="filter-campaign">
          <option value="">Todas</option>
          <?php foreach ($campaigns as $campaign): ?>
            <option value="<?php echo $campaign['id']; ?>"><?php echo htmlspecialchars($campaign['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="filter-group">
        <label>Ordenar por:</label>
        <select class="filter-select" id="filter-order">
          <option value="recent">Mais recentes</option>
          <option value="oldest">Mais antigas</option>
          <option value="popular">Mais curtidas</option>
          <option value="commented">Mais comentadas</option>
        </select>
      </div>

      <button class="btn-u ghost" id="btn-clear-filters"><i class="fa-solid fa-filter-circle-xmark"></i> Limpar filtros</button>
    </div>

    <div class="ideas-counter">
      <span id="ideas-count"><?php echo count($ideas); ?></span> ideias encontradas
    </div>
  </div>

  <main class="pulsar-ideas-grid">
  
    <?php if (count($ideas) > 0): ?>
      <?php foreach ($ideas as $idea): 
        $user = new User();
        $user->getFromDB($idea['users_id_recipient']);
        
        $statusClass = 'info';
        if ($idea['status'] == Ticket::SOLVED) $statusClass = 'success';
        elseif ($idea['status'] == Ticket::CLOSED) $statusClass = 'implemented';
        elseif ($idea['status'] == Ticket::WAITING) $statusClass = 'warn';
      ?>
      <?php
        $idea_title_attr = htmlspecialchars(strtolower(strip_tags((string) $idea['name'])));
        $idea_content_attr = htmlspecialchars(strtolower(strip_tags((string) $idea['content'])));
      ?>
      <article class="idea-card card-u"
               data-status="<?php echo (int) $idea['status']; ?>"
               data-title="<?php echo $idea_title_attr; ?>"
               data-content="<?php echo $idea_content_attr; ?>"
               data-likes="<?php echo (int) $idea['likes_count']; ?>"
               data-comments="<?php echo (int) $idea['comments_count']; ?>"
               data-date="<?php echo strtotime($idea['date']); ?>"
               data-campaign="">
        
        <div class="idea-card-header">
          <div class="idea-author">
            <span class="author-avatar"><?php echo strtoupper(substr($user->fields['realname'], 0, 2)); ?></span>
            <div class="author-info">
              <div class="author-name"><?php echo $user->getFriendlyName(); ?></div>
              <div class="idea-date pulsar-muted"><?php echo Html::convDate($idea['date']); ?></div>
            </div>
          </div>
          <span class="badge <?php echo $statusClass; ?>">
            <i class="fa-solid fa-circle-info"></i> 
            <?php echo Ticket::getStatus($idea['status']); ?>
          </span>
        </div>
        
        <div class="idea-card-body">
          <h3 class="idea-title"><?php echo htmlspecialchars($idea['name']); ?></h3>
          <p class="idea-excerpt"><?php echo htmlspecialchars(substr($idea['content'], 0, 150)); ?>...</p>
        </div>
        
        <div class="idea-card-footer">
          <div class="idea-stats">
            <button class="stat-btn like-btn <?php echo $idea['has_liked'] ? 'liked' : ''; ?>" 
                    data-ticket="<?php echo $idea['id']; ?>"
                    data-liked="<?php echo $idea['has_liked'] ? '1' : '0'; ?>">
              <i class="fa-solid fa-heart"></i>
              <span class="like-count"><?php echo $idea['likes_count']; ?></span>
            </button>
            <span class="stat-item">
              <i class="fa-solid fa-comment"></i>
              <span><?php echo $idea['comments_count']; ?></span>
            </span>
          </div>
          <a href="idea.php?id=<?php echo $idea['id']; ?>" class="btn-outline btn-small">
            <i class="fa-solid fa-arrow-right"></i> Ver detalhes
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="card-u text-center empty-state">
        <div class="empty">
          <div class="empty-icon">
            <i class="fa-solid fa-lightbulb" style="font-size: 4rem; color: #ccc;"></i>
          </div>
          <p class="empty-title">Nenhuma ideia encontrada</p>
          <p class="empty-subtitle pulsar-muted">Seja o primeiro a compartilhar uma ideia!</p>
          <div class="empty-action">
            <a href="<?php echo htmlspecialchars($idea_form_url); ?>" class="btn-u primary">
              <i class="fa-solid fa-plus"></i> Enviar primeira ideia
            </a>
          </div>
        </div>
      </div>
    <?php endif; ?>
    
    <div class="no-results-message" style="display: none;">
      <div class="card-u text-center">
        <div class="empty">
          <div class="empty-icon">
            <i class="fa-solid fa-search" style="font-size: 3rem; color: #ccc;"></i>
          </div>
          <p class="empty-title">Nenhuma ideia corresponde aos filtros</p>
          <p class="empty-subtitle pulsar-muted">Tente ajustar seus critérios de busca.</p>
        </div>
      </div>
    </div>
    
  </main>
  
</div>

<style>
  .pulsar-wrap *{box-sizing:border-box;margin:0;padding:0}
  .pulsar-muted{color:#667085}
  .pulsar-wrap{padding:16px;max-width:1400px;margin:0 auto}

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
    overflow-x:auto;
  }
  .topnav-item{
    display:inline-flex;gap:8px;align-items:center;
    padding:10px 16px;border:1px solid var(--u-border);border-radius:10px;background:#fff;cursor:pointer;
    font-weight:600; color:var(--u-dark);transition:all 0.2s ease;text-decoration:none;
    white-space:nowrap;
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

  .pulsar-filters-container{
    margin-bottom:20px;
  }
  .pulsar-search-inline{
    position:relative;
    margin-bottom:16px;
  }
  .pulsar-search-inline input{
    width:100%;
    padding:12px 16px 12px 40px;
    border:1px solid var(--u-border);
    border-radius:10px;
    background:#fff;
    font-size:14px;
    transition:all 0.2s ease;
  }
  .pulsar-search-inline input:focus{
    outline:none;
    border-color:var(--u-primary);
    box-shadow:0 0 0 2px rgba(0,153,93,0.1);
  }
  .search-clear{
    position:absolute;
    right:12px;
    top:50%;
    transform:translateY(-50%);
    background:none;
    border:none;
    color:#9aa3af;
    cursor:pointer;
    opacity:0;
    transition:opacity 0.2s ease;
  }
  .pulsar-search-inline input:not(:placeholder-shown) + .search-clear{
    opacity:1;
  }

  .pulsar-filters{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:center;
    margin-bottom:12px;
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
    white-space:nowrap;
  }
  .filter-select{
    padding:8px 12px;
    border:1px solid var(--u-border);
    border-radius:8px;
    background:#fff;
    font-size:14px;
    min-width:160px;
  }

  .ideas-counter{
    font-size:14px;
    color:var(--u-dark);
    font-weight:600;
  }
  .ideas-counter span{
    color:var(--u-primary);
    font-size:18px;
  }

  .pulsar-ideas-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(380px, 1fr));
    gap:20px;
  }
  @media (max-width:768px){
    .pulsar-ideas-grid{
      grid-template-columns:1fr;
    }
  }

  .idea-card{
    transition:transform 0.2s ease, box-shadow 0.2s ease;
    display:flex;
    flex-direction:column;
  }
  .idea-card:hover{
    transform:translateY(-4px);
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
  }
  
  .idea-card-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    margin-bottom:12px;
  }
  .idea-author{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .author-avatar{
    width:40px;
    height:40px;
    border-radius:50%;
    background:var(--u-primary);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    font-size:14px;
    flex-shrink:0;
  }
  .author-name{
    font-weight:600;
    color:var(--u-dark);
    font-size:14px;
  }
  .idea-date{
    font-size:12px;
  }

  .idea-card-body{
    flex-grow:1;
    margin-bottom:16px;
  }
  .idea-title{
    margin:0 0 8px 0;
    color:var(--u-dark);
    font-size:16px;
    font-weight:700;
    line-height:1.4;
  }
  .idea-excerpt{
    color:#64748b;
    font-size:14px;
    line-height:1.5;
    margin:0;
  }

  .idea-card-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding-top:12px;
    border-top:1px solid #f1f5f9;
  }
  .idea-stats{
    display:flex;
    gap:16px;
    align-items:center;
  }
  .stat-item{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:14px;
    color:#64748b;
  }
  .stat-btn{
    display:flex;
    align-items:center;
    gap:6px;
    background:none;
    border:1px solid transparent;
    padding:6px 10px;
    border-radius:8px;
    cursor:pointer;
    transition:all 0.2s ease;
    font-size:14px;
    color:#64748b;
  }
  .stat-btn:hover{
    background:#f8fafc;
    border-color:var(--u-border);
  }
  .like-btn.liked{
    color:var(--u-primary);
    border-color:var(--u-primary);
    background:rgba(0,153,93,0.05);
  }
  .like-btn.liked i{
    color:#e11d48;
  }

  .btn-outline{
    background:transparent;
    border:1px solid var(--u-primary);
    color:var(--u-primary);
    border-radius:8px;
    padding:8px 14px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.2s ease;
    font-size:13px;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:6px;
  }
  .btn-outline:hover{
    background:rgba(0,153,93,0.05);
  }
  .btn-small{
    padding:6px 12px;
    font-size:12px;
  }

  .badge{
    border-radius:999px;
    padding:4px 10px;
    font-size:12px;
    font-weight:600;
    display:inline-flex;
    align-items:center;
    gap:6px;
    white-space:nowrap;
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
  .badge.implemented{
    background:#ecfccb;
    color:var(--u-implemented);
  }

  .empty-state{
    grid-column:1 / -1;
  }
  .empty {
    text-align: center;
    padding: 3rem 2rem;
  }
  .empty-icon {
    margin-bottom: 1rem;
  }
  .empty-title {
    color: #495057;
    margin-bottom: 0.5rem;
    font-size:20px;
    font-weight:600;
  }
  .empty-subtitle {
    margin-bottom: 1.5rem;
  }

  .no-results-message{
    grid-column:1 / -1;
  }
</style>

<script src="<?php echo $CFG_GLPI['root_doc']; ?>/plugins/agilizepulsar/js/pulsar.js"></script>

<script>
(function() {
  const searchInput = document.getElementById('buscar-ideias');
  const statusFilter = document.getElementById('filter-status');
  const campaignFilter = document.getElementById('filter-campaign');
  const orderFilter = document.getElementById('filter-order');
  const clearFiltersBtn = document.getElementById('btn-clear-filters');
  const searchClearBtn = document.querySelector('.search-clear');
  const ideasCount = document.getElementById('ideas-count');
  const noResultsMsg = document.querySelector('.no-results-message');
  
  let allCards = Array.from(document.querySelectorAll('.idea-card'));
  
  // Função principal de filtro
  function applyFilters() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const statusValue = statusFilter.value;
    const campaignValue = campaignFilter.value;
    const orderValue = orderFilter.value;
    
    let visibleCards = allCards.filter(card => {
      // Filtro de busca
      if (searchTerm) {
        const title = card.dataset.title || '';
        const content = card.dataset.content || '';
        if (!title.includes(searchTerm) && !content.includes(searchTerm)) {
          return false;
        }
      }
      
      // Filtro de status
      if (statusValue && card.dataset.status !== statusValue) {
        return false;
      }
      
      // Filtro de campanha (implementar quando necessário)
      if (campaignValue && card.dataset.campaign !== campaignValue) {
        return false;
      }
      
      return true;
    });
    
    // Ordenação
    if (orderValue === 'recent') {
      visibleCards.sort((a, b) => parseInt(b.dataset.date) - parseInt(a.dataset.date));
    } else if (orderValue === 'oldest') {
      visibleCards.sort((a, b) => parseInt(a.dataset.date) - parseInt(b.dataset.date));
    } else if (orderValue === 'popular') {
      visibleCards.sort((a, b) => parseInt(b.dataset.likes) - parseInt(a.dataset.likes));
    } else if (orderValue === 'commented') {
      visibleCards.sort((a, b) => parseInt(b.dataset.comments) - parseInt(a.dataset.comments));
    }
    
    // Mostrar/ocultar cards
    allCards.forEach(card => card.style.display = 'none');
    visibleCards.forEach(card => card.style.display = 'flex');
    
    // Atualizar contador
    ideasCount.textContent = visibleCards.length;
    
    // Mostrar mensagem de "sem resultados"
    if (visibleCards.length === 0 && allCards.length > 0) {
      noResultsMsg.style.display = 'block';
    } else {
      noResultsMsg.style.display = 'none';
    }
    
    // Re-ordenar no DOM
    const grid = document.querySelector('.pulsar-ideas-grid');
    visibleCards.forEach(card => grid.appendChild(card));
  }
  
  // Event listeners
  searchInput.addEventListener('input', applyFilters);
  statusFilter.addEventListener('change', applyFilters);
  campaignFilter.addEventListener('change', applyFilters);
  orderFilter.addEventListener('change', applyFilters);
  
  searchClearBtn.addEventListener('click', function() {
    searchInput.value = '';
    applyFilters();
    searchInput.focus();
  });
  
  clearFiltersBtn.addEventListener('click', function() {
    searchInput.value = '';
    statusFilter.value = '';
    campaignFilter.value = '';
    orderFilter.value = 'recent';
    applyFilters();
  });
  
  // Sistema de curtidas
  document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const ticketId = this.dataset.ticket;
      const likeCountSpan = this.querySelector('.like-count');
      const isLiked = this.classList.contains('liked');
      
      PulsarLike.toggle(ticketId, (response) => {
        if (response.success) {
          likeCountSpan.textContent = response.count;
          this.dataset.liked = response.liked ? '1' : '0';
          
          if (response.liked) {
            this.classList.add('liked');
          } else {
            this.classList.remove('liked');
          }
          
          // Atualizar dataset para ordenação
          this.closest('.idea-card').dataset.likes = response.count;
        }
      });
    });
  });
})();
</script>

<?php
Html::footer();
?>