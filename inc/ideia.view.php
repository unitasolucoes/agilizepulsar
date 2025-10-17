<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova ideia.
 *
 * @param array  $campanhas      Lista de campanhas ativas.
 * @param array  $areas          Lista de áreas impactadas.
 * @param array  $colaboradores  Lista de colaboradores disponíveis.
 * @param string $csrf           Token CSRF.
 */
function plugin_agilizepulsar_render_ideia_form(array $campanhas, array $areas, array $colaboradores, string $csrf): void {
    $pluginWeb = Plugin::getWebDir('agilizepulsar');

    ob_start();
    ?>
    <div class="pulsar-wrap">
        <section class="pulsar-hero card-u">
            <div class="hero-left">
                <h1><?php echo __('Nova Ideia', 'agilizepulsar'); ?></h1>
                <p class="pulsar-muted"><?php echo __('Compartilhe sua ideia e contribua com a inovação da Unitá.', 'agilizepulsar'); ?></p>
            </div>
            <div class="pulsar-actions">
                <a href="<?php echo $pluginWeb; ?>/front/feed.php" class="btn-u ghost">
                    <i class="fa-solid fa-arrow-left"></i>
                    <?php echo __('Voltar ao feed', 'agilizepulsar'); ?>
                </a>
            </div>
        </section>

        <form id="form-nova-ideia" class="pulsar-form" method="post" action="<?php echo $pluginWeb; ?>/front/processar_ideia.php" enctype="multipart/form-data">
            <input type="hidden" name="_glpi_csrf_token" value="<?php echo Html::entities_deep($csrf); ?>">

            <section class="form-section">
                <div class="form-section-header">
                    <h2><i class="fa-solid fa-id-card-clip"></i> <?php echo __('Identificação da Ideia', 'agilizepulsar'); ?></h2>
                </div>
                <div class="card-u form-section-body">
                    <div class="form-group">
                        <label class="form-label required" for="titulo"><?php echo __('Título da Ideia', 'agilizepulsar'); ?></label>
                        <input type="text" class="form-control" id="titulo" name="titulo" maxlength="255" required placeholder="<?php echo __('Ex.: Automatizar fluxo de aprovação', 'agilizepulsar'); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="campanha_id"><?php echo __('Campanha vinculada', 'agilizepulsar'); ?></label>
                        <select class="form-select" id="campanha_id" name="campanha_id" required>
                            <option value=""><?php echo __('Selecione uma campanha', 'agilizepulsar'); ?></option>
                            <?php foreach ($campanhas as $campanha): ?>
                                <option value="<?php echo (int) $campanha['id']; ?>" data-deadline="<?php echo Html::entities_deep($campanha['time_to_resolve'] ?? ''); ?>">
                                    <?php echo Html::entities_deep($campanha['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="campaign-preview" class="campaign-preview is-hidden"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="area_impactada"><?php echo __('Área impactada', 'agilizepulsar'); ?></label>
                        <select class="form-select" id="area_impactada" name="area_impactada" required>
                            <option value=""><?php echo __('Selecione a área impactada', 'agilizepulsar'); ?></option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?php echo Html::entities_deep($area); ?>"><?php echo Html::entities_deep($area); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="descricao"><?php echo __('Descrição da Ideia', 'agilizepulsar'); ?></label>
                        <textarea id="descricao" class="form-control tinymce-editor" name="descricao" required></textarea>
                        <small class="form-help"><?php echo __('Detalhe o problema e a proposta de solução.', 'agilizepulsar'); ?></small>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-header">
                    <h2><i class="fa-solid fa-diagram-project"></i> <?php echo __('Benefícios e Implementação', 'agilizepulsar'); ?></h2>
                </div>
                <div class="card-u form-section-body">
                    <div class="form-group">
                        <label class="form-label required" for="beneficios"><?php echo __('Benefícios esperados', 'agilizepulsar'); ?></label>
                        <textarea id="beneficios" class="form-control tinymce-editor" name="beneficios" required></textarea>
                        <small class="form-help"><?php echo __('Explique o impacto e os ganhos ao implementar a ideia.', 'agilizepulsar'); ?></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="implementacao"><?php echo __('Equipe preparada para implementar?', 'agilizepulsar'); ?></label>
                        <select class="form-select" id="implementacao" name="implementacao">
                            <option value=""><?php echo __('Selecione uma opção', 'agilizepulsar'); ?></option>
                            <option value="Sim"><?php echo __('Sim', 'agilizepulsar'); ?></option>
                            <option value="Não"><?php echo __('Não', 'agilizepulsar'); ?></option>
                            <option value="Talvez"><?php echo __('Talvez', 'agilizepulsar'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <span class="form-label required"><?php echo __('A ideia já existe?', 'agilizepulsar'); ?></span>
                        <div class="form-radio-group">
                            <label class="form-radio">
                                <input type="radio" name="ideia_existente" value="Sim" required>
                                <span><?php echo __('Sim, já existe', 'agilizepulsar'); ?></span>
                            </label>
                            <label class="form-radio">
                                <input type="radio" name="ideia_existente" value="Não" required>
                                <span><?php echo __('Não, é inédita', 'agilizepulsar'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="objetivo_estrategico"><?php echo __('Objetivo estratégico relacionado', 'agilizepulsar'); ?></label>
                        <input type="text" class="form-control" id="objetivo_estrategico" name="objetivo_estrategico" required maxlength="255">
                    </div>

                    <div class="form-group">
                        <span class="form-label required"><?php echo __('Classificação da ideia', 'agilizepulsar'); ?></span>
                        <div class="form-radio-group">
                            <label class="form-radio">
                                <input type="radio" name="classificacao" value="Simples" required>
                                <span><?php echo __('Simples', 'agilizepulsar'); ?></span>
                            </label>
                            <label class="form-radio">
                                <input type="radio" name="classificacao" value="Complexa" required>
                                <span><?php echo __('Complexa', 'agilizepulsar'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-header">
                    <h2><i class="fa-solid fa-paperclip"></i> <?php echo __('Anexos e Autor', 'agilizepulsar'); ?></h2>
                </div>
                <div class="card-u form-section-body">
                    <div class="form-group">
                        <label class="form-label" for="anexos"><?php echo __('Anexos (opcional)', 'agilizepulsar'); ?></label>
                        <div class="file-upload-area">
                            <input type="file" id="anexos" name="anexos[]" class="form-control-file" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                            <button class="btn-file-upload" data-action="select-files"><?php echo __('Selecionar arquivos', 'agilizepulsar'); ?></button>
                            <p class="form-help"><?php echo __('Formatos aceitos: imagens, PDF, Word, Excel e PowerPoint. Tamanho máximo: 100 MB por arquivo.', 'agilizepulsar'); ?></p>
                            <ul id="selected-files" class="file-upload-list"></ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="autor_id"><?php echo __('Autor da ideia (opcional)', 'agilizepulsar'); ?></label>
                        <select class="form-select" id="autor_id" name="autor_id">
                            <option value="0"><?php echo __('Usar meu usuário', 'agilizepulsar'); ?></option>
                            <?php foreach ($colaboradores as $colaborador): ?>
                                <option value="<?php echo (int) $colaborador['id']; ?>">
                                    <?php echo Html::entities_deep(trim(($colaborador['realname'] ?? '') . ' ' . ($colaborador['firstname'] ?? '')) ?: $colaborador['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn-u primary">
                    <i class="fa-solid fa-paper-plane"></i>
                    <?php echo __('Enviar ideia', 'agilizepulsar'); ?>
                </button>
                <a href="<?php echo $pluginWeb; ?>/front/feed.php" class="btn-u ghost"><?php echo __('Cancelar', 'agilizepulsar'); ?></a>
            </div>
        </form>
    </div>
    <?php
    echo ob_get_clean();
}
