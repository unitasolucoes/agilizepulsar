<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova campanha com layout nativo do GLPI.
 *
 * @param array  $areasImpactadas Lista de áreas disponíveis.
 * @param array  $campanhas       Campanhas existentes para seleção como pai.
 * @param string $csrf            Token CSRF.
 */
function plugin_agilizepulsar_render_campanha_form(array $areasImpactadas, array $campanhas, string $csrf): void {
    $pluginWeb = Plugin::getWebDir('agilizepulsar');

    echo "<div class='center'>";
    echo "<h1>" . __('Nova Campanha', 'agilizepulsar') . "</h1>";
    echo "<form id='form-nova-campanha' method='post' action='{$pluginWeb}/front/processar_campanha.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='_glpi_csrf_token' value='" . Html::entities_deep($csrf) . "'>";

    // Identificação
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr><th colspan='2'>" . __('Identificação da Campanha', 'agilizepulsar') . "</th></tr>";
    echo "<tr class='tab_bg_1'><td class='left' width='30%'><label for='titulo'><span class='red'>*</span> " . __('Título da Campanha', 'agilizepulsar') . "</label></td>";
    echo "<td class='left'><input type='text' id='titulo' name='titulo' size='70' maxlength='255' required></td></tr>";

    echo "<tr class='tab_bg_2'><td class='left'><label for='campanha_pai_id'>" . __('Campanha pai (opcional)', 'agilizepulsar') . "</label></td><td class='left'>";
    echo "<select id='campanha_pai_id' name='campanha_pai_id'>";
    echo "<option value='0'>" . __('Nenhuma', 'agilizepulsar') . "</option>";
    foreach ($campanhas as $campanha) {
        $id = (int) $campanha['id'];
        $name = Html::entities_deep($campanha['name']);
        echo "<option value='{$id}'>{$name}</option>";
    }
    echo "</select></td></tr>";

    echo "<tr class='tab_bg_1'><td class='left' valign='top'><label for='descricao'><span class='red'>*</span> " . __('Descrição da Campanha', 'agilizepulsar') . "</label></td>";
    echo "<td class='left'><textarea id='descricao' name='descricao' class='tinymce-editor' rows='8' required></textarea></td></tr>";
    echo "</table>";

    // Estratégia
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr><th colspan='2'>" . __('Estratégia da Campanha', 'agilizepulsar') . "</th></tr>";
    echo "<tr class='tab_bg_1'><td class='left' valign='top'><label><span class='red'>*</span> " . __('Público-alvo', 'agilizepulsar') . "</label></td><td class='left'>";

    $publicos = [
        'Beneficiários',
        'Potenciais',
        'Rede credenciada',
        'Corretores',
        'Colaboradores'
    ];

    foreach ($publicos as $publico) {
        $value = Html::entities_deep($publico);
        echo "<label class='mr10'><input type='checkbox' name='publico_alvo[]' value='{$value}'> {$value}</label>";
    }
    echo "</td></tr>";

    echo "<tr class='tab_bg_2'><td class='left' valign='top'><label for='beneficios'><span class='red'>*</span> " . __('Benefícios esperados', 'agilizepulsar') . "</label></td>";
    echo "<td class='left'><textarea id='beneficios' name='beneficios' class='tinymce-editor' rows='8' required></textarea></td></tr>";

    $canais = [
        'E-mail',
        'WhatsApp',
        'App Beneficiário',
        'Portal',
        'Push App',
        'Redes Sociais',
        'Unidade'
    ];

    echo "<tr class='tab_bg_1'><td class='left' valign='top'><label><span class='red'>*</span> " . __('Canais de divulgação', 'agilizepulsar') . "</label></td><td class='left'>";
    foreach ($canais as $canal) {
        $value = Html::entities_deep($canal);
        echo "<label class='mr10'><input type='checkbox' name='canais[]' value='{$value}'> {$value}</label>";
    }
    echo "</td></tr>";
    echo "</table>";

    // Áreas e prazo
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr><th colspan='2'>" . __('Áreas impactadas e prazo', 'agilizepulsar') . "</th></tr>";
    echo "<tr class='tab_bg_1'><td class='left' valign='top'><label for='areas_impactadas'><span class='red'>*</span> " . __('Áreas impactadas', 'agilizepulsar') . "</label></td><td class='left'>";
    echo "<select id='areas_impactadas' name='areas_impactadas[]' multiple size='6' required>";
    foreach ($areasImpactadas as $area) {
        $value = Html::entities_deep($area);
        echo "<option value='{$value}'>{$value}</option>";
    }
    echo "</select></td></tr>";

    echo "<tr class='tab_bg_2'><td class='left'><label for='prazo_estimado'>" . __('Prazo estimado', 'agilizepulsar') . "</label></td>";
    echo "<td class='left'><input type='text' id='prazo_estimado' name='prazo_estimado' class='flatpickr-input' size='25' placeholder='dd/mm/aaaa'></td></tr>";
    echo "</table>";

    // Anexos
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr><th colspan='2'>" . __('Anexos', 'agilizepulsar') . "</th></tr>";
    echo "<tr class='tab_bg_1'><td class='left'><label for='anexos'>" . __('Arquivos adicionais', 'agilizepulsar') . "</label></td><td class='left'><input type='file' id='anexos' name='anexos[]' multiple></td></tr>";
    echo "</table>";

    echo "<div class='center'>";
    echo "<button type='submit' class='btn btn-primary'>" . __('Criar campanha', 'agilizepulsar') . "</button>";
    echo "<a class='btn btn-secondary' href='{$pluginWeb}/front/campanhas.php'>" . __('Cancelar', 'agilizepulsar') . "</a>";
    echo "</div>";

    echo "</form>";
    echo "</div>";
}
