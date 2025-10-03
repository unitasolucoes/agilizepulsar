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
$idea_category_id = (int)$config['idea_category_id'];
$campaign_category_id = (int)$config['campaign_category_id'];

$all_campaigns = PluginAgilizepulsarTicket::getCampaigns();
$active_statuses = [Ticket::INCOMING, Ticket::ASSIGNED, Ticket::PLANNED, Ticket::WAITING];
$total_campaigns_active = 0;
$total_campaigns_closed = 0;
foreach ($all_campaigns as $campaign_item) {
    if (in_array($campaign_item['status'], $active_statuses, true)) {
        $total_campaigns_active++;
    } else {
        $total_campaigns_closed++;
    }
}
$total_campaigns_all = count($all_campaigns);
$overall_ideas_count = count(PluginAgilizepulsarTicket::getIdeas());
$conversion_rate = $total_campaigns_all > 0 ? round($overall_ideas_count / $total_campaigns_all, 1) : 0;
$campaign_status_labels = [__('Ativas', 'agilizepulsar'), __('Encerradas', 'agilizepulsar')];
$campaign_status_dataset_label = __('Quantidade', 'agilizepulsar');

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

global $DB, $CFG_GLPI;

$now = new DateTime();
$now->setTime(23, 59, 59);
$startDate = null;
$endDate = clone $now;

if ($period === 'custom') {
    $startDate = $start_date_param ? DateTime::createFromFormat('Y-m-d', $start_date_param) : null;
    if ($startDate instanceof DateTime) {
        $startDate->setTime(0, 0, 0);
    } else {
        $start_date_param = '';
        $startDate = null;
    }

    $endCandidate = $end_date_param ? DateTime::createFromFormat('Y-m-d', $end_date_param) : null;
    if ($endCandidate instanceof DateTime) {
        $endCandidate->setTime(23, 59, 59);
        $endDate = $endCandidate;
    } else {
        $end_date_param = $endDate->format('Y-m-d');
    }
} else {
    if ($period === 'month') {
        $startDate = (clone $endDate)->modify('-1 month');
    } elseif ($period === 'quarter') {
        $startDate = (clone $endDate)->modify('-3 months');
    } else {
        $startDate = (clone $endDate)->modify('-1 year');
        $period = 'year';
    }
    $startDate->setTime(0, 0, 0);
    $start_date_param = $startDate->format('Y-m-d');
    $end_date_param = $endDate->format('Y-m-d');
}

if ($startDate && $endDate && $startDate > $endDate) {
    $tmp = clone $startDate;
    $startDate = clone $endDate;
    $startDate->setTime(0, 0, 0);
    $endDate = $tmp;
    $endDate->setTime(23, 59, 59);
}

$startDateStr = $startDate ? $startDate->format('Y-m-d H:i:s') : null;
$endDateStr = $endDate ? $endDate->format('Y-m-d H:i:s') : null;

$timeline_dataset_label = __('Ideias', 'agilizepulsar');
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

$buildWhere = function (array $categoryIds, $alias = 't') use ($DB, $startDateStr, $endDateStr) {
    if (empty($categoryIds)) {
        return '1=0';
    }

    $categoryIds = array_map('intval', $categoryIds);
    $parts = [];
    $parts[] = sprintf('%s.itilcategories_id IN (%s)', $alias, implode(',', $categoryIds));

    if ($startDateStr) {
        $parts[] = sprintf("%s.date >= '%s'", $alias, $DB->escape($startDateStr));
    }

    if ($endDateStr) {
        $parts[] = sprintf("%s.date <= '%s'", $alias, $DB->escape($endDateStr));
    }

    return implode(' AND ', $parts);
};

$countTickets = function (array $categoryIds, array $statuses = []) use ($DB, $buildWhere) {
    if (empty($categoryIds)) {
        return 0;
    }

    $where = $buildWhere($categoryIds, 't');
    if (!empty($statuses)) {
        $statuses = array_map('intval', $statuses);
        $where .= ' AND t.status IN (' . implode(',', $statuses) . ')';
    }

    $sql = "SELECT COUNT(*) AS total FROM glpi_tickets t WHERE $where";
    $result = $DB->query($sql);
    if ($result) {
        $row = $DB->fetchAssoc($result);
        return (int)($row['total'] ?? 0);
    }

    return 0;
};

