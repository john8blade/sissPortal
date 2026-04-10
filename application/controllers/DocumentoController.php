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

    public function visualizarAction() {
        $arquivoId = $this->getParam('id', 0);
        try {
            $arquivo = new Application_Model_Arquivo();
            $resultadoComando = $arquivo->fetchRow(array('arquivo_id = ?' => $arquivoId));
            if (is_null($resultadoComando) == false) {
                $resultadoComando = $resultadoComando->toArray();
                header("Content-type:{$resultadoComando['arquivo_mime_type']}");
                header("Content-Description: Arquivo gerado pelo sistema automaticamente");
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                echo $resultadoComando['arquivo_conteudo'];
                exit(0);
            }
        } catch (Exception $ex) {
            $this->_enviarCapturaExcecaoParaView($ex->getMessage());
        }
        $this->_desabilitarTodoCarregamentoDeVisualizacao();
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
