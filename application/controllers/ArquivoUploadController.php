<?php

require_once("../application/business/ArquivoUploadBusiness.php");

class ArquivoUploadController extends Controller {

    public function __construct(\Zend_Controller_Request_Abstract $request, \Zend_Controller_Response_Abstract $response, array $invokeArgs = array()) {
        parent::__construct($request, $response, $invokeArgs);
    }

    public function init() {
        parent::init();
    }

    // GET: /arquivo-upload/descarregar/id/[0-9]/modo/[fisico] | [virtural]
    //
    // Slice 05 do projeto migracao-arquivo-upload-s3:
    //  - Se a linha tem `arquivo_upload_s3_key`, pede presigned URL ao Laravel via
    //    cURL com X-API-Key (escopo empresa/contrato da sessão do cliente) e
    //    redireciona o cliente direto pro S3.
    //  - Senão, mantém o caminho legado (echo do BLOB pelo Business) — vale enquanto
    //    o backfill não migrar os BLOBs antigos.
    public function descarregarAction() {
        #self::$_habilitarRegistrarLog = false;
        $id = (int) $this->_getParam('id', 0);
        $modo = strtolower($this->_getParam('modo', 'virtual'));
        $this->_desabilitarTodoCarregamentoDeVisualizacao();
        try {
            if ($id == 0)
                throw new Exception('O parâmetro de identificação não foi atribuído ou não está em tipo de dado válido!');
            if ($modo == 'virtual') {
                $s3Key = $this->_lerS3KeyDoBanco($id);
                if ($s3Key === null) {
                    // Linha inexistente ou soft-deletada (slice 07): devolve 404
                    // limpo em vez de deixar o Business lançar exception → 500.
                    $this->getResponse()->setHttpResponseCode(404);
                    return;
                }
                // Slice 08 — hardening IDOR: valida que o cliente logado tem
                // direito ao arquivo (empresa+contrato da sessão batem com a
                // cadeia anx_proc → procedimento → prontuario). Cobre tanto o
                // caminho S3 quanto o BLOB legado. O Laravel já valida no caminho
                // S3, mas defesa em profundidade evita a viagem desnecessária e
                // fecha o BLOB também.
                $empresaId  = isset($_SESSION['empresa']['empresa_id']) ? (int) $_SESSION['empresa']['empresa_id'] : 0;
                $contratoId = isset($_SESSION['contrato_id']) ? (int) $_SESSION['contrato_id'] : 0;
                if (!$this->_arquivoUploadPertenceAoEscopo($id, $empresaId, $contratoId)) {
                    $this->getResponse()->setHttpResponseCode(403);
                    return;
                }
                if ($s3Key !== '') {
                    $url = $this->_pegarUrlPresignedDoLaravel($id);
                    if ($url === null) {
                        $this->getResponse()->setHttpResponseCode(502);
                        return;
                    }
                    header('Location: ' . $url);
                    exit(0);
                }
                ArquivoUploadBusiness::descarregarArquivoArmazenadoEmBancoIdentificandoPeloId($id);
            }
            #self::$_habilitarRegistrarLog = true;
            #$this->_log->logDetalhe = 'Download de arquivo armazenado em banco de dados';
            #$this->_log->logTabelaNome = 'arquivo_upload';
            #$this->_log->logTabelaNome = 'arquivo_upload_id';
            #$this->_log->logTabelaColunaValor = $id;
        } catch (Exception $exc) {
            throw $exc;
        }
    }

    /**
     * SELECT cirúrgico só do `arquivo_upload_s3_key` (sem carregar o BLOB).
     * Filtra `arquivo_upload_status = 0` pra ignorar linhas inativas.
     *
     * Retorno (slice 07 — robustez):
     *   - null  → linha inexistente ou inativa (caller deve devolver 404).
     *   - ''    → existe + ativa, mas sem s3_key (caller cai no caminho BLOB).
     *   - 'xxx' → existe + ativa + s3_key populada (caller faz redirect 302 via Laravel).
     * Em erro de DB, também devolve null por defensividade (vira 404, melhor que 500).
     */
    private function _lerS3KeyDoBanco($arquivoUploadId) {
        try {
            $cnx = Zend_Db_Table::getDefaultAdapter();
            $stmt = $cnx->prepare('SELECT arquivo_upload_s3_key FROM arquivo_upload WHERE arquivo_upload_id = ? AND arquivo_upload_status = 0');
            $stmt->execute(array((int) $arquivoUploadId));
            $row = $stmt->fetch();
            if (!is_array($row)) {
                return null;
            }
            return isset($row['arquivo_upload_s3_key']) ? (string) $row['arquivo_upload_s3_key'] : '';
        } catch (Exception $exc) {
            error_log('arquivo-upload descarregar: falha ao ler s3_key id=' . $arquivoUploadId . ' err=' . $exc->getMessage());
            return null;
        }
    }