$total_ideas = $countTickets([$idea_category_id]);
$total_campaigns = $countTickets([$campaign_category_id]);
$ideas_approved = $countTickets([$idea_category_id], [Ticket::SOLVED]);
$ideas_implemented = $countTickets([$idea_category_id], [Ticket::CLOSED]);

$top_likes = [];
if (!empty($active_categories)) {
    $likes_where_sql = $buildWhere($active_categories, 't');

    $iterator = $DB->request([
        'SELECT' => [
            't.id',
            't.name',
            't.users_id_recipient',
            't.itilcategories_id',
            'u.realname',
            'u.firstname',
            'u.name AS username',
            'likes_count' => new QueryExpression('COUNT(l.id)')
        ],
        'FROM' => 'glpi_tickets AS t',
        'LEFT JOIN' => [
            'glpi_plugin_agilizepulsar_likes AS l' => [
                'ON' => ['l' => 'tickets_id', 't' => 'id']
            ],
            'glpi_users AS u' => [
                'ON' => ['u' => 'id', 't' => 'users_id_recipient']
            ]
        ],
        'WHERE' => new QueryExpression($likes_where_sql),
        'GROUPBY' => ['t.id', 't.name', 't.users_id_recipient', 't.itilcategories_id', 'u.realname', 'u.firstname', 'u.name'],
        'ORDER' => ['likes_count DESC', 't.name ASC'],
        'LIMIT' => 10
    ]);

    foreach ($iterator as $row) {
        $userName = __('Não informado', 'agilizepulsar');
        if (!empty($row['firstname']) || !empty($row['realname'])) {
            $potential = trim(($row['firstname'] ?? '') . ' ' . ($row['realname'] ?? ''));
            if ($potential !== '') {
                $userName = $potential;
            }
        }

        if ($userName === __('Não informado', 'agilizepulsar') && !empty($row['username'])) {
            $userName = $row['username'];
        }

        $link = 'idea.php?id=' . $row['id'];
        if ((int)$row['itilcategories_id'] !== $idea_category_id) {
            $link = rtrim($CFG_GLPI['url_base'] ?? '', '/') . '/front/ticket.form.php?id=' . $row['id'];
        }

        $top_likes[] = [
            'id'     => $row['id'],
            'name'   => $row['name'],
            'author' => $userName,
            'count'  => (int) $row['likes_count'],
            'link'   => $link
        ];
    }
}

$top_views = [];
if (!empty($active_categories)) {
    $views_where_sql = $buildWhere($active_categories, 't');

    $iterator = $DB->request([
        'SELECT' => [
            't.id',
            't.name',
            't.users_id_recipient',
            't.itilcategories_id',
            'u.realname',
            'u.firstname',
            'u.name AS username',
            'views_count' => new QueryExpression('COUNT(DISTINCT v.users_id)')
        ],
        'FROM' => 'glpi_tickets AS t',
        'LEFT JOIN' => [
            'glpi_plugin_agilizepulsar_views AS v' => [
                'ON' => ['v' => 'tickets_id', 't' => 'id']
            ],
            'glpi_users AS u' => [
                'ON' => ['u' => 'id', 't' => 'users_id_recipient']
            ]
        ],
        'WHERE' => new QueryExpression($views_where_sql),
        'GROUPBY' => ['t.id', 't.name', 't.users_id_recipient', 't.itilcategories_id', 'u.realname', 'u.firstname', 'u.name'],
        'ORDER' => ['views_count DESC', 't.name ASC'],
        'LIMIT' => 10
    ]);

    foreach ($iterator as $row) {
        $userName = __('Não informado', 'agilizepulsar');
        if (!empty($row['firstname']) || !empty($row['realname'])) {
            $potential = trim(($row['firstname'] ?? '') . ' ' . ($row['realname'] ?? ''));
            if ($potential !== '') {
                $userName = $potential;
            }
        }

        if ($userName === __('Não informado', 'agilizepulsar') && !empty($row['username'])) {
            $userName = $row['username'];
        }

        $link = 'idea.php?id=' . $row['id'];
        if ((int)$row['itilcategories_id'] !== $idea_category_id) {
            $link = rtrim($CFG_GLPI['url_base'] ?? '', '/') . '/front/ticket.form.php?id=' . $row['id'];
        }

        $top_views[] = [
            'id'     => $row['id'],
            'name'   => $row['name'],
            'author' => $userName,
            'count'  => (int) $row['views_count'],
            'link'   => $link
        ];
    }
}

