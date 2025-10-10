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
$plugin_web = Plugin::getWebDir('agilizepulsar');
$idea_category_id = (int)$config['idea_category_id'];
$campaign_category_id = (int)$config['campaign_category_id'];

// Função auxiliar para executar queries simples
function simpleQuery($sql) {
    global $DB;
    try {
        $result = $DB->query($sql);
        if ($result) {
            return $DB->fetchAssoc($result);
        }
    } catch (Exception $e) {
        // Silenciar erros e retornar valores padrão
    }
    return ['count' => 0, 'total' => 0];
}

function simpleQueryAll($sql) {
    global $DB;
    try {
        $result = $DB->query($sql);
        $data = [];
        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    } catch (Exception $e) {
        // Silenciar erros e retornar array vazio
    }
    return [];
}

// Contar campanhas ativas/inativas de forma simples
$active_statuses = implode(',', [Ticket::INCOMING, Ticket::ASSIGNED, Ticket::PLANNED, Ticket::WAITING]);

$campaigns_active_sql = "SELECT COUNT(*) as count FROM glpi_tickets WHERE itilcategories_id = $campaign_category_id AND status IN ($active_statuses)";
$campaigns_active_result = simpleQuery($campaigns_active_sql);
$total_campaigns_active = (int)$campaigns_active_result['count'];

$campaigns_total_sql = "SELECT COUNT(*) as count FROM glpi_tickets WHERE itilcategories_id = $campaign_category_id";
$campaigns_total_result = simpleQuery($campaigns_total_sql);
$total_campaigns_all = (int)$campaigns_total_result['count'];

$total_campaigns_closed = $total_campaigns_all - $total_campaigns_active;

// Contar ideias
$ideas_total_sql = "SELECT COUNT(*) as count FROM glpi_tickets WHERE itilcategories_id = $idea_category_id";
$ideas_total_result = simpleQuery($ideas_total_sql);
$total_ideas = (int)$ideas_total_result['count'];

$ideas_approved_sql = "SELECT COUNT(*) as count FROM glpi_tickets WHERE itilcategories_id = $idea_category_id AND status = " . Ticket::SOLVED;
$ideas_approved_result = simpleQuery($ideas_approved_sql);
$ideas_approved = (int)$ideas_approved_result['count'];

$ideas_implemented_sql = "SELECT COUNT(*) as count FROM glpi_tickets WHERE itilcategories_id = $idea_category_id AND status = " . Ticket::CLOSED;
$ideas_implemented_result = simpleQuery($ideas_implemented_sql);
$ideas_implemented = (int)$ideas_implemented_result['count'];

$conversion_rate = $total_campaigns_all > 0 ? round($total_ideas / $total_campaigns_all, 1) : 0;

$period = $_GET['period'] ?? 'year';
$category_filter = $_GET['category'] ?? 'ideas';
$start_date_param = $_GET['start_date'] ?? '';
$end_date_param = $_GET['end_date'] ?? '';

$allowed_periods = ['month', 'quarter', 'year', 'custom'];
if (!in_array($period, $allowed_periods, true)) {
    $period = 'year';
}

$allowed_categories = ['ideas', 'campaigns', 'all'];
if (!in_array($category_filter, $allowed_categories, true)) {
    $category_filter = 'ideas';
}

