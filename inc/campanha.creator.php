<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarCampanhaCreator {

    private const MAX_ATTACHMENT_SIZE = 104857600; // 100 MB

    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'
    ];

    public static function createCampanhaTicket(array $dados, array $files = []): array {
        try {
            self::validateBasic($dados);

            $ticketData = self::prepareTicketData($dados);
            $ticket = new Ticket();
            $ticketId = $ticket->add($ticketData);

            if (!$ticketId) {
                throw new RuntimeException(__('Não foi possível criar o ticket da campanha.', 'agilizepulsar'));
            }

            self::addRequesterToTicket($ticketId);
            $attachments = self::processAttachments($ticketId, $files);

            if (class_exists('PluginAgilizepulsarLog')) {
                PluginAgilizepulsarLog::add('campaign_created', Session::getLoginUserID(), [
                    'ticket_id' => $ticketId
                ]);
            }

            return [
                'success'       => true,
                'ticket_id'     => $ticketId,
                'ticket_link'   => Plugin::getWebDir('agilizepulsar') . '/front/campaign.php?id=' . $ticketId,
                'anexos_count'  => $attachments,
                'message'       => __('Campanha criada com sucesso!', 'agilizepulsar')
            ];
        } catch (Throwable $exception) {
            error_log('Plugin Agilizepulsar - Campanha: ' . $exception->getMessage());

            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
    }

    private static function validateBasic(array $dados): void {
        Session::checkLoginUser();

        if (trim($dados['titulo'] ?? '') === '') {
            throw new InvalidArgumentException(__('Informe o título da campanha.', 'agilizepulsar'));
        }

        if (trim($dados['descricao'] ?? '') === '') {
            throw new InvalidArgumentException(__('Informe a descrição da campanha.', 'agilizepulsar'));
        }

        if (trim($dados['beneficios'] ?? '') === '') {
            throw new InvalidArgumentException(__('Informe os benefícios da campanha.', 'agilizepulsar'));
        }
    }

    private static function prepareTicketData(array $dados): array {
        $config = PluginAgilizepulsarConfig::getConfig();
        $campaignCategory = (int) ($config['campaign_category_id'] ?? 152);
        $currentTime = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');

        $prazo = self::convertDeadlineToGlpiFormat($dados['prazo_estimado'] ?? '');
        $content = self::generateTicketContent($dados);

        $ticketType = defined('Ticket::DEMAND_TYPE') ? Ticket::DEMAND_TYPE : 2;

        $ticketData = [
            'name'               => addslashes(trim($dados['titulo'])),
            'content'            => $content,
            'status'             => Ticket::INCOMING,
            'type'               => $ticketType,
            'priority'           => 3,
            'urgency'            => 3,
            'impact'             => 3,
            'entities_id'        => $_SESSION['glpiactive_entity'],
            'users_id_recipient' => Session::getLoginUserID(),
            'itilcategories_id'  => $campaignCategory,
            'date'               => $currentTime,
            'date_mod'           => $currentTime
        ];

        if ($prazo !== null) {
            $ticketData['time_to_resolve'] = $prazo;
        }

        return $ticketData;
    }

    private static function addRequesterToTicket(int $ticketId): void {
        $ticketUser = new Ticket_User();
        $ticketUser->add([
            'tickets_id' => $ticketId,
            'users_id'   => Session::getLoginUserID(),
            'type'       => CommonITILActor::REQUESTER
        ]);
    }

    private static function processAttachments(int $ticketId, array $files): int {
        if (empty($files) || !isset($files['name'])) {
            return 0;
        }

        $count = 0;
        $names = (array) $files['name'];
        $tmpNames = (array) ($files['tmp_name'] ?? []);
        $sizes = (array) ($files['size'] ?? []);
        $errors = (array) ($files['error'] ?? []);
        $types = (array) ($files['type'] ?? []);

        foreach ($names as $index => $name) {
            if (($errors[$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $file = [
                'name'     => $name,
                'tmp_name' => $tmpNames[$index] ?? null,
                'size'     => $sizes[$index] ?? 0,
                'type'     => $types[$index] ?? ''
            ];

            if (!self::isValidAttachment($file)) {
                continue;
            }

            if (self::storeAttachment($ticketId, $file)) {
                $count++;
            }
        }

        return $count;
    }

    private static function isValidAttachment(array $file): bool {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_ATTACHMENT_SIZE) {
            return false;
        }

        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        return true;
    }

    private static function storeAttachment(int $ticketId, array $file): bool {
        try {
            $document = new Document();
            $docId = $document->add([
                'name'                 => addslashes($file['name']),
                'entities_id'          => $_SESSION['glpiactive_entity'],
                'documentcategories_id'=> 0,
                '_filename'            => [$file]
            ]);

            if (!$docId) {
                return false;
            }

            $documentItem = new Document_Item();
            $documentItem->add([
                'documents_id' => $docId,
                'itemtype'     => 'Ticket',
                'items_id'     => $ticketId
            ]);

            return true;
        } catch (Throwable $throwable) {
            error_log('Plugin Agilizepulsar - Falha ao salvar anexo (campanha): ' . $throwable->getMessage());
        }

        return false;
    }

    private static function convertDeadlineToGlpiFormat(string $date): ?string {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        $parsed = DateTime::createFromFormat('d/m/Y', $date);
        if (!$parsed) {
            return null;
        }

        return $parsed->format('Y-m-d 23:59:59');
    }

    private static function sanitizeRichText(?string $value): string {
        $value = $value ?? '';
        return Html::clean($value);
    }

    private static function generateTicketContent(array $dados): string {
        $descricao = self::sanitizeRichText($dados['descricao'] ?? '');
        $beneficios = self::sanitizeRichText($dados['beneficios'] ?? '');

        $publico = isset($dados['publico_alvo']) ? (array) $dados['publico_alvo'] : [];
        $canais = isset($dados['canais']) ? (array) $dados['canais'] : [];
        $areas = isset($dados['areas_impactadas']) ? (array) $dados['areas_impactadas'] : [];
        $prazo = trim($dados['prazo_estimado'] ?? '') !== '' ? Html::clean($dados['prazo_estimado']) : __('Não informado', 'agilizepulsar');

        ob_start();
        ?>
        <h2><?php echo __('Nova Campanha de Ideias', 'agilizepulsar'); ?></h2>

        <table class="tab_cadre">
            <tr>
                <th><?php echo __('Descrição da campanha', 'agilizepulsar'); ?></th>
            </tr>
            <tr>
                <td><?php echo $descricao; ?></td>
            </tr>
        </table>

        <br>

        <table class="tab_cadre">
            <tr>
                <th><?php echo __('Benefícios esperados', 'agilizepulsar'); ?></th>
            </tr>
            <tr>
                <td><?php echo $beneficios; ?></td>
            </tr>
        </table>

        <br>

        <table class="tab_cadre">
            <tr>
                <th><?php echo __('Público-alvo', 'agilizepulsar'); ?></th>
                <td><?php echo Html::clean(implode(', ', $publico)); ?></td>
            </tr>
            <tr>
                <th><?php echo __('Canais de divulgação', 'agilizepulsar'); ?></th>
                <td><?php echo Html::clean(implode(', ', $canais)); ?></td>
            </tr>
        </table>

        <br>

        <table class="tab_cadre">
            <tr>
                <th><?php echo __('Áreas impactadas', 'agilizepulsar'); ?></th>
                <td><?php echo Html::clean(implode(', ', $areas)); ?></td>
            </tr>
            <tr>
                <th><?php echo __('Prazo estimado', 'agilizepulsar'); ?></th>
                <td><?php echo $prazo; ?></td>
            </tr>
        </table>
        <?php
        return ob_get_clean();
    }
}