$top_campaigns = [];
if ($campaign_category_id > 0 && $idea_category_id > 0) {
    $query = sprintf(
        "SELECT t.id, t.name, t.date, COUNT(it.tickets_id) AS ideas_count, "
        . "(SELECT COUNT(*) FROM glpi_plugin_agilizepulsar_likes WHERE tickets_id = t.id) AS likes_count "
        . "FROM glpi_tickets t "
        . "LEFT JOIN glpi_items_tickets it ON (it.items_id = t.id AND it.itemtype = 'Ticket') "
        . "LEFT JOIN glpi_tickets ideas ON (ideas.id = it.tickets_id AND ideas.itilcategories_id = %d) "
        . "WHERE t.itilcategories_id = %d "
        . "GROUP BY t.id, t.name, t.date "
        . "ORDER BY ideas_count DESC "
        . "LIMIT 10",
        $idea_category_id,
        $campaign_category_id
    );

    if ($result = $DB->query($query)) {
        while ($row = $DB->fetchAssoc($result)) {
            $top_campaigns[] = $row;
        }
    }
}

$status_counts = [];
if (!empty($active_categories)) {
    $whereSql = $buildWhere($active_categories, 't');
    $sql = "SELECT t.status, COUNT(*) AS total
            FROM glpi_tickets t
            WHERE $whereSql
            GROUP BY t.status";

    $iterator = $DB->request($sql);
    foreach ($iterator as $row) {
        $status_counts[(int)$row['status']] = (int)$row['total'];
    }
}

$timeline_start = new DateTime('first day of this month');
$timeline_start->modify('-11 months');
$timeline_start->setTime(0, 0, 0);
if ($startDate && $startDate > $timeline_start) {
    $timeline_start = (clone $startDate)->modify('first day of this month');
}

$month_labels = [];
$month_keys = [];
$cursor = clone $timeline_start;
for ($i = 0; $i < 12; $i++) {
    $month_labels[] = $cursor->format('m/Y');
    $month_keys[] = $cursor->format('Y-m');
    $cursor->modify('+1 month');
}

$monthly_counts_map = [];
if (!empty($active_categories)) {
    $whereSql = $buildWhere($active_categories, 't');
    $whereSql .= " AND t.date >= '" . $DB->escape($timeline_start->format('Y-m-d H:i:s')) . "'";
    if ($endDateStr) {
        $whereSql .= " AND t.date <= '" . $DB->escape($endDateStr) . "'";
    }

    $sql = "SELECT DATE_FORMAT(t.date, '%Y-%m') AS month_key, COUNT(*) AS total
            FROM glpi_tickets t
            WHERE $whereSql
            GROUP BY month_key";

    $iterator = $DB->request($sql);
    foreach ($iterator as $row) {
        $monthly_counts_map[$row['month_key']] = (int)$row['total'];
    }
}

