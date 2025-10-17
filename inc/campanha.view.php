<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova campanha com o layout Pulsar.
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
    echo "<form id='form-nova-campanha' class='card-u' method='post' action='{$pluginWeb}/front/processar_campanha.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='_glpi_csrf_token' value='" . Html::entities_deep($csrf) . "'>";

    echo "<section class='form-section'>";
    echo "  <div class='form-section-header'>";
    echo "    <h2>" . __('Identificação da Campanha', 'agilizepulsar') . "</h2>";
    echo "    <p class='form-help'>" . __('Defina as informações principais desta campanha de ideias.', 'agilizepulsar') . "</p>";
    echo "  </div>";
    echo "  <div class='card-u form-section-body'>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='titulo'>" . __('Título da Campanha', 'agilizepulsar') . "</label>";
    echo "      <input type='text' class='form-control' id='titulo' name='titulo' maxlength='255' required placeholder='" . __('Qual é o nome da campanha?', 'agilizepulsar') . "'>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label' for='campanha_pai_id'>" . __('Campanha pai (opcional)', 'agilizepulsar') . "</label>";
    echo "      <select id='campanha_pai_id' class='form-select' name='campanha_pai_id'>";
    echo "        <option value='0'>" . __('Nenhuma', 'agilizepulsar') . "</option>";
    foreach ($campanhas as $campanha) {
        $id = (int) $campanha['id'];
        $name = Html::entities_deep($campanha['name']);
        echo "        <option value='{$id}'>{$name}</option>";
    }
    echo "      </select>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='descricao'>" . __('Descrição da campanha', 'agilizepulsar') . "</label>";
    echo "      <textarea id='descricao' class='form-control tinymce-editor' name='descricao' rows='8' required placeholder='" . __('Descreva objetivos, contexto e expectativas gerais', 'agilizepulsar') . "'></textarea>";
    echo "    </div>";

    echo "  </div>";
    echo "</section>";

    echo "<section class='form-section'>";
    echo "  <div class='form-section-header'>";
    echo "    <h2>" . __('Estratégia da Campanha', 'agilizepulsar') . "</h2>";
    echo "    <p class='form-help'>" . __('Escolha os públicos, canais e benefícios-chave.', 'agilizepulsar') . "</p>";
    echo "  </div>";
    echo "  <div class='card-u form-section-body'>";

    echo "    <div class='form-group'>";
    echo "      <span class='form-label required'>" . __('Público-alvo', 'agilizepulsar') . "</span>";
    echo "      <div class='form-checkbox-group'>";
    foreach ($publicos as $publico) {
        $value = Html::entities_deep($publico);
        echo "        <label class='form-checkbox'><input type='checkbox' name='publico_alvo[]' value='{$value}'> <span>{$value}</span></label>";
    }
    echo "      </div>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='beneficios'>" . __('Benefícios esperados', 'agilizepulsar') . "</label>";
    echo "      <textarea id='beneficios' class='form-control tinymce-editor' name='beneficios' rows='8' required placeholder='" . __('Detalhe os resultados esperados e indicadores de sucesso', 'agilizepulsar') . "'></textarea>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <span class='form-label required'>" . __('Canais de divulgação', 'agilizepulsar') . "</span>";
    echo "      <div class='form-checkbox-group'>";
    foreach ($canais as $canal) {
        $value = Html::entities_deep($canal);
        echo "        <label class='form-checkbox'><input type='checkbox' name='canais[]' value='{$value}'> <span>{$value}</span></label>";
    }
    echo "      </div>";
    echo "    </div>";

    echo "  </div>";
    echo "</section>";

    echo "<section class='form-section'>";
    echo "  <div class='form-section-header'>";
    echo "    <h2>" . __('Áreas e Prazo', 'agilizepulsar') . "</h2>";
    echo "    <p class='form-help'>" . __('Defina quem será impactado e quando a campanha termina.', 'agilizepulsar') . "</p>";
    echo "  </div>";
    echo "  <div class='card-u form-section-body'>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label required' for='areas_impactadas'>" . __('Áreas impactadas', 'agilizepulsar') . "</label>";
    echo "      <select id='areas_impactadas' class='form-select' name='areas_impactadas[]' multiple size='6' required>";
    foreach ($areasImpactadas as $area) {
        $value = Html::entities_deep($area);
        echo "        <option value='{$value}'>{$value}</option>";
    }
    echo "      </select>";
    echo "      <small class='form-help'>" . __('Segure Ctrl (ou Cmd) para selecionar mais de uma área.', 'agilizepulsar') . "</small>";
    echo "    </div>";

    echo "    <div class='form-group'>";
    echo "      <label class='form-label' for='prazo_estimado'>" . __('Prazo estimado', 'agilizepulsar') . "</label>";
    echo "      <input type='text' id='prazo_estimado' class='form-control flatpickr-input' name='prazo_estimado' placeholder='dd/mm/aaaa'>";
    echo "      <small class='form-help'>" . __('Opcional – define a data prevista para encerramento.', 'agilizepulsar') . "</small>";
    echo "    </div>";

    echo "  </div>";
    echo "</section>";

    echo "<section class='form-section'>";
    echo "  <div class='form-section-header'>";
    echo "    <h2>" . __('Materiais de apoio', 'agilizepulsar') . "</h2>";
    echo "    <p class='form-help'>" . __('Envie arquivos que ajudem a apresentar a campanha.', 'agilizepulsar') . "</p>";
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

    echo "  </div>";
    echo "</section>";

    echo "<div class='form-actions'>";
    echo "  <button type='submit' class='btn btn-primary btn-u'>" . __('Criar campanha', 'agilizepulsar') . "</button>";
    echo "  <a class='btn btn-secondary btn-u' href='{$pluginWeb}/front/campanhas.php'>" . __('Cancelar', 'agilizepulsar') . "</a>";
    echo "</div>";

    echo "</form>";
    echo "</div>";
}
