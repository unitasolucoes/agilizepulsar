<?php

include('../../../inc/includes.php');
require_once __DIR__ . '/../inc/ticket.class.php';

Session::checkLoginUser();

$profile_id = $_SESSION['glpiactiveprofile']['id'] ?? 0;
if (!PluginAgilizepulsarConfig::canView($profile_id)) {
    Html::displayRightError();
    exit;
}

$config = PluginAgilizepulsarConfig::getConfig();
$menu_name = $config['menu_name'] ?? 'Pulsar';
$title = sprintf(__('%s – Campanhas', 'agilizepulsar'), $menu_name);

if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpHeader($title, '', 'helpdesk', 'management');
} else {
    Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
}

$plugin_web = Plugin::getWebDir('agilizepulsar');
$campaigns = PluginAgilizepulsarTicket::getCampaigns();

?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<link rel="stylesheet" href="<?php echo $plugin_web; ?>/css/pulsar.css" />

<div class="pulsar-wrap">
  <section class="pulsar-hero card-u">
    <div class="hero-left">
      <h1><?php echo __('Campanhas', 'agilizepulsar'); ?></h1>
      <p class="pulsar-muted"><?php echo __('Visualize todas as campanhas cadastradas e seus principais detalhes.', 'agilizepulsar'); ?></p>
    </div>
    <?php if (PluginAgilizepulsarConfig::canAdmin($profile_id)): ?>
      <div class="pulsar-actions">
        <a href="nova_campanha.php" class="btn-u primary"><i class="fa-solid fa-flag-checkered"></i> <?php echo __('Nova Campanha', 'agilizepulsar'); ?></a>
      </div>
    <?php endif; ?>
  </section>

  <section class="card-u">
    <h2><i class="fa-solid fa-flag"></i> <?php echo __('Campanhas cadastradas', 'agilizepulsar'); ?></h2>

    <?php if (empty($campaigns)): ?>
      <p class="pulsar-muted"><?php echo __('Nenhuma campanha cadastrada até o momento.', 'agilizepulsar'); ?></p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table-u">
          <thead>
            <tr>
              <th><?php echo __('Título', 'agilizepulsar'); ?></th>
              <th><?php echo __('Status', 'agilizepulsar'); ?></th>
              <th><?php echo __('Prazo estimado', 'agilizepulsar'); ?></th>
              <th><?php echo __('Ações', 'agilizepulsar'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($campaigns as $campaign):
              $deadline = $campaign['time_to_resolve'] ?? null;
              $deadline = $deadline ? Html::convDateTime($deadline) : __('Não informado', 'agilizepulsar');
              $status = PluginAgilizepulsarTicket::getStatusPresentation($campaign['status'] ?? Ticket::INCOMING);
            ?>
              <tr>
                <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                <td><span class="badge-u <?php echo htmlspecialchars($status['class']); ?>"><i class="fa-solid <?php echo htmlspecialchars($status['icon']); ?>"></i> <?php echo htmlspecialchars($status['label']); ?></span></td>
                <td><?php echo htmlspecialchars($deadline); ?></td>
                <td>
                  <a class="btn-u ghost" href="campaign.php?id=<?php echo (int) $campaign['id']; ?>">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <?php echo __('Detalhes', 'agilizepulsar'); ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php
if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpFooter();
} else {
    Html::footer();
}
