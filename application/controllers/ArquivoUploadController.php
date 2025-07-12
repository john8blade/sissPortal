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
    public function descarregarAction() {
        #self::$_habilitarRegistrarLog = false;
        $id = (int) $this->_getParam('id', 0);
        $modo = strtolower($this->_getParam('modo', 'virtual'));
        $this->_desabilitarTodoCarregamentoDeVisualizacao();
        try {
            if ($id == 0)
                throw new Exception('O parâmetro de identificação não foi atribuído ou não está em tipo de dado válido!');
            if ($modo == 'virtual')
                ArquivoUploadBusiness::descarregarArquivoArmazenadoEmBancoIdentificandoPeloId($id);
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
