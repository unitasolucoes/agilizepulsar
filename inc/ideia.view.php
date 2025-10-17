<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova ideia utilizando o layout personalizado do Pulsar.
 *
 * @param array  $campanhas      Lista de campanhas ativas.
 * @param array  $areas          Lista de áreas impactadas.
 * @param array  $colaboradores  Lista de colaboradores disponíveis.
 * @param string $csrf           Token CSRF.
 */
function plugin_agilizepulsar_render_ideia_form(array $campanhas, array $areas, array $colaboradores, string $csrf): void {
    $pluginWeb = Plugin::getWebDir('agilizepulsar');

    echo "<div class='pulsar-form'>";
    echo "<form id='form-nova-ideia' class='card-u' method='post' action='{$pluginWeb}/front/processar_ideia.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='_glpi_csrf_token' value='" . Html::entities_deep($csrf) . "'>";

    echo "<section class='form-section'>";
    echo "  <div class='form-section-header'>";
    echo "    <h2>" . __('Identificação da Ideia', 'agilizepulsar') . "</h2>";
    echo "    <p class='form-help'>" . __('Conte-nos detalhes essenciais para avaliarmos sua ideia.', 'agilizepulsar') . "</p>";
    echo "  </div>";
    echo "  <div class='card-u form-section-body'>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='titulo'>" . __('Título da Ideia', 'agilizepulsar') . "</label>";
    echo "      <input type='text' class='form-control' id='titulo' name='titulo' maxlength='255' required placeholder='" . __('Dê um nome memorável para sua ideia', 'agilizepulsar') . "'>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='campanha_id'>" . __('Campanha vinculada', 'agilizepulsar') . "</label>";
    echo "      <select id='campanha_id' class='form-select' name='campanha_id' required>";
    echo "        <option value=''>" . __('Selecione uma campanha', 'agilizepulsar') . "</option>";
    foreach ($campanhas as $campanha) {
        $id = (int) $campanha['id'];
        $deadline = Html::entities_deep($campanha['time_to_resolve'] ?? '');
        $name = Html::entities_deep($campanha['name']);
        echo "        <option value='{$id}' data-deadline='{$deadline}'>{$name}</option>";
    }
    echo "      </select>";
    echo "      <div id='campaign-preview' class='campaign-preview' style='display:none;'></div>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='area_impactada'>" . __('Área impactada', 'agilizepulsar') . "</label>";
    echo "      <select id='area_impactada' class='form-select' name='area_impactada' required>";
    echo "        <option value=''>" . __('Selecione a área impactada', 'agilizepulsar') . "</option>";
    foreach ($areas as $area) {
        $value = Html::entities_deep($area);
        echo "        <option value='{$value}'>{$value}</option>";
    }
    echo "      </select>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='descricao'>" . __('Descrição da ideia', 'agilizepulsar') . "</label>";
    echo "      <textarea id='descricao' class='form-control tinymce-editor' name='descricao' rows='8' required placeholder='" . __('Explique o contexto, o problema e a proposta de solução', 'agilizepulsar') . "'></textarea>";
    echo "    </div>";

    echo "  </div>";
    echo "</section>";

    echo "<section class='form-section'>";
    echo "  <div class='form-section-header'>";
    echo "    <h2>" . __('Benefícios e Implementação', 'agilizepulsar') . "</h2>";
    echo "    <p class='form-help'>" . __('Compartilhe o valor gerado, viabilidade e alinhamento estratégico.', 'agilizepulsar') . "</p>";
    echo "  </div>";
    echo "  <div class='card-u form-section-body'>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='beneficios'>" . __('Benefícios esperados', 'agilizepulsar') . "</label>";
    echo "      <textarea id='beneficios' class='form-control tinymce-editor' name='beneficios' rows='8' required placeholder='" . __('Liste os ganhos previstos com a implementação da ideia', 'agilizepulsar') . "'></textarea>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label' for='implementacao'>" . __('Equipe preparada para implementar?', 'agilizepulsar') . "</label>";
    echo "      <select id='implementacao' class='form-select' name='implementacao'>";
    echo "        <option value=''>" . __('Selecione uma opção', 'agilizepulsar') . "</option>";
    echo "        <option value='Sim'>" . __('Sim', 'agilizepulsar') . "</option>";
    echo "        <option value='Não'>" . __('Não', 'agilizepulsar') . "</option>";
    echo "        <option value='Talvez'>" . __('Talvez', 'agilizepulsar') . "</option>";
    echo "      </select>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <span class='form-label required'>" . __('A ideia já existe?', 'agilizepulsar') . "</span>";
    echo "      <div class='form-radio-group'>";
    echo "        <label class='form-radio'><input type='radio' name='ideia_existente' value='Sim' required> <span>" . __('Sim', 'agilizepulsar') . "</span></label>";
    echo "        <label class='form-radio'><input type='radio' name='ideia_existente' value='Não' required> <span>" . __('Não', 'agilizepulsar') . "</span></label>";
    echo "      </div>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='objetivo_estrategico'>" . __('Objetivo estratégico relacionado', 'agilizepulsar') . "</label>";
    echo "      <input type='text' class='form-control' id='objetivo_estrategico' name='objetivo_estrategico' maxlength='255' required placeholder='" . __('Como a ideia contribui para a estratégia da empresa?', 'agilizepulsar') . "'>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <span class='form-label required'>" . __('Classificação da ideia', 'agilizepulsar') . "</span>";
    echo "      <div class='form-radio-group'>";
    echo "        <label class='form-radio'><input type='radio' name='classificacao' value='Simples' required> <span>" . __('Simples', 'agilizepulsar') . "</span></label>";
    echo "        <label class='form-radio'><input type='radio' name='classificacao' value='Complexa' required> <span>" . __('Complexa', 'agilizepulsar') . "</span></label>";
    echo "      </div>";
    echo "    </div>";

    echo "  </div>";
    echo "</section>";

    echo "<section class='form-section'>";
    echo "  <div class='form-section-header'>";
    echo "    <h2>" . __('Anexos e Autor', 'agilizepulsar') . "</h2>";
    echo "    <p class='form-help'>" . __('Envie materiais complementares e ajuste o autor, se necessário.', 'agilizepulsar') . "</p>";
    echo "  </div>";
    echo "  <div class='card-u form-section-body'>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label' for='anexos'>" . __('Anexos', 'agilizepulsar') . "</label>";
    echo "      <div class='file-upload-area'>";
    echo "        <p class='form-help'>" . __('Formatos aceitos: imagens, PDF, Word, Excel e PowerPoint (até 100 MB cada).', 'agilizepulsar') . "</p>";
    echo "        <button class='btn-file-upload' data-action='select-files'>" . __('Selecionar arquivos', 'agilizepulsar') . "</button>";
    echo "        <input type='file' id='anexos' class='form-control-file' name='anexos[]' multiple accept='.jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx'>";
    echo "      </div>";
    echo "      <ul id='selected-files' class='file-upload-list'></ul>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label' for='autor_id'>" . __('Autor da ideia', 'agilizepulsar') . "</label>";
    echo "      <select id='autor_id' class='form-select' name='autor_id'>";
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
    echo "    </div>";

    echo "  </div>";
    echo "</section>";

    echo "<div class='form-actions'>";
    echo "  <button type='submit' class='btn btn-primary btn-u'>" . __('Enviar ideia', 'agilizepulsar') . "</button>";
    echo "  <a class='btn btn-secondary btn-u' href='{$pluginWeb}/front/feed.php'>" . __('Cancelar', 'agilizepulsar') . "</a>";
    echo "</div>";

    echo "</form>";
    echo "</div>";
}
