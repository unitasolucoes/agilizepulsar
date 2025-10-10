<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarTicketTab extends CommonGLPI {

    // ✅ Este é NÃO-ESTÁTICO
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() === 'Ticket') {
            $config = PluginAgilizepulsarConfig::getConfig();
            if ($item->fields['itilcategories_id'] == $config['campaign_category_id']
                || $item->fields['itilcategories_id'] == $config['idea_category_id']) {
                return $config['menu_name'];
            }
        }

        return '';
    }

    // ✅ Este é ESTÁTICO
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() === 'Ticket') {
            self::showForTicket($item);
        }
        return true;
    }

    public static function showForTicket(Ticket $ticket) {
        $tickets_id = $ticket->getID();
        $likes      = PluginAgilizepulsarLike::getByTicket($tickets_id);
        $views      = PluginAgilizepulsarView::getByTicket($tickets_id);

        echo "<div class='card-u'>";
        echo "<h2><i class='fa-solid fa-heart'></i> " . __('Curtidas', 'agilizepulsar') . " (" . count($likes) . ")</h2>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th>" . __('Usuário', 'agilizepulsar') . "</th><th>" . __('Data', 'agilizepulsar') . "</th></tr>";
        foreach ($likes as $like) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($like['user_name']) . '</td>';
            echo '<td>' . Html::convDateTime($like['date_creation']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        echo "<h2 style='margin-top:20px'><i class='fa-solid fa-eye'></i> " . __('Visualizações', 'agilizepulsar') . " (" . count($views) . ")</h2>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th>" . __('Usuário', 'agilizepulsar') . "</th><th>" . __('Data', 'agilizepulsar') . "</th></tr>";
        foreach ($views as $view) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($view['user_name']) . '</td>';
            echo '<td>' . Html::convDateTime($view['viewed_at']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
    }
}