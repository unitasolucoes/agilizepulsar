<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova campanha utilizando um layout inspirado no GLPI.
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

    ob_start();
    ?>
    <div class="pulsar-form">
        <form id="form-nova-campanha" method="post" action="<?php echo $pluginWeb; ?>/front/processar_campanha.php" enctype="multipart/form-data">
            <input type="hidden" name="_glpi_csrf_token" value="<?php echo Html::entities_deep($csrf); ?>">

            <section class="pulsar-card">
                <div class="pulsar-card__header">
                    <h2><?php echo __('Informações da campanha', 'agilizepulsar'); ?></h2>
                    <p class="pulsar-card__subtitle"><?php echo __('Defina o título, a hierarquia e descreva a iniciativa.', 'agilizepulsar'); ?></p>
                </div>
                <div class="pulsar-card__body">
                    <div class="pulsar-grid">
                        <div class="pulsar-field">
                            <label class="pulsar-label required" for="titulo"><?php echo __('Título da campanha', 'agilizepulsar'); ?></label>
                            <input type="text" id="titulo" name="titulo" class="form-control" maxlength="255" required>
                        </div>

                        <div class="pulsar-field">
                            <label class="pulsar-label" for="campanha_pai_id"><?php echo __('Campanha pai (opcional)', 'agilizepulsar'); ?></label>
                            <select id="campanha_pai_id" name="campanha_pai_id" class="form-select">
                                <option value="0"><?php echo __('Nenhuma', 'agilizepulsar'); ?></option>
                                <?php foreach ($campanhas as $campanha):
                                    $id = (int) $campanha['id'];
                                    $name = Html::entities_deep($campanha['name']);
                                    ?>
                                    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="pulsar-field pulsar-field--full">
                            <label class="pulsar-label required" for="descricao"><?php echo __('Descrição da campanha', 'agilizepulsar'); ?></label>
                            <textarea id="descricao" name="descricao" class="tinymce-editor" rows="10" required></textarea>
                            <span class="pulsar-note"><?php echo __('Descreva objetivos, escopo e contexto geral.', 'agilizepulsar'); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="pulsar-card">
                <div class="pulsar-card__header">
                    <h2><?php echo __('Estratégia de divulgação', 'agilizepulsar'); ?></h2>
                    <p class="pulsar-card__subtitle"><?php echo __('Selecione os públicos atendidos, canais utilizados e benefícios esperados.', 'agilizepulsar'); ?></p>
                </div>
                <div class="pulsar-card__body">
                    <div class="pulsar-grid">
                        <div class="pulsar-field pulsar-field--full">
                            <span class="pulsar-label required"><?php echo __('Público-alvo', 'agilizepulsar'); ?></span>
                            <div class="pulsar-options">
                                <?php foreach ($publicos as $publico): ?>
                                    <label>
                                        <input type="checkbox" name="publico_alvo[]" value="<?php echo Html::entities_deep($publico); ?>">
                                        <?php echo Html::entities_deep($publico); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="pulsar-field pulsar-field--full">
                            <label class="pulsar-label required" for="beneficios"><?php echo __('Benefícios esperados', 'agilizepulsar'); ?></label>
                            <textarea id="beneficios" name="beneficios" class="tinymce-editor" rows="10" required></textarea>
                            <span class="pulsar-note"><?php echo __('Qual impacto positivo a campanha pretende alcançar?', 'agilizepulsar'); ?></span>
                        </div>

                        <div class="pulsar-field pulsar-field--full">
                            <span class="pulsar-label required"><?php echo __('Canais de divulgação', 'agilizepulsar'); ?></span>
                            <div class="pulsar-options">
                                <?php foreach ($canais as $canal): ?>
                                    <label>
                                        <input type="checkbox" name="canais[]" value="<?php echo Html::entities_deep($canal); ?>">
                                        <?php echo Html::entities_deep($canal); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="pulsar-card">
                <div class="pulsar-card__header">
                    <h2><?php echo __('Áreas e prazo', 'agilizepulsar'); ?></h2>
                    <p class="pulsar-card__subtitle"><?php echo __('Defina quem participa e quando a campanha deve ser concluída.', 'agilizepulsar'); ?></p>
                </div>
                <div class="pulsar-card__body">
                    <div class="pulsar-grid">
                        <div class="pulsar-field">
                            <label class="pulsar-label required" for="areas_impactadas"><?php echo __('Áreas impactadas', 'agilizepulsar'); ?></label>
                            <select id="areas_impactadas" name="areas_impactadas[]" class="form-select" multiple size="6" required>
                                <?php foreach ($areasImpactadas as $area): ?>
                                    <option value="<?php echo Html::entities_deep($area); ?>"><?php echo Html::entities_deep($area); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="pulsar-note"><?php echo __('Use Ctrl (ou Cmd) para marcar mais de uma área.', 'agilizepulsar'); ?></span>
                        </div>

                        <div class="pulsar-field">
                            <label class="pulsar-label" for="prazo_estimado"><?php echo __('Prazo estimado', 'agilizepulsar'); ?></label>
                            <input type="text" id="prazo_estimado" name="prazo_estimado" class="form-control flatpickr-input" placeholder="dd/mm/aaaa">
                            <span class="pulsar-note"><?php echo __('Opcional – defina a data prevista para encerramento.', 'agilizepulsar'); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="pulsar-card">
                <div class="pulsar-card__header">
                    <h2><?php echo __('Materiais de apoio', 'agilizepulsar'); ?></h2>
                    <p class="pulsar-card__subtitle"><?php echo __('Anexe peças gráficas, planilhas ou apresentações relacionadas.', 'agilizepulsar'); ?></p>
                </div>
                <div class="pulsar-card__body">
                    <div class="pulsar-field pulsar-field--full">
                        <div class="pulsar-attachment">
                            <strong><?php echo __('Anexos opcionais', 'agilizepulsar'); ?></strong>
                            <span><?php echo __('Arraste e solte ou selecione arquivos (até 100 MB cada).', 'agilizepulsar'); ?></span>
                            <input type="file" id="anexos" name="anexos[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                        </div>
                        <span class="pulsar-note"><?php echo __('Aceitamos imagens, PDFs, documentos do Office e apresentações.', 'agilizepulsar'); ?></span>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-u"><?php echo __('Criar campanha', 'agilizepulsar'); ?></button>
                <a class="btn btn-secondary btn-u" href="<?php echo $pluginWeb; ?>/front/campanhas.php"><?php echo __('Cancelar', 'agilizepulsar'); ?></a>
            </div>
        </form>
    </div>
    <?php
    echo ob_get_clean();
}