$monthly_counts = [];
foreach ($month_keys as $key) {
    $monthly_counts[] = $monthly_counts_map[$key] ?? 0;
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
    ['label' => __('Total Campanhas', 'agilizepulsar'), 'value' => $total_campaigns, 'icon' => 'fa-flag'],
    ['label' => __('Campanhas Ativas', 'agilizepulsar'), 'value' => $total_campaigns_active, 'icon' => 'fa-bullseye'],
    ['label' => __('Taxa de Conversão (Ideias/Campanha)', 'agilizepulsar'), 'value' => $conversion_rate, 'icon' => 'fa-chart-simple', 'format' => 'decimal'],
    ['label' => __('Ideias Aprovadas', 'agilizepulsar'), 'value' => $ideas_approved, 'icon' => 'fa-circle-check'],
    ['label' => __('Ideias Implementadas', 'agilizepulsar'), 'value' => $ideas_implemented, 'icon' => 'fa-screwdriver-wrench']
];

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
        fputcsv($output, []);
        fputcsv($output, ['Status', 'Quantidade']);
        foreach ($status_labels as $index => $label) {
            fputcsv($output, [$label, $status_values[$index]]);
        }
        fputcsv($output, []);
        fputcsv($output, [$top_likes_title]);
        fputcsv($output, ['Título', 'Autor', 'Curtidas']);
        foreach ($top_likes as $item) {
            fputcsv($output, [$item['name'], $item['author'], $item['count']]);
        }
        fputcsv($output, []);
        fputcsv($output, [$top_views_title]);
        fputcsv($output, ['Título', 'Autor', 'Visualizações']);
        foreach ($top_views as $item) {
            fputcsv($output, [$item['name'], $item['author'], $item['count']]);
        }
        fclose($output);
        exit;
    }

    if ($export === 'pdf') {
        if (file_exists(GLPI_ROOT . '/lib/tcpdf/tcpdf.php')) {
            require_once GLPI_ROOT . '/lib/tcpdf/tcpdf.php';
            $pdf = new TCPDF();
            $pdf->SetCreator('GLPI');
            $pdf->SetAuthor('Pulsar');
            $pdf->SetTitle('Dashboard Pulsar');
            $pdf->AddPage();
            $html = '<h1>Dashboard Pulsar</h1>';
            $html .= '<h2>Indicadores</h2><ul>';
            foreach ($card_data as $card) {
                $html .= '<li><strong>' . htmlspecialchars($card['label']) . ':</strong> ' . (int)$card['value'] . '</li>';
            }
            $html .= '</ul>';
            $html .= '<h2>' . htmlspecialchars($status_chart_title) . '</h2><ul>';
            foreach ($status_labels as $index => $label) {
                $html .= '<li>' . htmlspecialchars($label) . ': ' . (int)$status_values[$index] . '</li>';
            }
            $html .= '</ul>';
            $html .= '<h2>' . htmlspecialchars($top_likes_title) . '</h2><ol>';
            foreach ($top_likes as $item) {
                $html .= '<li>' . htmlspecialchars($item['name']) . ' – ' . htmlspecialchars($item['author']) . ' (' . (int)$item['count'] . ')</li>';
            }
            $html .= '</ol>';
            $html .= '<h2>' . htmlspecialchars($top_views_title) . '</h2><ol>';
            foreach ($top_views as $item) {
                $html .= '<li>' . htmlspecialchars($item['name']) . ' – ' . htmlspecialchars($item['author']) . ' (' . (int)$item['count'] . ')</li>';
            }
            $html .= '</ol>';
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output('pulsar_dashboard.pdf', 'D');
            exit;
        }

        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Biblioteca TCPDF não encontrada.';
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

$export_params = [
    'period' => $period,
    'category' => $category_filter
];
if ($period === 'custom') {
    $export_params['start_date'] = $start_date_param;
    $export_params['end_date'] = $end_date_param;
}
$pdf_link = 'dashboard.php?' . http_build_query($export_params + ['export' => 'pdf']);
$csv_link = 'dashboard.php?' . http_build_query($export_params + ['export' => 'csv']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
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
        <label for="period-select">Período</label>
        <select id="period-select" name="period">
          <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Último mês</option>
          <option value="quarter" <?php echo $period === 'quarter' ? 'selected' : ''; ?>>Último trimestre</option>
          <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Último ano</option>
          <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Personalizado</option>
        </select>
      </div>
      <div class="filter-group">
        <label for="category-select">Categoria</label>
        <select id="category-select" name="category">
          <option value="ideas" <?php echo $category_filter === 'ideas' ? 'selected' : ''; ?>>Ideias</option>
          <option value="campaigns" <?php echo $category_filter === 'campaigns' ? 'selected' : ''; ?>>Campanhas</option>
          <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>Todas</option>
        </select>
      </div>
      <div class="filter-group custom-dates" <?php echo $period === 'custom' ? '' : 'style="display:none"'; ?>>
        <label>Data inicial</label>
        <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date_param); ?>">
      </div>
      <div class="filter-group custom-dates" <?php echo $period === 'custom' ? '' : 'style="display:none"'; ?>>
        <label>Data final</label>
        <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date_param); ?>">
      </div>
      <div class="filter-actions">
        <button type="submit" class="btn-u primary"><i class="fa-solid fa-filter"></i> Aplicar</button>
      </div>
    </form>
    <div class="export-actions">
      <a href="<?php echo htmlspecialchars($pdf_link); ?>" class="btn-u ghost"><i class="fa-solid fa-file-pdf"></i> Exportar PDF</a>
      <a href="<?php echo htmlspecialchars($csv_link); ?>" class="btn-u ghost"><i class="fa-solid fa-file-csv"></i> Exportar CSV</a>
    </div>
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

  <div class="dashboard-grid">
    <section class="card-u">
      <h2><i class="fa-solid fa-chart-pie"></i> <?php echo htmlspecialchars($status_chart_title); ?></h2>
      <canvas id="statusChart" height="240"></canvas>
    </section>

    <section class="card-u">
      <h2><i class="fa-solid fa-chart-line"></i> <?php echo htmlspecialchars($timeline_title); ?></h2>
      <canvas id="timelineChart" height="240"></canvas>
    </section>
    <section class="card-u">
      <h2><i class="fa-solid fa-chart-bar"></i> <?php echo __('Campanhas Ativas vs Encerradas', 'agilizepulsar'); ?></h2>
      <canvas id="campaignStatusChart" height="240"></canvas>
    </section>
  </div>
