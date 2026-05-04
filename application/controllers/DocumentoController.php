<?php

class DocumentoController extends Controller {

    public function init() {
        parent::init();
        $this->modulo = 'portal cliente';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
    }

    public function indexAction() {
        $itensPorPagina = 20;
        $intervaloBotoesPagina = 10;
        $parametroPagina = (int) $this->_getParam('page', 1);
        if ($parametroPagina < 1) {
            $parametroPagina = 1;
        }
        $empresaId = $_SESSION['empresa']['empresa_id'];
        $contratoId = $_SESSION['contrato_id'];
        $resultadoComando = array();
        $paginacaoDocumento = null;
        $imprimirComando = false;
        $ordenacao = 'arquivo.arquivo_data_registro DESC, arquivo.arquivo_id DESC';
        try {
            $arquivo = new Application_Model_Arquivo();
            $filtro = "arquivo.arquivo_status = 0";
            $filtro .= " AND arquivo.fk_empresa_id = {$empresaId}";
            $filtro .= " AND arquivo.fk_contrato_id = {$contratoId}";
            $totalItens = $arquivo->contarUsandoClausula($filtro);
            $totalPaginas = $totalItens > 0 ? (int) ceil($totalItens / $itensPorPagina) : 0;
            if ($totalPaginas > 0 && $parametroPagina > $totalPaginas) {
                $parametroPagina = $totalPaginas;
            }
            $offset = ($parametroPagina - 1) * $itensPorPagina;
            $limiteSql = $offset . ',' . $itensPorPagina;
            $resultadoComando = $arquivo->buscaCompletaUsandoClausula($filtro, $ordenacao, $limiteSql, $imprimirComando);
            $delta = (int) ceil($intervaloBotoesPagina / 2);
            $inicioIntervalo = max(1, $parametroPagina - $delta);
            $fimIntervalo = min($totalPaginas > 0 ? $totalPaginas : 1, $inicioIntervalo + $intervaloBotoesPagina - 1);
            $inicioIntervalo = max(1, $fimIntervalo - $intervaloBotoesPagina + 1);
            $paginasNoIntervalo = $totalPaginas > 0 ? range($inicioIntervalo, $fimIntervalo) : array();
            $paginacaoDocumento = array(
                'paginaAtual' => $parametroPagina,
                'totalPaginas' => $totalPaginas,
                'totalItens' => $totalItens,
                'itensPorPagina' => $itensPorPagina,
                'paginaAnterior' => ($totalPaginas > 0 && $parametroPagina > 1) ? $parametroPagina - 1 : null,
                'proximaPagina' => ($totalPaginas > 0 && $parametroPagina < $totalPaginas) ? $parametroPagina + 1 : null,
                'paginasNoIntervalo' => $paginasNoIntervalo,
            );
            $parametrosPesquisa = array(
                'pagina' => $parametroPagina,
                'contrato' => $contratoId,
                'empresa' => $empresaId,
            );
        } catch (Exception $ex) {
            $this->_enviarCapturaExcecaoParaView($ex->getMessage());
            $parametrosPesquisa = array(
                'pagina' => $parametroPagina,
                'contrato' => $contratoId,
                'empresa' => $empresaId,
            );
        }
        $this->view->parametrosPesquisa = $parametrosPesquisa;
        $this->view->itensGrid = $resultadoComando;
        $this->view->paginacaoDocumento = $paginacaoDocumento;
    }

