<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova campanha.
 *
 * @param array  $campanhasPai Lista de campanhas disponíveis para vínculo opcional.
 * @param array  $areas        Lista de áreas impactadas.
 * @param string $csrf         Token CSRF.
 */
function plugin_agilizepulsar_render_campanha_form(array $campanhasPai, array $areas, string $csrf): void {
    $pluginWeb = Plugin::getWebDir('agilizepulsar');

    ob_start();
    ?>
    <div class="pulsar-wrap">
        <section class="pulsar-hero card-u">
            <div class="hero-left">
                <h1><?php echo __('Nova Campanha', 'agilizepulsar'); ?></h1>
                <p class="pulsar-muted"><?php echo __('Planeje campanhas e conecte as ideias da equipe.', 'agilizepulsar'); ?></p>
            </div>
            <div class="pulsar-actions">
                <a href="<?php echo $pluginWeb; ?>/front/campaign.php" class="btn-u ghost">
                    <i class="fa-solid fa-arrow-left"></i>
                    <?php echo __('Voltar para campanhas', 'agilizepulsar'); ?>
                </a>
            </div>
        </section>

        <form id="form-nova-campanha" class="pulsar-form" method="post" action="<?php echo $pluginWeb; ?>/front/processar_campanha.php" enctype="multipart/form-data">
            <input type="hidden" name="_glpi_csrf_token" value="<?php echo Html::entities_deep($csrf); ?>">

            <section class="form-section">
                <div class="form-section-header">
                    <h2><i class="fa-solid fa-flag"></i> <?php echo __('Identificação da Campanha', 'agilizepulsar'); ?></h2>
                </div>
                <div class="card-u form-section-body">
                    <div class="form-group">
                        <label class="form-label required" for="titulo"><?php echo __('Título da campanha', 'agilizepulsar'); ?></label>
                        <input type="text" class="form-control" id="titulo" name="titulo" maxlength="255" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="campanha_pai_id"><?php echo __('Campanha pai (opcional)', 'agilizepulsar'); ?></label>
                        <select class="form-select" id="campanha_pai_id" name="campanha_pai_id">
                            <option value="0"><?php echo __('Nenhuma', 'agilizepulsar'); ?></option>
                            <?php foreach ($campanhasPai as $campanha): ?>
                                <option value="<?php echo (int) $campanha['id']; ?>"><?php echo Html::entities_deep($campanha['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="descricao"><?php echo __('Descrição detalhada', 'agilizepulsar'); ?></label>
                        <textarea id="descricao" class="form-control tinymce-editor" name="descricao" required></textarea>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-header">
                    <h2><i class="fa-solid fa-bullseye"></i> <?php echo __('Estratégia da Campanha', 'agilizepulsar'); ?></h2>
                </div>
                <div class="card-u form-section-body">
                    <div class="form-group">
                        <span class="form-label"><?php echo __('Público-alvo', 'agilizepulsar'); ?></span>
                        <div class="checkbox-grid">
                            <?php
                            $publicos = ['Beneficiários', 'Potenciais', 'Rede credenciada', 'Corretores', 'Colaboradores'];
                            foreach ($publicos as $publico): ?>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="publico_alvo[]" value="<?php echo Html::entities_deep($publico); ?>">
                                    <span><?php echo Html::entities_deep($publico); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="beneficios"><?php echo __('Benefícios esperados', 'agilizepulsar'); ?></label>
                        <textarea id="beneficios" class="form-control tinymce-editor" name="beneficios" required></textarea>
                    </div>

                    <div class="form-group">
                        <span class="form-label"><?php echo __('Canais de divulgação', 'agilizepulsar'); ?></span>
                        <div class="checkbox-grid">
                            <?php
                            $canais = ['E-mail', 'WhatsApp', 'App Beneficiário', 'Portal', 'Push App', 'Redes Sociais', 'Unidade'];
                            foreach ($canais as $canal): ?>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="canais[]" value="<?php echo Html::entities_deep($canal); ?>">
                                    <span><?php echo Html::entities_deep($canal); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-header">
                    <h2><i class="fa-solid fa-people-group"></i> <?php echo __('Áreas e Prazo', 'agilizepulsar'); ?></h2>
                </div>
                <div class="card-u form-section-body">
                    <div class="form-group">
                        <label class="form-label required" for="areas_impactadas"><?php echo __('Áreas impactadas', 'agilizepulsar'); ?></label>
                        <select class="form-select" id="areas_impactadas" name="areas_impactadas[]" multiple required>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?php echo Html::entities_deep($area); ?>"><?php echo Html::entities_deep($area); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help"><?php echo __('Selecione todas as áreas que devem participar da campanha.', 'agilizepulsar'); ?></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="prazo_estimado"><?php echo __('Prazo estimado de conclusão', 'agilizepulsar'); ?></label>
                        <input type="text" id="prazo_estimado" name="prazo_estimado" class="form-control flatpickr-input" placeholder="dd/mm/aaaa">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="anexos"><?php echo __('Anexos (opcional)', 'agilizepulsar'); ?></label>
                        <div class="file-upload-area">
                            <input type="file" id="anexos" name="anexos[]" class="form-control-file" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                            <button class="btn-file-upload" data-action="select-files"><?php echo __('Selecionar arquivos', 'agilizepulsar'); ?></button>
                            <p class="form-help"><?php echo __('Formatos aceitos: imagens, PDF, Word, Excel e PowerPoint. Tamanho máximo: 100 MB por arquivo.', 'agilizepulsar'); ?></p>
                            <ul id="selected-files" class="file-upload-list"></ul>
                        </div>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn-u primary">
                    <i class="fa-solid fa-flag-checkered"></i>
                    <?php echo __('Cadastrar campanha', 'agilizepulsar'); ?>
                </button>
                <a href="<?php echo $pluginWeb; ?>/front/campaign.php" class="btn-u ghost"><?php echo __('Cancelar', 'agilizepulsar'); ?></a>
            </div>
        </form>
    </div>
    <?php
    echo ob_get_clean();
}