    /**
     * Slice 08 — gate de escopo (defesa em profundidade). Verifica se o
     * `arquivo_upload_id` requisitado pertence ao escopo do cliente logado
     * (empresa+contrato da sessão), via JOIN reverso até `prontuario`.
     *
     * Retorna true se existe pelo menos uma cadeia `anx_proc → procedimento →
     * prontuario` ativa cujo `fk_empresa_id`+`fk_contrato_id` batem. Retorna
     * false se nenhuma cadeia bate (gate fechado → 403) ou se a query falha
     * (defensividade: prefere bloquear download a deixar passar errado).
     */
    private function _arquivoUploadPertenceAoEscopo($arquivoUploadId, $empresaId, $contratoId) {
        if ((int) $arquivoUploadId <= 0 || (int) $empresaId <= 0 || (int) $contratoId <= 0) {
            return false;
        }
        $sql = 'SELECT 1
                FROM arquivo_upload au
                JOIN anx_proc ap        ON ap.fk_arquivo_upload_id = au.arquivo_upload_id
                                       AND ap.anx_proc_status = 0
                JOIN procedimento pr    ON pr.procedimento_id      = ap.fk_procedimento_id
                JOIN prontuario p       ON p.prontuario_id         = pr.fk_prontuario_id
                WHERE au.arquivo_upload_id = ?
                  AND p.fk_empresa_id      = ?
                  AND p.fk_contrato_id     = ?
                LIMIT 1';
        try {
            $cnx = Zend_Db_Table::getDefaultAdapter();
            $stmt = $cnx->prepare($sql);
            $stmt->execute(array((int) $arquivoUploadId, (int) $empresaId, (int) $contratoId));
            return (bool) $stmt->fetchColumn();
        } catch (Exception $exc) {
            error_log('arquivo-upload escopo: falha ao validar id=' . $arquivoUploadId . ' err=' . $exc->getMessage());
            return false;
        }
    }

    /**
     * GET em /api/portal/arquivo-upload/{id}/url no Laravel, autenticando via
     * X-API-Key/X-API-Secret (cliente externo, sem SSO). Escopo empresa+contrato
     * vem da sessão do cliente; Laravel valida cruzado via JOIN reverso
     * `arquivo_upload → anx_proc → procedimento → prontuario`.
     */
    private function _pegarUrlPresignedDoLaravel($arquivoUploadId) {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $opts = $bootstrap ? $bootstrap->getOptions() : array();
        $cfg = isset($opts['portal']['arquivo_upload']['api']) ? $opts['portal']['arquivo_upload']['api'] : array();

        if (empty($cfg['url']) || empty($cfg['key']) || empty($cfg['secret'])) {
            error_log('arquivo-upload descarregar: config portal.arquivo_upload.api ausente em application.ini');
            return null;
        }

        $empresaId  = isset($_SESSION['empresa']['empresa_id']) ? (int) $_SESSION['empresa']['empresa_id'] : 0;
        $contratoId = isset($_SESSION['contrato_id']) ? (int) $_SESSION['contrato_id'] : 0;

        $endpoint = rtrim($cfg['url'], '/') . '/' . (int) $arquivoUploadId . '/url'
                  . '?empresa_id=' . $empresaId
                  . '&contrato_id=' . $contratoId;

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'X-API-Key: ' . $cfg['key'],
            'X-API-Secret: ' . $cfg['secret'],
        ));
        $body = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($http !== 200 || empty($body)) {
            error_log('arquivo-upload presigned falhou (http ' . $http . ', err=' . $err . ') arquivo_upload_id=' . $arquivoUploadId);
            return null;
        }

        $json = json_decode($body, true);
        return isset($json['url']) ? $json['url'] : null;
    }

    /**
     * Faz o upload de um arquivo.</br>
     * POST: /arquivo-upload/carregar/layout-upload/[api|form]
     * @throws Exception
     */
    public function carregarAction() {
        $resposta = array(
            'dh_ini_proc' => date('Y-m-d H:i:s'),
            'arquivo_upload_id' => null,
            'upload_executado' => false,
            'status' => 'SUCESSO',
            'mensagem' => null
        );
        $this->_desabilitarTodoCarregamentoDeVisualizacao();

        if ($this->getRequest()->isPost()) {
            $anexo = (isset($_FILES['file']) && is_array($_FILES['file']) == true) ? $_FILES['file'] : null;
            $codigo = $this->_getParam('codigoControle');
            $observacao = $this->_getParam('observacao');
            $descricao = $this->_getParam('descricao');
            $status = (int) $this->_getParam('status', 0);
            $layoutUpload = $this->_getParam('layout-upload', 'API');

            if (isset($anexo['size']) && (int) $anexo['size'] > 0) {
                $Nau = new ArquivoUploadBusiness();
                $Nau->codigoControle = $codigo;
                $Nau->descricao = $descricao;
                $Nau->observacao = $observacao;
                $Nau->status = $status;
                try {
                    $idItemGravado = $Nau->armazenar($anexo);
                    if (strtoupper($layoutUpload) === 'API') {
                        if ($idItemGravado > 0) {
                            $resposta['arquivo_upload_id'] = $idItemGravado;
                            $resposta['upload_executado'] = true;
                        }
                    }
                } catch (Exception $Exc) {
                    $resposta['status'] = 'ERRRO';
                }

                if (strtoupper($layoutUpload) === 'API') {
                    $resposta['dh_ter_proc'] = date('Y-m-d H:i:s');
                    echo json_encode($resposta);
                } else {
                    if ($idItemGravado <= 0) {
                        throw new Exception('O recurso de gravar a nota fiscal em anexo não apresentou um retorno de sucesso e não identificou o erro!');
                    }
                }
            }
        }
    }

}
