<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova ideia utilizando a estrutura padrão do GLPI.
 *
 * @param array  $campanhas      Lista de campanhas ativas.
 * @param array  $areas          Lista de áreas impactadas.
 * @param array  $colaboradores  Lista de colaboradores disponíveis.
 * @param string $csrf           Token CSRF.
 */
function plugin_agilizepulsar_render_ideia_form(array $campanhas, array $areas, array $colaboradores, string $csrf): void {
    $pluginWeb = Plugin::getWebDir('agilizepulsar');

    echo "<div class='pulsar-form'>";
    echo "<form id='form-nova-ideia' method='post' action='{$pluginWeb}/front/processar_ideia.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='_glpi_csrf_token' value='" . Html::entities_deep($csrf) . "'>";

    echo "<table class='tab_cadre_fixe'>";
    echo "  <tr class='tab_bg_2'>";
    echo "    <th colspan='4'>" . __('Identificação da Ideia', 'agilizepulsar') . "</th>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right' style='width: 220px;'>";
    echo "      <label for='titulo'>" . __('Título da ideia', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <input type='text' id='titulo' name='titulo' class='form-control' maxlength='255' required>";
    echo "      <div class='section-help'>" . __('Dê um nome memorável para sua ideia.', 'agilizepulsar') . "</div>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='campanha_id'>" . __('Campanha vinculada', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <select id='campanha_id' name='campanha_id' class='form-select' required>";
    echo "        <option value=''>" . __('Selecione uma campanha', 'agilizepulsar') . "</option>";
    foreach ($campanhas as $campanha) {
        $id = (int) $campanha['id'];
        $deadline = Html::entities_deep($campanha['time_to_resolve'] ?? '');
        $name = Html::entities_deep($campanha['name']);
        echo "        <option value='{$id}' data-deadline='{$deadline}'>{$name}</option>";
    }
    echo "      </select>";
    echo "      <div id='campaign-preview' class='campaign-preview' style='display:none;'></div>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='area_impactada'>" . __('Área impactada', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <select id='area_impactada' name='area_impactada' class='form-select' required>";
    echo "        <option value=''>" . __('Selecione a área impactada', 'agilizepulsar') . "</option>";
    foreach ($areas as $area) {
        $value = Html::entities_deep($area);
        echo "        <option value='{$value}'>{$value}</option>";
    }
    echo "      </select>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='descricao'>" . __('Descrição da ideia', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <textarea id='descricao' name='descricao' class='tinymce-editor' rows='10' required></textarea>";
    echo "    </td>";
    echo "  </tr>";
    echo "</table>";

    echo "<table class='tab_cadre_fixe'>";
    echo "  <tr class='tab_bg_2'>";
    echo "    <th colspan='4'>" . __('Benefícios e Implementação', 'agilizepulsar') . "</th>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right' style='width: 220px;'>";
    echo "      <label for='beneficios'>" . __('Benefícios esperados', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <textarea id='beneficios' name='beneficios' class='tinymce-editor' rows='10' required></textarea>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='implementacao'>" . __('Equipe preparada para implementar?', 'agilizepulsar') . "</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <select id='implementacao' name='implementacao' class='form-select'>";
    echo "        <option value=''>" . __('Selecione uma opção', 'agilizepulsar') . "</option>";
    echo "        <option value='Sim'>" . __('Sim', 'agilizepulsar') . "</option>";
    echo "        <option value='Não'>" . __('Não', 'agilizepulsar') . "</option>";
    echo "        <option value='Talvez'>" . __('Talvez', 'agilizepulsar') . "</option>";
    echo "      </select>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>" . __('A ideia já existe?', 'agilizepulsar') . " *</td>";
    echo "    <td colspan='3'>";
    echo "      <label><input type='radio' name='ideia_existente' value='Sim' required> " . __('Sim', 'agilizepulsar') . "</label>&nbsp;&nbsp;";
    echo "      <label><input type='radio' name='ideia_existente' value='Não' required> " . __('Não', 'agilizepulsar') . "</label>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='objetivo_estrategico'>" . __('Objetivo estratégico relacionado', 'agilizepulsar') . " *</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <input type='text' id='objetivo_estrategico' name='objetivo_estrategico' class='form-control' maxlength='255' required>";
    echo "    </td>";
    echo "  </tr>";

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>" . __('Classificação da ideia', 'agilizepulsar') . " *</td>";
    echo "    <td colspan='3'>";
    echo "      <label><input type='radio' name='classificacao' value='Simples' required> " . __('Simples', 'agilizepulsar') . "</label>&nbsp;&nbsp;";
    echo "      <label><input type='radio' name='classificacao' value='Complexa' required> " . __('Complexa', 'agilizepulsar') . "</label>";
    echo "    </td>";
    echo "  </tr>";
    echo "</table>";

    echo "<table class='tab_cadre_fixe'>";
    echo "  <tr class='tab_bg_2'>";
    echo "    <th colspan='4'>" . __('Anexos e autor', 'agilizepulsar') . "</th>";
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

    echo "  <tr class='tab_bg_1'>";
    echo "    <td class='right'>";
    echo "      <label for='autor_id'>" . __('Autor da ideia', 'agilizepulsar') . "</label>";
    echo "    </td>";
    echo "    <td colspan='3'>";
    echo "      <select id='autor_id' name='autor_id' class='form-select'>";
    echo "        <option value='0'>" . __('Usar meu usuário', 'agilizepulsar') . "</option>";
    foreach ($colaboradores as $colaborador) {
        $id = (int) $colaborador['id'];
        $label = trim(($colaborador['realname'] ?? '') . ' ' . ($colaborador['firstname'] ?? ''));
        if ($label === '') {
            $label = $colaborador['name'];
        }
        $label = Html::entities_deep($label);
        echo "        <option value='{$id}'>{$label}</option>";
    }
    echo "      </select>";
    echo "    </td>";
    echo "  </tr>";
    echo "</table>";

    echo "<div class='form-footer'>";
    echo "  <button type='submit' class='btn btn-primary btn-u'>" . __('Enviar ideia', 'agilizepulsar') . "</button>";
    echo "  <a class='btn btn-secondary btn-u' href='{$pluginWeb}/front/feed.php'>" . __('Cancelar', 'agilizepulsar') . "</a>";
    echo "</div>";

    echo "</form>";
    echo "</div>";
}