// Configuração de títulos baseado na categoria
switch ($category_filter) {
    case 'campaigns':
        $active_categories = [$campaign_category_id];
        $top_likes_title = __('Top 10 Campanhas Mais Curtidas', 'agilizepulsar');
        $top_views_title = __('Top 10 Campanhas Mais Visualizadas', 'agilizepulsar');
        $status_chart_title = __('Campanhas por Status', 'agilizepulsar');
        $timeline_title = __('Evolução de Campanhas', 'agilizepulsar');
        $timeline_dataset_label = __('Campanhas', 'agilizepulsar');
        break;
    case 'all':
        $active_categories = [$idea_category_id, $campaign_category_id];
        $top_likes_title = __('Top 10 Registros Mais Curtidos', 'agilizepulsar');
        $top_views_title = __('Top 10 Registros Mais Visualizados', 'agilizepulsar');
        $status_chart_title = __('Registros por Status', 'agilizepulsar');
        $timeline_title = __('Evolução de Registros', 'agilizepulsar');
        $timeline_dataset_label = __('Registros', 'agilizepulsar');
        break;
    case 'ideas':
    default:
        $category_filter = 'ideas';
        $active_categories = [$idea_category_id];
        $top_likes_title = __('Top 10 Ideias Mais Curtidas', 'agilizepulsar');
        $top_views_title = __('Top 10 Ideias Mais Visualizadas', 'agilizepulsar');
        $status_chart_title = __('Ideias por Status', 'agilizepulsar');
        $timeline_title = __('Evolução de Ideias', 'agilizepulsar');
        $timeline_dataset_label = __('Ideias', 'agilizepulsar');
        break;
}

// Top likes (versão ultra-simplificada)
$top_likes = [];
$categories_str = implode(',', $active_categories);
$top_likes_sql = "
    SELECT t.id, t.name, t.users_id_recipient, t.itilcategories_id, COUNT(l.id) as likes_count
    FROM glpi_tickets t 
    LEFT JOIN glpi_plugin_agilizepulsar_likes l ON l.tickets_id = t.id 
    WHERE t.itilcategories_id IN ($categories_str) 
    GROUP BY t.id, t.name, t.users_id_recipient, t.itilcategories_id
    HAVING likes_count > 0
    ORDER BY likes_count DESC 
    LIMIT 10
";

$likes_data = simpleQueryAll($top_likes_sql);
foreach ($likes_data as $row) {
    $user = new User();
    $userName = __('Não informado', 'agilizepulsar');
    
    if (!empty($row['users_id_recipient']) && $user->getFromDB($row['users_id_recipient'])) {
        $userName = $user->getFriendlyName();
    }

    $link = 'idea.php?id=' . $row['id'];
    if ((int)$row['itilcategories_id'] !== $idea_category_id) {
        global $CFG_GLPI;
        $link = rtrim($CFG_GLPI['url_base'] ?? '', '/') . '/front/ticket.form.php?id=' . $row['id'];
    }

    $top_likes[] = [
        'id'     => $row['id'],
        'name'   => $row['name'],
        'author' => $userName,
        'count'  => (int)$row['likes_count'],
        'link'   => $link
    ];
}

// Top views (versão ultra-simplificada)
$top_views = [];
$top_views_sql = "
    SELECT t.id, t.name, t.users_id_recipient, t.itilcategories_id, COUNT(v.id) as views_count
    FROM glpi_tickets t 
    LEFT JOIN glpi_plugin_agilizepulsar_views v ON v.tickets_id = t.id 
    WHERE t.itilcategories_id IN ($categories_str) 
    GROUP BY t.id, t.name, t.users_id_recipient, t.itilcategories_id
    HAVING views_count > 0
    ORDER BY views_count DESC 
    LIMIT 10
";

$views_data = simpleQueryAll($top_views_sql);
foreach ($views_data as $row) {
    $user = new User();
    $userName = __('Não informado', 'agilizepulsar');
    
    if (!empty($row['users_id_recipient']) && $user->getFromDB($row['users_id_recipient'])) {
        $userName = $user->getFriendlyName();
    }

    $link = 'idea.php?id=' . $row['id'];
    if ((int)$row['itilcategories_id'] !== $idea_category_id) {
        global $CFG_GLPI;
        $link = rtrim($CFG_GLPI['url_base'] ?? '', '/') . '/front/ticket.form.php?id=' . $row['id'];
    }

    $top_views[] = [
        'id'     => $row['id'],
        'name'   => $row['name'],
        'author' => $userName,
        'count'  => (int)$row['views_count'],
        'link'   => $link
    ];
}

