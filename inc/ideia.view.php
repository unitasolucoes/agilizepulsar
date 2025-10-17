<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza o formulário de nova ideia utilizando um layout inspirado no GLPI.
 *
 * @param array  $campanhas Lista de campanhas ativas.
 * @param array  $areas     Lista de áreas impactadas.
 * @param string $csrf      Token CSRF.
 * @param string $autorNome Nome formatado do autor autenticado.
 */
function plugin_agilizepulsar_render_ideia_form(array $campanhas, array $areas, string $csrf, string $autorNome): void {
    $pluginWeb = Plugin::getWebDir('agilizepulsar');

    ob_start();
    ?>
    <div class="pulsar-form">
        <form id="form-nova-ideia" method="post" action="<?php echo $pluginWeb; ?>/front/processar_ideia.php" enctype="multipart/form-data">
            <input type="hidden" name="_glpi_csrf_token" value="<?php echo Html::entities_deep($csrf); ?>">

            <section class="pulsar-card">
                <div class="pulsar-card__header">
                    <h2><?php echo __('Identificação da ideia', 'agilizepulsar'); ?></h2>
                    <p class="pulsar-card__subtitle"><?php echo __('Conte em poucas palavras qual é a proposta e onde ela se encaixa.', 'agilizepulsar'); ?></p>
                </div>
                <div class="pulsar-card__body">
                    <div class="pulsar-grid">
                        <div class="pulsar-field pulsar-field--full">
                            <label class="pulsar-label required" for="titulo"><?php echo __('Título da ideia', 'agilizepulsar'); ?></label>
                            <input type="text" id="titulo" name="titulo" class="form-control" maxlength="255" required>
                            <span class="pulsar-note"><?php echo __('Escolha um nome curto e objetivo. Esse será o título do ticket.', 'agilizepulsar'); ?></span>
                        </div>

                        <div class="pulsar-field pulsar-field--full">
                            <label class="pulsar-label required" for="campanha_id"><?php echo __('Campanha vinculada', 'agilizepulsar'); ?></label>
                            <select id="campanha_id" name="campanha_id" class="form-select" required>
                                <option value=""><?php echo __('Selecione uma campanha', 'agilizepulsar'); ?></option>
                                <?php foreach ($campanhas as $campanha):
                                    $id = (int) $campanha['id'];
                                    $deadline = Html::entities_deep($campanha['time_to_resolve'] ?? '');
                                    $name = Html::entities_deep($campanha['name']);
                                    ?>
                                    <option value="<?php echo $id; ?>" data-deadline="<?php echo $deadline; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="campaign-preview" class="campaign-preview" style="display:none;"></div>
                        </div>

                        <div class="pulsar-field">
                            <label class="pulsar-label required" for="area_impactada"><?php echo __('Área impactada', 'agilizepulsar'); ?></label>
                            <select id="area_impactada" name="area_impactada" class="form-select" required>
                                <option value=""><?php echo __('Selecione a área impactada', 'agilizepulsar'); ?></option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo Html::entities_deep($area); ?>"><?php echo Html::entities_deep($area); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="pulsar-field pulsar-field--full">
                            <label class="pulsar-label required" for="descricao"><?php echo __('Descrição da ideia', 'agilizepulsar'); ?></label>
                            <textarea id="descricao" name="descricao" class="tinymce-editor" rows="10" required></textarea>
                            <span class="pulsar-note"><?php echo __('Detalhe o problema, a proposta e o impacto esperado.', 'agilizepulsar'); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="pulsar-card">
                <div class="pulsar-card__header">
                    <h2><?php echo __('Planejamento da ideia', 'agilizepulsar'); ?></h2>
                    <p class="pulsar-card__subtitle"><?php echo __('Explique benefícios, objetivos estratégicos e o nível de complexidade.', 'agilizepulsar'); ?></p>
                </div>
                <div class="pulsar-card__body">
                    <div class="pulsar-grid">
                        <div class="pulsar-field pulsar-field--full">
                            <label class="pulsar-label required" for="beneficios"><?php echo __('Benefícios esperados', 'agilizepulsar'); ?></label>
                            <textarea id="beneficios" name="beneficios" class="tinymce-editor" rows="10" required></textarea>
                            <span class="pulsar-note"><?php echo __('Quais resultados a ideia gera para clientes, operação ou negócio?', 'agilizepulsar'); ?></span>
                        </div>

                        <div class="pulsar-field pulsar-field--full">
                            <span class="pulsar-label required"><?php echo __('A ideia já existe?', 'agilizepulsar'); ?></span>
                            <div class="pulsar-options">
                                <label>
                                    <input type="radio" name="ideia_existente" value="Sim" required>
                                    <?php echo __('Sim', 'agilizepulsar'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="ideia_existente" value="Não" required>
                                    <?php echo __('Não', 'agilizepulsar'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="pulsar-field">
                            <label class="pulsar-label required" for="objetivo_estrategico"><?php echo __('Objetivo estratégico relacionado', 'agilizepulsar'); ?></label>
                            <input type="text" id="objetivo_estrategico" name="objetivo_estrategico" class="form-control" maxlength="255" required>
                        </div>

                        <div class="pulsar-field">
                            <span class="pulsar-label required"><?php echo __('Classificação da ideia', 'agilizepulsar'); ?></span>
                            <div class="pulsar-options">
                                <label>
                                    <input type="radio" name="classificacao" value="Simples" required>
                                    <?php echo __('Simples', 'agilizepulsar'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="classificacao" value="Complexa" required>
                                    <?php echo __('Complexa', 'agilizepulsar'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="pulsar-card">
                <div class="pulsar-card__header">
                    <h2><?php echo __('Anexos e autor', 'agilizepulsar'); ?></h2>
                    <p class="pulsar-card__subtitle"><?php echo __('Inclua materiais de apoio e confirme quem está registrando a ideia.', 'agilizepulsar'); ?></p>
                </div>
                <div class="pulsar-card__body">
                    <div class="pulsar-grid">
                        <div class="pulsar-field pulsar-field--full">
                            <div class="pulsar-attachment">
                                <strong><?php echo __('Anexos opcionais', 'agilizepulsar'); ?></strong>
                                <span><?php echo __('Arraste e solte ou selecione arquivos (até 100 MB cada).', 'agilizepulsar'); ?></span>
                                <input type="file" id="anexos" name="anexos[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                            </div>
                            <span class="pulsar-note"><?php echo __('Aceitamos imagens, PDFs, documentos do Office e apresentações.', 'agilizepulsar'); ?></span>
                        </div>

                        <div class="pulsar-field">
                            <span class="pulsar-label"><?php echo __('Autor da ideia', 'agilizepulsar'); ?></span>
                            <span class="pulsar-static-field"><?php echo Html::entities_deep($autorNome); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-u"><?php echo __('Enviar ideia', 'agilizepulsar'); ?></button>
                <a class="btn btn-secondary btn-u" href="<?php echo $pluginWeb; ?>/front/feed.php"><?php echo __('Cancelar', 'agilizepulsar'); ?></a>
            </div>
        </form>
    </div>
    <?php
    echo ob_get_clean();
}