    /**
     * Slice 4.5 do projeto migracao-arquivos-s3 (vault no repositório SISS):
     *  - se a linha tem `arquivo_s3_key`: pede presigned URL ao Laravel via
     *    cURL autenticado por X-API-Key/X-API-Secret e redireciona o cliente
     *    direto pro S3.
     *  - senão, se `arquivo_conteudo` (BLOB) ainda está populado: caminho
     *    legado intacto (echo do binário). Vale até o backfill (slice 05) zerar.
     *  - senão: 404.
     */
    public function visualizarAction() {
        $arquivoId = (int) $this->getParam('id', 0);
        $this->_desabilitarTodoCarregamentoDeVisualizacao();

        try {
            $empresaId  = isset($_SESSION['empresa']['empresa_id']) ? (int) $_SESSION['empresa']['empresa_id'] : 0;
            $contratoId = isset($_SESSION['contrato_id']) ? (int) $_SESSION['contrato_id'] : 0;

            $arquivo = new Application_Model_Arquivo();
            $row = $arquivo->fetchRow(array('arquivo_id = ?' => $arquivoId));

            if (is_null($row)) {
                $this->getResponse()->setHttpResponseCode(404);
                return;
            }
            $row = $row->toArray();

            // Defesa em profundidade: o portal só serve arquivos do escopo do
            // cliente logado. O Laravel valida de novo no endpoint, mas barrar
            // aqui evita uma viagem desnecessária.
            if ((int) $row['fk_empresa_id'] !== $empresaId || (int) $row['fk_contrato_id'] !== $contratoId) {
                $this->getResponse()->setHttpResponseCode(403);
                return;
            }

            if (!empty($row['arquivo_s3_key'])) {
                $url = $this->_pegarUrlPresignedDoLaravel($arquivoId, $empresaId, $contratoId);
                if ($url === null) {
                    $this->getResponse()->setHttpResponseCode(502);
                    return;
                }
                header('Location: ' . $url);
                exit(0);
            }

            if (!is_null($row['arquivo_conteudo'])) {
                header("Content-type:{$row['arquivo_mime_type']}");
                header("Content-Description: Arquivo gerado pelo sistema automaticamente");
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                echo $row['arquivo_conteudo'];
                exit(0);
            }

            $this->getResponse()->setHttpResponseCode(404);
        } catch (Exception $ex) {
            $this->getResponse()->setHttpResponseCode(500);
            error_log('documento visualizar falhou: ' . $ex->getMessage());
        }
    }

    /**
     * Server-to-server pro Laravel: GET /api/portal/arquivo/{id}/url.
     * Lê URL/key/secret do application.ini (seção [production] ou herdada).
     * Retorna a URL presigned ou null em qualquer falha.
     */
    private function _pegarUrlPresignedDoLaravel($arquivoId, $empresaId, $contratoId) {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $opts = $bootstrap ? $bootstrap->getOptions() : array();
        $cfg = isset($opts['portal']['arquivo']['api']) ? $opts['portal']['arquivo']['api'] : array();

        if (empty($cfg['url']) || empty($cfg['key']) || empty($cfg['secret'])) {
            error_log('documento visualizar: config portal.arquivo.api ausente em application.ini');
            return null;
        }

        $endpoint = rtrim($cfg['url'], '/') . '/' . (int) $arquivoId . '/url'
                  . '?empresa_id=' . (int) $empresaId
                  . '&contrato_id=' . (int) $contratoId;

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
            error_log('documento visualizar: presigned falhou (http ' . $http . ', err=' . $err . ') arquivo_id=' . $arquivoId);
            return null;
        }

        $json = json_decode($body, true);
        return isset($json['url']) ? $json['url'] : null;
    }

//    public function salvarAction() {
//        $arquivo = $_FILES['campoUpload'];
//        $ponteiroArquivo = fopen($_FILES['campoUpload']['tmp_name'], "rb");
//        $binario = fread($ponteiroArquivo, $_FILES['campoUpload']['size']);
//        $empresaId = $_SESSION['empresa']['empresa_id'];
//        $contratoId = $_SESSION['contrato_id'];
//        $colunas = array(
//            'arquivo_descricao' => 'Arquivo de Upload automático',
//            'arquivo_data_registro' => date('Y-m-d'),
//            'arquivo_mime_type' => $arquivo['type'],
//            'arquivo_tipo' => 'OUTRO',
//            'fk_empresa_id' => $empresaId,
//            'fk_contrato_id' => $contratoId,
//            'arquivo_conteudo' => $binario
//        );
//        // ALTER TABLE  `arquivo` CHANGE  `arquivo_conteudo`  `arquivo_conteudo` LONGBLOB NULL DEFAULT NULL
//        $arquivo = new Application_Model_Arquivo();
//        $arquivo->insert($colunas);
//        $this->_desabilitarTodoCarregamentoDeVisualizacao();
//    }
}
