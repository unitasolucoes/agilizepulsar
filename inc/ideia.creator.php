<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarIdeiaCreator {

    private const MAX_ATTACHMENT_SIZE = 104857600; // 100 MB

    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'
    ];

    /**
     * @param array $dados Dados enviados pelo formulário
     * @param array $files Arquivos recebidos em $_FILES['anexos']
     *
     * @return array
     */
    public static function createIdeiaTicket(array $dados, array $files = []): array {
        try {
            self::validateBasic($dados);

            $ticketData = self::prepareTicketData($dados);
            $ticket = new Ticket();
            $ticketId = $ticket->add($ticketData);

            if (!$ticketId) {
                throw new RuntimeException(__('Não foi possível criar o ticket da ideia.', 'agilizepulsar'));
            }

            self::addRequesterToTicket($ticketId);
            $attachments = self::processAttachments($ticketId, $files);

            if (!empty($dados['campanha_id'])) {
                PluginAgilizepulsarIdeaCampaign::linkIdeaToCampaign(
                    $ticketId,
                    (int) $dados['campanha_id'],
                    Session::getLoginUserID()
                );
            }

            if (class_exists('PluginAgilizepulsarUserPoints')) {
                PluginAgilizepulsarUserPoints::addPoints(Session::getLoginUserID(), 'submitted_idea', $ticketId, false);
            }

            if (class_exists('PluginAgilizepulsarLog')) {
                PluginAgilizepulsarLog::add('idea_created', Session::getLoginUserID(), [
                    'ticket_id'   => $ticketId,
                    'campaign_id' => (int) ($dados['campanha_id'] ?? 0)
                ]);
            }

            return [
                'success'       => true,
                'ticket_id'     => $ticketId,
                'ticket_link'   => Plugin::getWebDir('agilizepulsar') . '/front/idea.php?id=' . $ticketId,
                'anexos_count'  => $attachments,
                'message'       => __('Ideia criada com sucesso!', 'agilizepulsar')
            ];
        } catch (Throwable $exception) {
            error_log('Plugin Agilizepulsar - Ideia: ' . $exception->getMessage());

            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
    }

    private static function validateBasic(array $dados): void {
        Session::checkLoginUser();

        $titulo = trim($dados['titulo'] ?? '');
        $descricao = trim($dados['descricao'] ?? '');
        $campanhaId = (int) ($dados['campanha_id'] ?? 0);

        if ($titulo === '') {
            throw new InvalidArgumentException(__('Informe o título da ideia.', 'agilizepulsar'));
        }

        if ($descricao === '') {
            throw new InvalidArgumentException(__('Informe a descrição da ideia.', 'agilizepulsar'));
        }

        if ($campanhaId <= 0) {
            throw new InvalidArgumentException(__('Selecione uma campanha válida.', 'agilizepulsar'));
        }

        if (!PluginAgilizepulsarTicket::isCampaign($campanhaId)) {
            throw new InvalidArgumentException(__('A campanha informada é inválida.', 'agilizepulsar'));
        }
    }

    private static function prepareTicketData(array $dados): array {
        $config = PluginAgilizepulsarConfig::getConfig();
        $ideaCategory = (int) ($config['idea_category_id'] ?? 153);
        $currentTime = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');

        $campanhaInfo = self::getCampanhaInfo((int) ($dados['campanha_id'] ?? 0));
        $content = self::generateTicketContent($dados, $campanhaInfo);

        $ticketType = defined('Ticket::DEMAND_TYPE') ? Ticket::DEMAND_TYPE : 2;

        return [
            'name'               => addslashes(trim($dados['titulo'])),
            'content'            => $content,
            'status'             => Ticket::INCOMING,
            'type'               => $ticketType,
            'priority'           => 3,
            'urgency'            => 3,
            'impact'             => 3,
            'entities_id'        => $_SESSION['glpiactive_entity'],
            'users_id_recipient' => Session::getLoginUserID(),
            'itilcategories_id'  => $ideaCategory,
            'date'               => $currentTime,
            'date_mod'           => $currentTime
        ];
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
            error_log('Plugin Agilizepulsar - Falha ao salvar anexo: ' . $throwable->getMessage());
        }

        return false;
    }

    private static function getCampanhaInfo(int $campanhaId): array {
        if ($campanhaId <= 0) {
            return [];
        }

        $ticket = new Ticket();
        if (!$ticket->getFromDB($campanhaId)) {
            return [];
        }

        return $ticket->fields;
    }

    private static function sanitizeRichText(?string $value): string {
        $value = $value ?? '';
        return Html::clean($value);
    }

    private static function generateTicketContent(array $dados, array $campanhaInfo): string {
        $descricao = self::sanitizeRichText($dados['descricao'] ?? '');
        $beneficios = self::sanitizeRichText($dados['beneficios'] ?? '');
        $implementacao = Html::clean($dados['implementacao'] ?? '');
        $ideiaExistente = Html::clean($dados['ideia_existente'] ?? '');
        $objetivo = Html::clean($dados['objetivo_estrategico'] ?? '');
        $classificacao = Html::clean($dados['classificacao'] ?? '');
        $areaImpactada = Html::clean($dados['area_impactada'] ?? '');

        $campanhaNome = $campanhaInfo['name'] ?? __('Campanha não encontrada', 'agilizepulsar');
        $campanhaPrazo = $campanhaInfo['time_to_resolve'] ?? null;
        $campanhaPrazo = $campanhaPrazo ? Html::convDateTime($campanhaPrazo) : __('Não informado', 'agilizepulsar');

        ob_start();
        ?>
        <h2><?php echo __('Nova Ideia', 'agilizepulsar'); ?></h2>

        <table class="tab_cadre">
            <tr>
                <th><?php echo __('Campanha', 'agilizepulsar'); ?></th>
                <td>#<?php echo (int) ($campanhaInfo['id'] ?? 0); ?> - <?php echo Html::clean($campanhaNome); ?></td>
            </tr>
            <tr>
                <th><?php echo __('Área impactada', 'agilizepulsar'); ?></th>
                <td><?php echo $areaImpactada; ?></td>
            </tr>
            <tr>
                <th><?php echo __('Prazo da campanha', 'agilizepulsar'); ?></th>
                <td><?php echo Html::clean($campanhaPrazo); ?></td>
            </tr>
        </table>

        <br>

        <table class="tab_cadre">
            <tr>
                <th><?php echo __('Descrição da ideia', 'agilizepulsar'); ?></th>
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
                <th><?php echo __('Implementação', 'agilizepulsar'); ?></th>
                <td><?php echo $implementacao !== '' ? $implementacao : __('Não informado', 'agilizepulsar'); ?></td>
            </tr>
            <tr>
                <th><?php echo __('Ideia existente?', 'agilizepulsar'); ?></th>
                <td><?php echo $ideiaExistente; ?></td>
            </tr>
            <tr>
                <th><?php echo __('Objetivo estratégico', 'agilizepulsar'); ?></th>
                <td><?php echo $objetivo; ?></td>
            </tr>
            <tr>
                <th><?php echo __('Classificação', 'agilizepulsar'); ?></th>
                <td><?php echo $classificacao; ?></td>
            </tr>
        </table>
        <?php
        return ob_get_clean();
    }
}
