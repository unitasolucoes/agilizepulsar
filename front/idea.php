<?php

include('../../../inc/includes.php');
Session::checkLoginUser();

$tickets_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!PluginAgilizepulsarTicket::isIdea($tickets_id)) {
    Html::displayErrorAndDie(__('Access denied'));
}

$ticket = new Ticket();
if (!$ticket->getFromDB($tickets_id)) {
    Html::displayErrorAndDie(__('Item not found'));
}

$idea = PluginAgilizepulsarTicket::enrichTicketData($ticket->fields);
$form_answers = PluginAgilizepulsarTicket::getFormAnswers($tickets_id);
$coauthors = PluginAgilizepulsarTicket::getCoauthors($tickets_id);
$comments = PluginAgilizepulsarComment::getByTicket($tickets_id);
$approvals = PluginAgilizepulsarApproval::getByTicket($tickets_id);

$title = __('Pulsar – ') . $idea['name'];
if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
   Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}

$user = new User();
$user->getFromDB($idea['users_id_recipient']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

<div id="pulsar-demo" class="pulsar-wrap">

  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1>Pulsar</h1>
      <p class="pulsar-muted">Detalhes da Ideia</p>
    </div>
    <div class="pulsar-actions">
      <a href="feed.php" class="btn-u ghost"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
    </div>
  </section>

  <nav class="pulsar-topnav card-u">
    <a class="topnav-item" href="feed.php">
      <i class="fa-solid fa-bolt"></i><span>Feed</span>
    </a>
    <a class="topnav-item is-active" href="#">
      <i class="fa-solid fa-lightbulb"></i><span>Ideia</span>
    </a>
    <a class="topnav-item" href="my_ideas.php">
      <i class="fa-solid fa-lightbulb"></i><span>Minhas Ideias</span>
    </a>
    <a class="topnav-item" href="dashboard.php">
      <i class="fa-solid fa-chart-bar"></i><span>Dashboard</span>
    </a>
    <a class="topnav-item" href="settings.php">
      <i class="fa-solid fa-gear"></i><span>Configurações</span>
    </a>
  </nav>

  <div class="idea-detail-container">
    <div class="idea-header card-u">
      <div class="idea-status-badge <?php echo ($idea['status'] == Ticket::SOLVED ? 'approved' : 'analysis'); ?>">
        <i class="fa-solid fa-circle-check"></i> <?php echo Ticket::getStatus($idea['status']); ?>
      </div>

      <div class="idea-title-section">
        <h1><?php echo htmlspecialchars($idea['name']); ?></h1>
        <div class="idea-meta">
          <div class="author-info">
            <span class="author-avatar"><?php echo strtoupper(substr($user->fields['realname'], 0, 2)); ?></span>
            <div>
              <strong><?php echo $user->getFriendlyName(); ?></strong>
              <div class="pulsar-muted large">Enviada em <?php echo Html::convDate($idea['date']); ?></div>
            </div>
          </div>
          <div class="idea-stats">
            <span class="stat-item"><i class="fa-solid fa-heart"></i> <span id="likes-count"><?php echo $idea['likes_count']; ?></span></span>
            <span class="stat-item"><i class="fa-solid fa-comment"></i> <?php echo $idea['comments_count']; ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="idea-body">
      <div class="idea-main-content">
        <section class="idea-section card-u idea-desc">
          <div class="section-title">
            <h2><i class="fa-solid fa-align-left"></i> Descrição</h2>
          </div>

          <div class="section-content">
            <?php if (isset($form_answers['Descrição'])): ?>
              <div><?php echo $form_answers['Descrição']; ?></div>
            <?php else: ?>
              <div><?php echo $idea['content']; ?></div>
            <?php endif; ?>

            <?php if (isset($form_answers['Benefícios'])): ?>
              <h3>Benefícios</h3>
              <div><?php echo $form_answers['Benefícios']; ?></div>
            <?php endif; ?>

            <?php if (isset($form_answers['Implementação'])): ?>
              <h3>Implementação</h3>
              <div><?php echo $form_answers['Implementação']; ?></div>
            <?php endif; ?>
          </div>
        </section>

        <section class="idea-section card-u">
          <h2><i class="fa-solid fa-comments"></i> Comentários</h2>
          <div class="section-content">
            <div class="comment-form">
              <textarea id="comment-text" placeholder="Adicione seu comentário..." rows="3"></textarea>
              <button class="btn-u primary" id="btn-add-comment">Comentar</button>
            </div>

            <div class="comments-list" id="comments-list">
              <?php foreach ($comments as $comment): ?>
              <div class="comment-line">
                <span class="comment-avatar"><?php echo strtoupper(substr($comment['user_name'], 0, 2)); ?></span>
                <div class="comment-bubble">
                  <div class="comment-head">
                    <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong>
                    <span class="pulsar-muted large"><?php echo Html::convDateTime($comment['date_creation']); ?></span>
                  </div>
                  <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
      </div>

      <aside class="idea-sidebar">
        <section class="sidebar-section card-u">
          <h2><i class="fa-solid fa-rocket"></i> Campanha</h2>
          <div class="section-content">
            <div class="related-campaign">
              <h3><?php echo isset($form_answers['Campanha']) ? htmlspecialchars($form_answers['Campanha']) : 'Sem campanha'; ?></h3>
            </div>
          </div>
        </section>

        <section class="sidebar-section card-u">
          <h2><i class="fa-solid fa-tasks"></i> Status</h2>
          <div class="section-content">
            <div class="status-info">
              <div class="status-item">
                <i class="fa-solid fa-check-circle" style="color:#10b981"></i>
                <div>
                  <strong><?php echo Ticket::getStatus($idea['status']); ?></strong>
                  <div class="pulsar-muted small"><?php echo Html::convDate($idea['date_mod']); ?></div>
                </div>
              </div>
            </div>

            <div class="idea-actions">
              <button class="btn-u primary full-width" id="btn-like" data-ticket="<?php echo $tickets_id; ?>" data-liked="<?php echo $idea['has_liked'] ? '1' : '0'; ?>">
                <i class="fa-solid fa-heart"></i> <?php echo $idea['has_liked'] ? 'Descurtir' : 'Curtir'; ?>
              </button>
              <button class="btn-u ghost full-width" onclick="navigator.clipboard.writeText(window.location.href)">
                <i class="fa-solid fa-share-nodes"></i> Compartilhar
              </button>
            </div>
          </div>
        </section>
      </aside>
    </div>
  </div>
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
  .btn-u.small{padding:8px 12px;font-size:.875rem}
  .full-width{width:100%}

  .pulsar-topnav{display:flex;gap:8px;align-items:center;margin-bottom:16px;background:linear-gradient(180deg,#fff,#fbfcff);padding:12px 16px}
  .topnav-item{display:inline-flex;gap:8px;align-items:center;padding:10px 16px;border:1px solid var(--u-border);border-radius:10px;background:#fff;text-decoration:none;color:var(--u-dark);font-weight:600}
  .topnav-item:hover{background:var(--u-chip);border-color:var(--u-primary)}
  .topnav-item.is-active{background:var(--u-primary);color:#fff;border-color:var(--u-primary)}
  .topnav-item.is-active i{color:#fff}

  .idea-detail-container{width:100%}

  .idea-header{padding:24px;background:linear-gradient(180deg,#fff,#f8fafc);border-left:4px solid var(--u-success)}
  .idea-status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;font-weight:700;margin-bottom:12px}
  .idea-status-badge.approved{background:#d1fae5;color:#065f46}
  .idea-status-badge.analysis{background:#e6efff;color:#1e40af}
  .idea-header h1{margin:8px 0 12px 0;color:var(--u-dark);font-size:22px}
  .idea-meta{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
  .author-info{display:flex;align-items:center;gap:12px}
  .author-avatar{width:40px;height:40px;border-radius:50%;background:var(--u-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700}

  .idea-stats{display:flex;gap:14px}
  .stat-item{display:flex;align-items:center;gap:6px;color:#64748b}

  .idea-body{display:grid;grid-template-columns:1fr 340px;gap:16px}
  @media (max-width:1100px){.idea-body{grid-template-columns:1fr}}

  .idea-section{margin-bottom:16px}
  .idea-section h2{color:var(--u-dark);font-size:18px;margin:0;display:flex;align-items:center;gap:10px}
  .idea-section h2 i{color:var(--u-primary)}
  .section-content{padding-top:12px;line-height:1.6}

  .idea-desc .section-title{display:flex;justify-content:space-between;align-items:center}
  .idea-desc h3{margin:14px 0 8px 0;color:#0f172a;font-size:16px}

  .comment-form{display:flex;gap:8px;margin-bottom:12px;flex-direction:column;}
  .comment-form textarea{flex:1;border:1px solid var(--u-border);border-radius:10px;padding:10px;min-height:90px;resize:vertical}
  .comments-list{display:flex;flex-direction:column;gap:10px}
  .comment-line{display:flex;gap:10px}
  .comment-avatar{width:36px;height:36px;border-radius:50%;background:#e2e8f0;color:#334155;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0}
  .comment-bubble{background:#f9fafb;border:1px solid var(--u-border);border-radius:12px;padding:10px;flex:1}
  .comment-head{display:flex;align-items:center;gap:8px;margin-bottom:4px}

  .sidebar-section h2{color:var(--u-dark);font-size:18px;margin:0;display:flex;align-items:center;gap:10px}
  .sidebar-section h2 i{color:var(--u-primary)}
  .status-info{display:grid;gap:12px;margin-top:8px}
  .status-item{display:flex;align-items:center;gap:10px}

  .related-campaign h3{margin:0 0 6px 0;color:var(--u-dark)}
  .idea-actions{display:flex;flex-direction:column;gap:8px;margin-top:12px;}
</style>

<script src="<?php echo $CFG_GLPI['root_doc']; ?>/plugins/agilizepulsar/js/pulsar.js"></script>

<script>
(function() {
  const btnLike = document.getElementById('btn-like');
  const likeCount = document.getElementById('likes-count');
  const ticketId = btnLike.dataset.ticket;
  let liked = btnLike.dataset.liked === '1';

  btnLike.addEventListener('click', function() {
    PulsarLike.toggle(ticketId, function(response) {
      if (response.success) {
        liked = response.liked;
        likeCount.textContent = response.count;
        btnLike.innerHTML = liked ? '<i class="fa-solid fa-heart"></i> Descurtir' : '<i class="fa-solid fa-heart"></i> Curtir';
      }
    });
  });

  const btnComment = document.getElementById('btn-add-comment');
  const textarea = document.getElementById('comment-text');
  const commentsList = document.getElementById('comments-list');

  btnComment.addEventListener('click', function() {
    const content = textarea.value.trim();
    if (!content) return;

    PulsarComment.add(ticketId, content, function(response) {
      if (response.success) {
        const comment = document.createElement('div');
        comment.className = 'comment-line';
        comment.innerHTML = `
          <span class="comment-avatar">${response.comment.user_initials}</span>
          <div class="comment-bubble">
            <div class="comment-head">
              <strong>${response.comment.user_name}</strong>
              <span class="pulsar-muted large">${response.comment.date}</span>
            </div>
            <p>${response.comment.content}</p>
          </div>
        `;
        commentsList.prepend(comment);
        textarea.value = '';
      }
    });
  });
})();
</script>

<?php
Html::footer();
?>