// Top campanhas
$top_campaigns = [];
$campaigns_sql = "SELECT id, name, date FROM glpi_tickets WHERE itilcategories_id = $campaign_category_id ORDER BY date DESC LIMIT 10";
$campaigns_data = simpleQueryAll($campaigns_sql);

foreach ($campaigns_data as $campaign_data) {
    // Contar ideias vinculadas
    $ideas_count_sql = "SELECT COUNT(*) as count FROM glpi_items_tickets WHERE itemtype = 'Ticket' AND items_id = " . $campaign_data['id'];
    $ideas_count_result = simpleQuery($ideas_count_sql);
    $ideas_count = (int)$ideas_count_result['count'];
    
    // Contar likes
    $likes_count_sql = "SELECT COUNT(*) as count FROM glpi_plugin_agilizepulsar_likes WHERE tickets_id = " . $campaign_data['id'];
    $likes_count_result = simpleQuery($likes_count_sql);
    $likes_count = (int)$likes_count_result['count'];
    
    $top_campaigns[] = [
        'id' => $campaign_data['id'],
        'name' => $campaign_data['name'],
        'date' => $campaign_data['date'],
        'ideas_count' => $ideas_count,
        'likes_count' => $likes_count
    ];
}

// Ordenar por ideias
usort($top_campaigns, function($a, $b) {
    return $b['ideas_count'] - $a['ideas_count'];
});

// Status counts
$status_counts = [];
$status_sql = "SELECT status, COUNT(*) as total FROM glpi_tickets WHERE itilcategories_id IN ($categories_str) GROUP BY status";
$status_data = simpleQueryAll($status_sql);
foreach ($status_data as $row) {
    $status_counts[(int)$row['status']] = (int)$row['total'];
}

// Timeline data (simplificado - últimos 12 meses)
$month_labels = [];
$monthly_counts = [];
for ($i = 11; $i >= 0; $i--) {
    $date = new DateTime();
    $date->modify("-$i months");
    $month_labels[] = $date->format('m/Y');
    
    $year_month = $date->format('Y-m');
    $timeline_sql = "SELECT COUNT(*) as count FROM glpi_tickets WHERE itilcategories_id IN ($categories_str) AND DATE_FORMAT(date, '%Y-%m') = '$year_month'";
    $timeline_result = simpleQuery($timeline_sql);
    $monthly_counts[] = (int)$timeline_result['count'];
}

$status_labels = [];
$status_values = [];
$status_colors = [];
$color_palette = ['#0ea5e9', '#6366f1', '#10b981', '#f97316', '#f43f5e', '#a855f7', '#14b8a6', '#f59e0b'];
$color_index = 0;
foreach (Ticket::getAllStatusArray() as $status => $label) {
    $status_labels[] = $label;
    $status_values[] = $status_counts[$status] ?? 0;
    $status_colors[] = $color_palette[$color_index % count($color_palette)];
    $color_index++;
}

$card_data = [
    ['label' => __('Total Ideias', 'agilizepulsar'), 'value' => $total_ideas, 'icon' => 'fa-lightbulb'],
    ['label' => __('Total Campanhas', 'agilizepulsar'), 'value' => $total_campaigns_all, 'icon' => 'fa-flag'],
    ['label' => __('Campanhas Ativas', 'agilizepulsar'), 'value' => $total_campaigns_active, 'icon' => 'fa-bullseye'],
    ['label' => __('Taxa de Conversão (Ideias/Campanha)', 'agilizepulsar'), 'value' => $conversion_rate, 'icon' => 'fa-chart-simple', 'format' => 'decimal'],
    ['label' => __('Ideias Aprovadas', 'agilizepulsar'), 'value' => $ideas_approved, 'icon' => 'fa-circle-check'],
    ['label' => __('Ideias Implementadas', 'agilizepulsar'), 'value' => $ideas_implemented, 'icon' => 'fa-screwdriver-wrench']
];

$campaign_status_labels = [__('Ativas', 'agilizepulsar'), __('Encerradas', 'agilizepulsar')];
$campaign_status_dataset_label = __('Quantidade', 'agilizepulsar');

