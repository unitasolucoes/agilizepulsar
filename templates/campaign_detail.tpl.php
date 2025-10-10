<?php
/**
 * Template de detalhes da campanha - DEFINITIVO CORRIGIDO
 * Descrição renderizada + Iniciais corretas + Cores verde
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

$plugin_web = Plugin::getWebDir('agilizepulsar');
$ideas = $specific_data['ideas'] ?? [];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="<?php echo $plugin_web; ?>/css/pulsar.css"/>

<div id="pulsar-campaign-detail" class="pulsar-wrap">

  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1><?php echo htmlspecialchars($config['menu_name']); ?></h1>
      <p class="pulsar-muted">Campanha de coleta de ideias</p>
    </div>
    <div class="pulsar-actions">
      <a href="<?php echo htmlspecialchars($config['idea_form_url']); ?>" class="btn-u primary">
        <i class="fa-solid fa-lightbulb"></i> Participar com uma Ideia
      </a>
      <a href="feed.php" class="btn-u ghost"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
    </div>
  </section>

  <nav class="pulsar-topnav card-u">
    <a class="topnav-item" href="feed.php">
      <i class="fa-solid fa-bolt"></i>
      <span>Feed</span>
    </a>
    <a class="topnav-item is-active" href="campaign.php?id=<?php echo $tickets_id; ?>">
      <i class="fa-solid fa-flag"></i>
      <span>Campanha</span>
    </a>
    <a class="topnav-item" href="dashboard.php">
      <i class="fa-solid fa-chart-bar"></i>
      <span>Dashboard</span>
    </a>
  </nav>

  <div class="detail-grid">
    
    <!-- Coluna Principal -->
    <div class="detail-main">
      
      <!-- Card da Campanha -->
      <article class="card-u campaign-detail-card">
        <header class="campaign-header">
          <div class="campaign-icon">
            <i class="fa-solid fa-flag"></i>
          </div>
          <div class="campaign-info">
            <h2 class="campaign-title"><?php echo htmlspecialchars($data['name']); ?></h2>
            <p class="campaign-meta">
              Prazo: <strong><?php echo Html::convDateTime($data['time_to_resolve']); ?></strong>
            </p>
          </div>
        </header>

        <div class="campaign-content">
          <?php 
          // Renderizar o conteúdo HTML do ticket corretamente
          $content = $data['content'];
          
          // Decodificar entidades HTML
          $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
          
          // Exibir o HTML
          echo $content; 
          ?>
        </div>

        <footer class="campaign-footer">
          <div class="campaign-stats">
            <div class="stat-box">
              <i class="fa-solid fa-lightbulb"></i>
              <div>
                <strong><?php echo count($ideas); ?></strong>
                <span>Ideias</span>
              </div>
            </div>
            <div class="stat-box">
              <i class="fa-solid fa-heart"></i>
              <div>
                <strong><?php echo $data['likes_count']; ?></strong>
                <span>Curtidas</span>
              </div>
            </div>
            <div class="stat-box">
              <i class="fa-solid fa-eye"></i>
              <div>
                <strong><?php echo $data['views_count']; ?></strong>
                <span>Visualizações</span>
              </div>
            </div>
          </div>
        </footer>
      </article>

      <!-- Ideias Vinculadas -->
      <section class="card-u ideas-section">
        <header class="section-header">
          <h2><i class="fa-solid fa-lightbulb"></i> Ideias Vinculadas (<?php echo count($ideas); ?>)</h2>
          <a href="<?php echo htmlspecialchars($config['idea_form_url']); ?>" class="btn-outline btn-small">
            <i class="fa-solid fa-plus"></i> Adicionar Ideia
          </a>
        </header>

        <?php if (count($ideas) > 0): ?>
          <div class="ideas-grid">
            <?php foreach ($ideas as $idea): 
              $idea_user = new User();
              $idea_user->getFromDB($idea['users_id_recipient']);
            ?>
            <article class="idea-card-mini">
              <div class="idea-card-header">
                <h3><?php echo htmlspecialchars($idea['name']); ?></h3>
                <span class="badge <?php 
                  $status = $idea['status'];
                  if ($status == Ticket::SOLVED) echo 'success';
                  elseif ($status == Ticket::CLOSED) echo 'implemented';
                  elseif ($status == Ticket::ASSIGNED) echo 'info';
                  else echo 'warn';
                ?>">
                  <?php echo Ticket::getStatus($idea['status']); ?>
                </span>
              </div>
              <p class="idea-author">por <?php echo $idea_user->getFriendlyName(); ?></p>
              <div class="idea-card-footer">
                <div class="idea-stats-mini">
                  <span><i class="fa-solid fa-heart"></i> <?php echo $idea['likes_count']; ?></span>
                  <span><i class="fa-solid fa-comment"></i> <?php echo $idea['comments_count']; ?></span>
                </div>
                <a href="idea.php?id=<?php echo $idea['id']; ?>" class="btn-outline btn-xs">
                  <i class="fa-solid fa-arrow-right"></i>
                </a>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-solid fa-lightbulb"></i>
            <p>Nenhuma ideia vinculada ainda. Seja o primeiro a participar!</p>
            <a href="<?php echo htmlspecialchars($config['idea_form_url']); ?>" class="btn-u primary">
              <i class="fa-solid fa-plus"></i> Enviar Primeira Ideia
            </a>
          </div>
        <?php endif; ?>
      </section>

      <!-- Comentários da Campanha -->
      <section class="card-u comments-section" id="comments-section">
        <header class="comments-header">
          <h2><i class="fa-solid fa-comments"></i> Comentários (<span id="total-comments"><?php echo count($comments); ?></span>)</h2>
        </header>

        <!-- Formulário de Novo Comentário -->
        <div class="comment-form-wrapper">
          <form id="comment-form" class="comment-form">
            <div class="comment-input-group">
              <div class="current-user-avatar"><?php 
                $current_user = new User();
                $current_user->getFromDB(Session::getLoginUserID());
                $current_name = $current_user->fields['realname'] ?? $current_user->fields['name'] ?? 'Usuário';
                
                // Pegar iniciais corretas
                $name_parts = explode(' ', trim($current_name));
                if (count($name_parts) >= 2) {
                    $current_initials = strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1));
                } else {
                    $current_initials = strtoupper(substr($current_name, 0, 2));
                }
                
                echo htmlspecialchars($current_initials);
              ?></div>
              <textarea 
                id="comment-content" 
                name="content" 
                placeholder="Adicione seu comentário..." 
                rows="3"
                required></textarea>
            </div>
            <div class="comment-form-actions">
              <button type="submit" class="btn-u primary">
                <i class="fa-solid fa-paper-plane"></i> Enviar Comentário
              </button>
            </div>
          </form>
        </div>

        <!-- Lista de Comentários -->
        <div class="comments-list" id="comments-list">
          <?php if (count($comments) > 0): ?>
            <?php foreach ($comments as $index => $comment): 
              $comment_user_name = $comment['user_name'] ?? 'Usuário';
              
              // Pegar iniciais corretas do nome completo
              $name_parts = explode(' ', $comment_user_name);
              if (count($name_parts) >= 2) {
                  $comment_user_initials = strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1));
              } else {
                  $comment_user_initials = strtoupper(substr($comment_user_name, 0, 2));
              }
              
              $is_hidden = $index >= 5 ? 'comment-hidden' : '';
            ?>
            <article class="comment-item <?php echo $is_hidden; ?>" data-comment-index="<?php echo $index; ?>" data-comment-id="<?php echo $comment['id']; ?>">
              <div class="comment-avatar"><?php echo htmlspecialchars($comment_user_initials); ?></div>
              <div class="comment-body">
                <div class="comment-header">
                  <strong class="comment-author"><?php echo htmlspecialchars($comment_user_name); ?></strong>
                  <span class="comment-date"><?php echo Html::convDateTime($comment['date_creation']); ?></span>
                </div>
                <div class="comment-content">
                  <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                </div>
              </div>
            </article>
            <?php endforeach; ?>

            <?php if (count($comments) > 5): ?>
            <div class="comments-toggle">
              <button id="toggle-comments-btn" class="btn-outline btn-toggle">
                <i class="fa-solid fa-chevron-down"></i>
                <span class="toggle-text">Ver mais <?php echo count($comments) - 5; ?> comentários</span>
              </button>
            </div>
            <?php endif; ?>

          <?php else: ?>
            <div class="empty-comments">
              <i class="fa-solid fa-comment-slash"></i>
              <p>Nenhum comentário ainda. Seja o primeiro a comentar!</p>
            </div>
          <?php endif; ?>
        </div>
      </section>

    </div>

    <!-- Sidebar -->
    <aside class="detail-sidebar">
      
      <!-- Status -->
      <div class="card-u sidebar-card">
        <h3><i class="fa-solid fa-info-circle"></i> Status</h3>
        <div class="status-info">
          <span class="badge-large <?php 
            $status = $data['status'];
            if ($status == Ticket::INCOMING || $status == Ticket::ASSIGNED) echo 'info';
            elseif ($status == Ticket::CLOSED) echo 'success';
            else echo 'warn';
          ?>">
            <?php echo Ticket::getStatus($data['status']); ?>
          </span>
        </div>
      </div>

      <!-- Prazo -->
      <div class="card-u sidebar-card">
        <h3><i class="fa-solid fa-calendar"></i> Prazo</h3>
        <div class="deadline-info">
          <p class="deadline-date">
            <?php echo Html::convDateTime($data['time_to_resolve']); ?>
          </p>
          <?php 
          $now = time();
          $deadline = strtotime($data['time_to_resolve']);
          $days_left = floor(($deadline - $now) / 86400);
          ?>
          <?php if ($days_left > 0): ?>
            <p class="deadline-countdown">
              <i class="fa-solid fa-clock"></i>
              Faltam <?php echo $days_left; ?> dias
            </p>
          <?php elseif ($days_left == 0): ?>
            <p class="deadline-countdown today">
              <i class="fa-solid fa-exclamation-triangle"></i>
              Último dia!
            </p>
          <?php else: ?>
            <p class="deadline-countdown expired">
              <i class="fa-solid fa-times-circle"></i>
              Prazo encerrado
            </p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Estatísticas -->
      <div class="card-u sidebar-card">
        <h3><i class="fa-solid fa-chart-bar"></i> Estatísticas</h3>
        <div class="stats-list">
          <div class="stat-row">
            <span class="stat-label"><i class="fa-solid fa-lightbulb"></i> Total de Ideias</span>
            <span class="stat-value"><?php echo count($ideas); ?></span>
          </div>
          <div class="stat-row">
            <span class="stat-label"><i class="fa-solid fa-heart"></i> Curtidas</span>
            <span class="stat-value"><?php echo $data['likes_count']; ?></span>
          </div>
          <div class="stat-row">
            <span class="stat-label"><i class="fa-solid fa-comment"></i> Comentários</span>
            <span class="stat-value"><?php echo count($comments); ?></span>
          </div>
          <div class="stat-row">
            <span class="stat-label"><i class="fa-solid fa-eye"></i> Visualizações</span>
            <span class="stat-value"><?php echo $data['views_count']; ?></span>
          </div>
        </div>
      </div>

      <!-- Ações Administrativas -->
      <?php if ($can_admin): ?>
      <div class="card-u sidebar-card admin-actions">
        <h3><i class="fa-solid fa-cog"></i> Ações</h3>
        <a href="<?php echo $CFG_GLPI['root_doc']; ?>/front/ticket.form.php?id=<?php echo $tickets_id; ?>" 
           class="btn-u ghost full-width">
          <i class="fa-solid fa-edit"></i> Editar no GLPI
        </a>
      </div>
      <?php endif; ?>

    </aside>

  </div>

</div>


<script src="<?php echo $CFG_GLPI['root_doc']; ?>/plugins/agilizepulsar/js/pulsar.js"></script>

<script>
(function() {
  'use strict';

  const toggleBtn = document.getElementById('toggle-comments-btn');
  if (toggleBtn) {
    let expanded = false;

    toggleBtn.addEventListener('click', function(e) {
      e.preventDefault();
      expanded = !expanded;
      
      const allComments = document.querySelectorAll('.comment-item');
      const toggleText = this.querySelector('.toggle-text');
      
      allComments.forEach((comment, index) => {
        if (index >= 5) {
          if (expanded) {
            comment.classList.remove('comment-hidden');
          } else {
            comment.classList.add('comment-hidden');
          }
        }
      });

      const hiddenCount = Array.from(allComments).filter((c, i) => i >= 5).length;
      
      if (expanded) {
        toggleText.textContent = 'Ver menos comentários';
        this.classList.add('expanded');
      } else {
        toggleText.textContent = 'Ver mais ' + hiddenCount + ' comentários';
        this.classList.remove('expanded');
        
        const commentsSection = document.getElementById('comments-section');
        if (commentsSection) {
          commentsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  }

  const commentForm = document.getElementById('comment-form');
  if (commentForm) {
    commentForm.addEventListener('submit', function(e) {
      e.preventDefault();

      const contentInput = document.getElementById('comment-content');
      const content = contentInput.value.trim();

      if (!content) {
        alert('Por favor, escreva um comentário.');
        return;
      }

      const ticketId = <?php echo $tickets_id; ?>;
      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

      PulsarComment.add(ticketId, content, (response) => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Enviar Comentário';

        if (response.success) {
          const commentsList = document.getElementById('comments-list');
          const emptyComments = commentsList.querySelector('.empty-comments');
          
          if (emptyComments) {
            emptyComments.remove();
          }

          const newComment = document.createElement('article');
          newComment.className = 'comment-item comment-new';
          newComment.innerHTML = `
            <div class="comment-avatar">${response.comment.user_initials}</div>
            <div class="comment-body">
              <div class="comment-header">
                <strong class="comment-author">${response.comment.user_name}</strong>
                <span class="comment-date">${response.comment.date}</span>
              </div>
              <div class="comment-content">${response.comment.content}</div>
            </div>
          `;

          const firstComment = commentsList.querySelector('.comment-item');
          if (firstComment) {
            commentsList.insertBefore(newComment, firstComment);
          } else {
            commentsList.appendChild(newComment);
          }

          const commentCount = document.getElementById('total-comments');
          if (commentCount) {
            commentCount.textContent = parseInt(commentCount.textContent) + 1;
          }

          contentInput.value = '';
          contentInput.focus();
          newComment.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
          alert(response.message || 'Erro ao adicionar comentário');
        }
      });
    });
  }
})();
</script>

<?php
// Fim do template