</div>

<script>
  const periodSelect = document.getElementById('period-select');
  const customFields = document.querySelectorAll('.custom-dates');

  function toggleCustomDates() {
    const isCustom = periodSelect.value === 'custom';
    customFields.forEach(field => {
      field.style.display = isCustom ? '' : 'none';
    });
  }

  periodSelect.addEventListener('change', toggleCustomDates);
  toggleCustomDates();

  const statusCtx = document.getElementById('statusChart');
  const statusChart = new Chart(statusCtx, {
    type: 'pie',
    data: {
      labels: <?php echo json_encode($status_labels); ?>,
      datasets: [{
        data: <?php echo json_encode($status_values); ?>,
        backgroundColor: <?php echo json_encode($status_colors); ?>
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {position: 'bottom'}
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
        backgroundColor: 'rgba(0, 153, 93, 0.15)',
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
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
          backgroundColor: ['#00995d', '#94a3b8']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }
</script>

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
  .btn-outline{border:1px solid var(--u-border);border-radius:10px;padding:8px 12px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;color:#1f2933;transition:all .2s}
  .btn-outline:hover{border-color:var(--u-primary);color:#00995d}
  .btn-small{font-size:0.85rem}
  .pulsar-topnav{display:flex;gap:8px;align-items:center;margin-bottom:16px;background:linear-gradient(180deg,#fff,#fbfcff);padding:12px 16px}
  .topnav-item{display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;color:#1f2933;text-decoration:none;font-weight:600}
  .topnav-item.is-active,.topnav-item:hover{background:rgba(0,153,93,.12);color:#00995d}
  .dashboard-filters{display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;align-items:flex-end}
  .filters-form{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end}
  .filter-group{display:flex;flex-direction:column;gap:8px;min-width:160px}
  .filter-group label{font-weight:600;color:#1f2933}
  .filter-group select,.filter-group input{border:1px solid var(--u-border);border-radius:8px;padding:10px;font-size:1rem}
  .filter-actions{display:flex;align-items:flex-end}
  .export-actions{display:flex;gap:12px;flex-wrap:wrap}
  .card-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-bottom:16px}
  .dashboard-card{display:flex;align-items:center;gap:16px}
  .card-icon{width:56px;height:56px;border-radius:16px;background:rgba(0,153,93,.12);display:flex;align-items:center;justify-content:center;color:#00995d;font-size:24px}
  .card-info{display:flex;flex-direction:column}
  .card-value{font-size:26px;font-weight:700;color:#1f2933}
  .card-label{color:#667085;font-size:0.95rem}
  .dashboard-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));margin-bottom:16px}
  .pulsar-table{width:100%;border-collapse:collapse;margin-top:12px}
  .pulsar-table th,.pulsar-table td{border-bottom:1px solid var(--u-border);padding:10px;text-align:left;font-size:0.95rem}
  .pulsar-table th{color:#1f2933;font-weight:600;background:#f8fafc}
  .pulsar-table tbody tr:hover{background:#f3f4f6}
  .empty-cell{text-align:center;color:#9ca3af;font-style:italic}
  .link-inline{color:#00995d;text-decoration:none}
  .link-inline:hover{text-decoration:underline}
</style>
<?php
Html::footer();