// Export
if (isset($_GET['export'])) {
    $export = $_GET['export'];
    if ($export === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="pulsar_dashboard.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Indicador', 'Valor']);
        foreach ($card_data as $card) {
            fputcsv($output, [$card['label'], $card['value']]);
        }
        fclose($output);
        exit;
    }
}

$title = sprintf(__('%s – Dashboard', 'agilizepulsar'), $menu_name);
if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
   Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}

$can_admin = PluginAgilizepulsarConfig::canAdmin($user_profile);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="<?php echo $plugin_web; ?>/css/pulsar.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div id="pulsar-demo" class="pulsar-wrap">

  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1><?php echo htmlspecialchars($menu_name); ?></h1>
      <p class="pulsar-muted">Acompanhe indicadores, rankings e a evolução das ideias.</p>
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
    <a class="topnav-item is-active" href="dashboard.php">
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

  <section class="card-u dashboard-filters">
    <form method="get" class="filters-form">
      <div class="filter-group">
        <label for="category-select">Categoria</label>
        <select id="category-select" name="category">
          <option value="ideas" <?php echo $category_filter === 'ideas' ? 'selected' : ''; ?>>Ideias</option>
          <option value="campaigns" <?php echo $category_filter === 'campaigns' ? 'selected' : ''; ?>>Campanhas</option>
          <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>Todas</option>
        </select>
      </div>
      <div class="filter-actions">
        <button type="submit" class="btn-u primary"><i class="fa-solid fa-filter"></i> Aplicar</button>
      </div>
    </form>
  </section>

  <section class="card-grid">
    <?php foreach ($card_data as $card): ?>
    <?php
      $value_display = isset($card['format']) && $card['format'] === 'decimal'
        ? number_format((float)$card['value'], 1, ',', '.')
        : number_format((int)$card['value'], 0, ',', '.');
    ?>
    <article class="card-u dashboard-card">
      <div class="card-icon"><i class="fa-solid <?php echo htmlspecialchars($card['icon']); ?>"></i></div>
      <div class="card-info">
        <span class="card-value"><?php echo $value_display; ?></span>
        <span class="card-label"><?php echo htmlspecialchars($card['label']); ?></span>
      </div>
    </article>
    <?php endforeach; ?>
  </section>

  <div class="charts-section">
    <div class="charts-row">
      <section class="card-u chart-container">
        <h2><i class="fa-solid fa-chart-pie"></i> <?php echo htmlspecialchars($status_chart_title); ?></h2>
        <div class="chart-wrapper">
          <canvas id="statusChart"></canvas>
        </div>
      </section>

      <section class="card-u chart-container">
        <h2><i class="fa-solid fa-chart-line"></i> <?php echo htmlspecialchars($timeline_title); ?></h2>
        <div class="chart-wrapper">
          <canvas id="timelineChart"></canvas>
        </div>
      </section>

      <section class="card-u chart-container">
        <h2><i class="fa-solid fa-chart-bar"></i> <?php echo __('Campanhas Ativas vs Encerradas', 'agilizepulsar'); ?></h2>
        <div class="chart-wrapper">
          <canvas id="campaignStatusChart"></canvas>
        </div>
      </section>
    </div>
  </div>

  <div class="dashboard-grid">
    <section class="card-u">
      <h2><i class="fa-solid fa-heart"></i> <?php echo htmlspecialchars($top_likes_title); ?></h2>
      <table class="pulsar-table">
        <thead>
          <tr>
            <th>Título</th>
            <th>Autor</th>
            <th>Curtidas</th>
            <th>Link</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($top_likes) === 0): ?>
            <tr><td colspan="4" class="empty-cell">Nenhum registro encontrado</td></tr>
          <?php else: ?>
            <?php foreach ($top_likes as $item): ?>
            <tr>
              <td><?php echo htmlspecialchars($item['name']); ?></td>
              <td><?php echo htmlspecialchars($item['author']); ?></td>
              <td><?php echo (int)$item['count']; ?></td>
              <td><a href="<?php echo htmlspecialchars($item['link']); ?>" class="link-inline"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <section class="card-u">
      <h2><i class="fa-solid fa-eye"></i> <?php echo htmlspecialchars($top_views_title); ?></h2>
      <table class="pulsar-table">
        <thead>
          <tr>
            <th>Título</th>
            <th>Autor</th>
            <th>Visualizações</th>
            <th>Link</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($top_views) === 0): ?>
            <tr><td colspan="4" class="empty-cell">Nenhum registro encontrado</td></tr>
          <?php else: ?>
            <?php foreach ($top_views as $item): ?>
            <tr>
              <td><?php echo htmlspecialchars($item['name']); ?></td>
              <td><?php echo htmlspecialchars($item['author']); ?></td>
              <td><?php echo (int)$item['count']; ?></td>
              <td><a href="<?php echo htmlspecialchars($item['link']); ?>" class="link-inline"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </div>

  <section class="card-u">
    <h2><i class="fa-solid fa-flag"></i> <?php echo __('Top 10 Campanhas com Mais Ideias', 'agilizepulsar'); ?></h2>
    <table class="pulsar-table">
      <thead>
        <tr>
          <th>Campanha</th>
          <th>Ideias</th>
          <th>Curtidas</th>
          <th>Data</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($top_campaigns) === 0): ?>
          <tr><td colspan="5" class="empty-cell"><?php echo __('Nenhuma campanha encontrada', 'agilizepulsar'); ?></td></tr>
        <?php else: ?>
          <?php foreach ($top_campaigns as $campaign_row): ?>
          <tr>
            <td><?php echo htmlspecialchars($campaign_row['name']); ?></td>
            <td><?php echo (int)$campaign_row['ideas_count']; ?></td>
            <td><i class="fa-solid fa-heart"></i> <?php echo (int)$campaign_row['likes_count']; ?></td>
            <td><?php echo Html::convDate($campaign_row['date']); ?></td>
            <td>
              <a href="campaign.php?id=<?php echo (int)$campaign_row['id']; ?>" class="btn-outline btn-small">
                <?php echo __('Ver detalhes', 'agilizepulsar'); ?>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

