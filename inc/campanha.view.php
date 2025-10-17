<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova campanha utilizando a estrutura padrão do GLPI.
 *
 * @param array  $areasImpactadas Lista de áreas disponíveis.
 * @param array  $campanhas       Campanhas existentes para seleção como pai.
 * @param string $csrf            Token CSRF.
 */
function plugin_agilizepulsar_render_campanha_form(array $areasImpactadas, array $campanhas, string $csrf): void {
    $pluginWeb = Plugin::getWebDir('agilizepulsar');

    $publicos = [
        'Beneficiários',
        'Potenciais',
        'Rede credenciada',
        'Corretores',
        'Colaboradores'
    ];

    $canais = [
        'E-mail',
        'WhatsApp',
        'App Beneficiário',
        'Portal',
        'Push App',
        'Redes Sociais',
        'Unidade'
    ];

    echo "<div class='pulsar-form'>";
    echo "<form id='form-nova-campanha' method='post' action='{$pluginWeb}/front/processar_campanha.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='_glpi_csrf_token' value='" . Html::entities_deep($csrf) . "'>";

    echo "<table class='tab_cadre_fixe'>";
    echo "  <tr class='tab_bg_2'>";
    echo "    <th colspan='4'>" . __('Identificação da Campanha', 'agilizepulsar') . "</th>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right' style='width: 220px;'>";
    echo "      <label for='titulo'>" . __('Título da campanha', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <input type='text' id='titulo' name='titulo' class='form-control' maxlength='255' required>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='campanha_pai_id'>" . __('Campanha pai (opcional)', 'agilizepulsar') . "</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <select id='campanha_pai_id' name='campanha_pai_id' class='form-select'>";
    echo "        <option value='0'>" . __('Nenhuma', 'agilizepulsar') . "</option>";
    foreach ($campanhas as $campanha) {
        $id = (int) $campanha['id'];
        $name = Html::entities_deep($campanha['name']);
        echo "        <option value='{$id}'>{$name}</option>";
    }
    echo "      </select>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='descricao'>" . __('Descrição da campanha', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <textarea id='descricao' name='descricao' class='tinymce-editor' rows='10' required></textarea>";
    echo "    </td>";
    echo "  </tr>";
    echo "</table>";

    echo "<table class='tab_cadre_fixe'>";
    echo "  <tr class='tab_bg_2'>";
    echo "    <th colspan='4'>" . __('Estratégia da campanha', 'agilizepulsar') . "</th>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right' style='width: 220px;'>" . __('Público-alvo', 'agilizepulsar') . " *</td>";
    echo "    <td colspan='3'>";
    foreach ($publicos as $publico) {
        $value = Html::entities_deep($publico);
        echo "      <label><input type='checkbox' name='publico_alvo[]' value='{$value}'> {$value}</label>&nbsp;&nbsp;";
    }
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='beneficios'>" . __('Benefícios esperados', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <textarea id='beneficios' name='beneficios' class='tinymce-editor' rows='10' required></textarea>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>" . __('Canais de divulgação', 'agilizepulsar') . " *</td>";
    echo "    <td colspan='3'>";
    foreach ($canais as $canal) {
        $value = Html::entities_deep($canal);
        echo "      <label><input type='checkbox' name='canais[]' value='{$value}'> {$value}</label>&nbsp;&nbsp;";
    }
    echo "    </td>";
    echo "  </tr>";
    echo "</table>";

    echo "<table class='tab_cadre_fixe'>";
    echo "  <tr class='tab_bg_2'>";
    echo "    <th colspan='4'>" . __('Áreas e prazo', 'agilizepulsar') . "</th>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right' style='width: 220px;'>";
    echo "      <label for='areas_impactadas'>" . __('Áreas impactadas', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <select id='areas_impactadas' name='areas_impactadas[]' class='form-select' multiple size='6' required>";
    foreach ($areasImpactadas as $area) {
        $value = Html::entities_deep($area);
        echo "        <option value='{$value}'>{$value}</option>";
    }
    echo "      </select>";
    echo "      <div class='section-help'>" . __('Segure Ctrl (ou Cmd) para selecionar mais de uma área.', 'agilizepulsar') . "</div>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='prazo_estimado'>" . __('Prazo estimado', 'agilizepulsar') . "</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <input type='text' id='prazo_estimado' name='prazo_estimado' class='form-control flatpickr-input' placeholder='dd/mm/aaaa'>";
    echo "      <div class='section-help'>" . __('Opcional – define a data prevista para encerramento.', 'agilizepulsar') . "</div>";
    echo "    </td>";
    echo "  </tr>";
    echo "</table>";

    echo "<table class='tab_cadre_fixe'>";
    echo "  <tr class='tab_bg_2'>";
    echo "    <th colspan='4'>" . __('Materiais de apoio', 'agilizepulsar') . "</th>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right' style='width: 220px;'>";
    echo "      <label for='anexos'>" . __('Anexos', 'agilizepulsar') . "</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <input type='file' id='anexos' name='anexos[]' multiple accept='.jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx'>";
    echo "      <div class='section-help'>" . __('Formatos aceitos: imagens, PDF, Word, Excel e PowerPoint (até 100 MB cada).', 'agilizepulsar') . "</div>";
    echo "    </td>";
    echo "  </tr>";
    echo "</table>";

    echo "<div class='form-footer'>";
    echo "  <button type='submit' class='btn btn-primary btn-u'>" . __('Criar campanha', 'agilizepulsar') . "</button>";
    echo "  <a class='btn btn-secondary btn-u' href='{$pluginWeb}/front/campanhas.php'>" . __('Cancelar', 'agilizepulsar') . "</a>";
    echo "</div>";

    echo "</form>";
    echo "</div>";
}
