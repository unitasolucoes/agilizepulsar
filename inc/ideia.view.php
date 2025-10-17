<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova ideia utilizando componentes padrão do GLPI.
 *
 * @param array  $campanhas      Lista de campanhas ativas.
 * @param array  $areas          Lista de áreas impactadas.
 * @param array  $colaboradores  Lista de colaboradores disponíveis.
 * @param string $csrf           Token CSRF.
 */
function plugin_agilizepulsar_render_ideia_form(array $campanhas, array $areas, array $colaboradores, string $csrf): void {
    $pluginWeb = Plugin::getWebDir('agilizepulsar');

    echo "<div class='center'>";
    echo "<h1>" . __('Nova Ideia', 'agilizepulsar') . "</h1>";
    echo "<form id='form-nova-ideia' method='post' action='{$pluginWeb}/front/processar_ideia.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='_glpi_csrf_token' value='" . Html::entities_deep($csrf) . "'>";

    // Identificação
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr><th colspan='2'>" . __('Identificação da Ideia', 'agilizepulsar') . "</th></tr>";
    echo "<tr class='tab_bg_1'><td class='left' width='30%'><label for='titulo'><span class='red'>*</span> " . __('Título da Ideia', 'agilizepulsar') . "</label></td>";
    echo "<td class='left'><input type='text' id='titulo' name='titulo' size='70' maxlength='255' required></td></tr>";

    echo "<tr class='tab_bg_2'><td class='left'><label for='campanha_id'><span class='red'>*</span> " . __('Campanha vinculada', 'agilizepulsar') . "</label></td><td class='left'>";
    echo "<select id='campanha_id' name='campanha_id' required>";
    echo "<option value=''>" . __('Selecione uma campanha', 'agilizepulsar') . "</option>";
    foreach ($campanhas as $campanha) {
        $id = (int) $campanha['id'];
        $deadline = Html::entities_deep($campanha['time_to_resolve'] ?? '');
        $name = Html::entities_deep($campanha['name']);
        echo "<option value='{$id}' data-deadline='{$deadline}'>{$name}</option>";
    }
    echo "</select><div id='campaign-preview' style='margin-top:10px;display:none;'></div></td></tr>";

    echo "<tr class='tab_bg_1'><td class='left'><label for='area_impactada'><span class='red'>*</span> " . __('Área impactada', 'agilizepulsar') . "</label></td><td class='left'>";
    echo "<select id='area_impactada' name='area_impactada' required>";
    echo "<option value=''>" . __('Selecione a área impactada', 'agilizepulsar') . "</option>";
    foreach ($areas as $area) {
        $value = Html::entities_deep($area);
        echo "<option value='{$value}'>{$value}</option>";
    }
    echo "</select></td></tr>";

    echo "<tr class='tab_bg_2'><td class='left' valign='top'><label for='descricao'><span class='red'>*</span> " . __('Descrição da Ideia', 'agilizepulsar') . "</label></td>";
    echo "<td class='left'><textarea id='descricao' name='descricao' class='tinymce-editor' rows='8' required></textarea></td></tr>";
    echo "</table>";

    // Benefícios e implementação
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr><th colspan='2'>" . __('Benefícios e Implementação', 'agilizepulsar') . "</th></tr>";
    echo "<tr class='tab_bg_1'><td class='left' valign='top'><label for='beneficios'><span class='red'>*</span> " . __('Benefícios esperados', 'agilizepulsar') . "</label></td>";
    echo "<td class='left'><textarea id='beneficios' name='beneficios' class='tinymce-editor' rows='8' required></textarea></td></tr>";

    echo "<tr class='tab_bg_2'><td class='left'><label for='implementacao'>" . __('Equipe preparada para implementar?', 'agilizepulsar') . "</label></td><td class='left'>";
    echo "<select id='implementacao' name='implementacao'>";
    echo "<option value=''>" . __('Selecione uma opção', 'agilizepulsar') . "</option>";
    echo "<option value='Sim'>" . __('Sim', 'agilizepulsar') . "</option>";
    echo "<option value='Não'>" . __('Não', 'agilizepulsar') . "</option>";
    echo "<option value='Talvez'>" . __('Talvez', 'agilizepulsar') . "</option>";
    echo "</select></td></tr>";

    echo "<tr class='tab_bg_1'><td class='left' valign='top'><span class='red'>*</span> " . __('A ideia já existe?', 'agilizepulsar') . "</td><td class='left'>";
    echo "<label class='mr10'><input type='radio' name='ideia_existente' value='Sim' required> " . __('Sim', 'agilizepulsar') . "</label>";
    echo "<label><input type='radio' name='ideia_existente' value='Não' required> " . __('Não', 'agilizepulsar') . "</label>";
    echo "</td></tr>";

    echo "<tr class='tab_bg_2'><td class='left'><label for='objetivo_estrategico'><span class='red'>*</span> " . __('Objetivo estratégico relacionado', 'agilizepulsar') . "</label></td>";
    echo "<td class='left'><input type='text' id='objetivo_estrategico' name='objetivo_estrategico' size='70' maxlength='255' required></td></tr>";

    echo "<tr class='tab_bg_1'><td class='left' valign='top'><span class='red'>*</span> " . __('Classificação da ideia', 'agilizepulsar') . "</td><td class='left'>";
    echo "<label class='mr10'><input type='radio' name='classificacao' value='Simples' required> " . __('Simples', 'agilizepulsar') . "</label>";
    echo "<label><input type='radio' name='classificacao' value='Complexa' required> " . __('Complexa', 'agilizepulsar') . "</label>";
    echo "</td></tr>";
    echo "</table>";

    // Anexos e autor
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr><th colspan='2'>" . __('Anexos e Autor', 'agilizepulsar') . "</th></tr>";
    echo "<tr class='tab_bg_1'><td class='left'><label for='anexos'>" . __('Anexos', 'agilizepulsar') . "</label></td><td class='left'><input type='file' id='anexos' name='anexos[]' multiple></td></tr>";

    echo "<tr class='tab_bg_2'><td class='left'><label for='autor_id'>" . __('Autor da ideia', 'agilizepulsar') . "</label></td><td class='left'>";
    echo "<select id='autor_id' name='autor_id'>";
    echo "<option value='0'>" . __('Usar meu usuário', 'agilizepulsar') . "</option>";
    foreach ($colaboradores as $colaborador) {
        $id = (int) $colaborador['id'];
        $label = trim(($colaborador['realname'] ?? '') . ' ' . ($colaborador['firstname'] ?? ''));
        if ($label === '') {
            $label = $colaborador['name'];
        }
        $label = Html::entities_deep($label);
        echo "<option value='{$id}'>{$label}</option>";
    }
    echo "</select></td></tr>";
    echo "</table>";

    echo "<div class='center'>";
    echo "<button type='submit' class='btn btn-primary'>" . __('Enviar ideia', 'agilizepulsar') . "</button>";
    echo "<a class='btn btn-secondary' href='{$pluginWeb}/front/feed.php'>" . __('Cancelar', 'agilizepulsar') . "</a>";
    echo "</div>";

    echo "</form>";
    echo "</div>";
}