</div>

<script>
  // Configuração padrão para todos os gráficos
  Chart.defaults.responsive = true;
  Chart.defaults.maintainAspectRatio = false;

  const statusCtx = document.getElementById('statusChart');
  const statusChart = new Chart(statusCtx, {
    type: 'pie',
    data: {
      labels: <?php echo json_encode($status_labels); ?>,
      datasets: [{
        data: <?php echo json_encode($status_values); ?>,
        backgroundColor: <?php echo json_encode($status_colors); ?>,
        borderWidth: 2,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 15,
            usePointStyle: true
          }
        }
      }
    }
  });

  const timelineCtx = document.getElementById('timelineChart');
  const timelineChart = new Chart(timelineCtx, {
    type: 'line',
    data: {
      labels: <?php echo json_encode($month_labels); ?>,
      datasets: [{
        label: '<?php echo addslashes($timeline_dataset_label); ?>',
        data: <?php echo json_encode($monthly_counts); ?>,
        borderColor: '#00995d',
        backgroundColor: 'rgba(0, 153, 93, 0.1)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#00995d',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          },
          grid: {
            color: 'rgba(0,0,0,0.1)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });

  const campaignStatusCtx = document.getElementById('campaignStatusChart');
  if (campaignStatusCtx) {
    new Chart(campaignStatusCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($campaign_status_labels); ?>,
        datasets: [{
          label: '<?php echo addslashes($campaign_status_dataset_label); ?>',
          data: [<?php echo $total_campaigns_active; ?>, <?php echo $total_campaigns_closed; ?>],
          backgroundColor: ['#00995d', '#94a3b8'],
          borderRadius: 6,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { 
            display: false 
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            },
            grid: {
              color: 'rgba(0,0,0,0.1)'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  }
</script>

<?php
Html::footer();
?>