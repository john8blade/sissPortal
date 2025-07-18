<?php

class EsocialController extends Controller {

    public function init() {
        parent::init();
        $this->modulo = 'portal cliente';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
    }

    public function indexAction() {

        $atributos = $_SESSION;

        $resultadoComando  = [];
        $resultadoPaginado = [];
        $EsocialModel      = new Application_Model_Esocial();        
        $TipoEvtModel      = new Application_Model_ESocialTipoEvento();
        $FuncionarioModel  = new Application_Model_Funcionario();
        $geral             = Zend_Db_Table::getDefaultAdapter();
        
        $pagina      = $this->_getParam('page', 1);
        $evento      = trim($this->_getParam('paramEvento'));
        $data        = trim($this->_getParam('paramData'));
        $responsavel = trim($this->_getParam('paramResponsavel'));

        $form = [
            'paramEvento'      => $evento,
            'paramData'        => $data,
            'paramResponsavel' => $responsavel
        ];

        $filtro = '';
        if (!empty($responsavel)) {
            $filtro .= " AND (p.pessoa_nome LIKE '%{$responsavel}%' OR p.pessoa_cpf LIKE '%{$responsavel}%')";
        }
        
        try {
            $funcionarios = $FuncionarioModel->xobter([
                'funcionario_id AS id', 'pessoa_nome AS nome'
            ], [
                'pessoa' => 'pessoa.pessoa_id = funcionario.fk_pessoa_id'
            ], [
                "funcionario.fk_contrato_id = {$_SESSION['empresa']['fk_contrato_id']}"
            ], 'ORDER BY pessoa.pessoa_nome ASC');

            $consulta = $EsocialModel->obterTodosContrato($atributos['contrato_id'], $filtro);
            $eventos = $TipoEvtModel->obter(['nome', 'detalhe', 'id'], 'ORDER BY esocial_tipoevento.esocial_tipoevento_nome DESC');
            $resultadoPaginado = Zend_Paginator::factory($consulta);
            $resultadoPaginado->setCurrentPageNumber($pagina);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
     
        $this->view->funcionarios   = $funcionarios;
        $this->view->textoContato   = $geral->query("SELECT parametro_valor FROM parametro WHERE parametro_nome = 'eSocial.textoContato'")->fetch()['parametro_valor'];
        $this->view->eventos        = $eventos;
        $this->view->atributos      = $atributos;
        $this->view->itensPaginados = $resultadoPaginado;
        $this->view->like           = '';
        $this->view->form           = $form;
        $this->view->params         = $this->getAllParams();

    }

    public function catAction() {

        $EsocialCat = new Application_Model_EsocialCat();
        
        $pagina = $this->_getParam('page', 1);
        $data   = trim($this->_getParam('paramData'));
        $cpf    = trim($this->_getParam('paramCpf'));
        $func   = trim($this->_getParam('paramFuncionario'));
        $tpacid = trim($this->_getParam('paramTipoAcid'));
        $obt    = trim($this->_getParam('paramObito'));

        $form = array(
            'paramData'         => $data,
            'paramCpf'          => $cpf,
            'paramFuncionario'  => $func,
            'paramTipoAcid'     => $tpacid,
            'paramObito'        => $obt
        );

        $dt = null;
        if (strlen($data) >= 10) {
            $dt = Util::dataBD($data);
        }

        // Definindo os parametros de busca.
        $filtro = " AND contrato_id = ". $_SESSION['contrato_id'];

        if (!empty($data)) {
            $filtro .= "esocial_cat_dtAcid LIKE '%{$dt}%' ";
        }
        if (!empty($data)) {
            $filtro .= " AND pessoa_cpf LIKE '%{$cpf}%'";
        }
        if (!empty($func)) {
            $filtro .= " AND pessoa_nome LIKE '%{$func}%'";
        }
        if (!empty($tpacid)) { 
            $filtro .= " AND tpAcid LIKE '%{$tpacid}%'";
        }
        if (!empty($obt)) {
            $filtro .= " AND indCatObito LIKE '%{$obt}%'";
        }

        $resultadoComando = array();
        $resultadoPaginado = array();

        $tpAcid  = array('1' => 'Típica', 
                         '2' => 'Doença', 
                         '3' => 'Trajeto');
        
        try {
            $consulta = $EsocialCat->obterCatContrato($filtro);
            $resultadoPaginado = Zend_Paginator::factory($consulta);
            $resultadoPaginado->setCurrentPageNumber($pagina);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $this->view->tpAcid = $tpAcid;
        $this->view->itensPaginados = $resultadoPaginado;
        $this->view->like = '';
        $this->view->form = $form;

    }

    public function adicionarAction() {

      $atributos              = $this->_processamentodados(); 
      $this->view->atributos  = $atributos;
      $this->renderScript('esocial/form.phtml');

    }

    public function alterarAction() {

      $id = $this->_getParam("id", 0);
      
      $EsocialCat = new Application_Model_EsocialCat();
      $dados_cat = $EsocialCat->obterCatId($id);

      $dados_cat['esocial_cat_dtAcid'] = Util::dataBR($dados_cat['esocial_cat_dtAcid']);
      $dados_cat['esocial_cat_hrAcid'] = substr($dados_cat['esocial_cat_hrAcid'], 0, 2).':'.substr($dados_cat['esocial_cat_hrAcid'], 2, 3);
      $dados_cat['esocial_cat_hrsTrabAntesAcid'] = substr($dados_cat['esocial_cat_hrsTrabAntesAcid'], 0, 2).':'.substr($dados_cat['esocial_cat_hrsTrabAntesAcid'], 2, 3);
      $dados_cat['esocial_cat_dtObito'] = Util::dataBR($dados_cat['esocial_cat_dtObito']);
      $dados_cat['esocial_cat_ultDiaTrab'] = Util::dataBR($dados_cat['esocial_cat_ultDiaTrab']);
      $dados_cat['esocial_cat_cep'] = ($dados_cat['esocial_cat_cep'] == 0) ? '' : $dados_cat['esocial_cat_cep'];
      $dados_cat['esocial_cat_dtAtendimento'] = Util::dataBR($dados_cat['esocial_cat_dtAtendimento']);
      $dados_cat['esocial_cat_hrAtendimento'] = substr($dados_cat['esocial_cat_hrAtendimento'], 0, 2).':'.substr($dados_cat['esocial_cat_hrAtendimento'], 2, 3);

      $atributos              = $this->_processamentodados();      
      $atributos["dados_cat"] = $dados_cat;
      $this->view->atributos  = $atributos;

      $this->view->atributos = $atributos;
      $this->renderScript('esocial/form.phtml');

    }

    public function _processamentodados() {

      $empresaId = $_SESSION['empresa']['empresa_id'];
      $cnpj = $_SESSION['empresa']['empresa_cnpj'];
      $contratoId = $_SESSION['contrato_id'];
      
      $model_funcionario = new Application_Model_Funcionario();
      $filtro  = "fk_contrato_id = {$contratoId} AND funcionario.fk_empresa_id = {$empresaId} ";
      $filtro .= " AND (funcionario_motivo_inativacao IS NULL OR funcionario_motivo_inativacao = '')";
      $alocacao = $model_funcionario->obterPeloFiltro($filtro);
    
      $model_uf = new Application_Model_UnidadeFederativa();
      $uf = $model_uf->obterUnidadeFederativa();
      
      $s_n              = array('S' => 'Sim', 
                                'N' => 'Não');

      $tpAcid           = array('1' => 'Típica', 
                                '2' => 'Doença', 
                                '3' => 'Trajeto');      

      $tpCat            = array('1' => 'Inicial', 
                                '2' => 'Reabertura', 
                                '3' => 'Comunicação de óbito');

      //$iniciatCat     = array('1' => 'Empregador', 
      //                        '2' => 'Ordem judicial', 
      //                        '3' => 'Determinação de órgão fiscalizador');
      
      $iniciatCat       = array('1' => 'Empregador');

      $tpLocal          = array('1' => 'Estabelecimento do empregador no Brasil', 
                                '2' => 'Estabelecimento do empregador no Exterior',
                                '3' => 'Estabelecimento de terceiros onde o empregador presta serviços',
                                '4' => 'Via pública',
                                '5' => 'Área rural',
                                '6' => 'Embarcação',
                                '9' => 'Outros');

      $tpInsc           = array('1' => 'CNPJ', 
                                '3' => 'CAEPF', 
                                '4' => 'CNO');

      $lateralidade     = array('0' => 'Não aplicável', 
                                '1' => 'Esquerda',
                                '2' => 'Direita',
                                '3' => 'Ambos');

      $ideOC            = array('1' => 'Conselho Regional de Medicina - CRM', 
                                '2' => 'Conselho Regional de Odontologia - CRO', 
                                '3' => 'Registro do Ministério da Saúde - RMS');

      $atributos["empresa_id"]         = $empresaId;  
      $atributos["empresa"]            = $cnpj;
      $atributos["alocacao"]           = $alocacao;
      $atributos["tipo_acidente"]      = $tpAcid;
      $atributos["tipo_cat"]           = $tpCat;      
      $atributos["houve_obito"]        = $s_n;
      $atributos["comun_policia"]      = $s_n;
      $atributos["iniciat_cat"]        = $iniciatCat;
      $atributos["houve_afast"]        = $s_n;
      $atributos["tipo_local"]         = $tpLocal;
      $atributos["tipo_inscricao"]     = $tpInsc;
      $atributos["lateralidade"]       = $lateralidade;
      $atributos["internacao"]         = $s_n;
      $atributos["afastamento_trab"]   = $s_n;
      $atributos["orgao_classe"]       = $ideOC;
      $atributos["uf"]                 = $uf;      
      #util::dump($atributos);
      return $atributos;

    }

    public function salvarAction() {

        if ($this->getRequest()->isPost()) {
            $feedback = array("erro" => 1, "msg" => "");

            $form = $this->getRequest()->getPost();
            
            $id = $form['esocial_cat_id'];
            unset($form['esocial_cat_id']);
            unset($form['pessoa_cpf']);            
            unset($form['matricula']);
            unset($form['cnpj']);

            $dados_cat = array();
            $dados_cat = $form;
            
            $dados_cat['esocial_cat_criada_em'] = date('Y-m-d H:i:s');
            $dados_cat['esocial_cat_dtAcid'] = Util::dataBD($dados_cat['esocial_cat_dtAcid']);
            if (!empty($dados_cat['esocial_cat_hrAcid'])) {
               $dados_cat['esocial_cat_hrAcid'] = str_replace(':', '', $dados_cat['esocial_cat_hrAcid']);
            }
            if (!empty($dados_cat['esocial_cat_hrsTrabAntesAcid'])) {
               $dados_cat['esocial_cat_hrsTrabAntesAcid'] = str_replace(':', '', $dados_cat['esocial_cat_hrsTrabAntesAcid']); 
            }
            if ($dados_cat['esocial_cat_indCatObito'] == 'N') {
               $dados_cat['esocial_cat_dtObito'] = NULL;
            }elseif ($dados_cat['esocial_cat_indCatObito'] == 'S'){
               if (!empty($dados_cat['esocial_cat_dtObito'])) {
                  $dados_cat['esocial_cat_dtObito'] = Util::dataBD($dados_cat['esocial_cat_dtObito']);
               }
               $dados_cat['esocial_cat_indAfast'] = 'N';     
            }
            $dados_cat['esocial_cat_iniciatCAT'] = 1; //CAT inicial Empregador
            $dados_cat['esocial_cat_ultDiaTrab'] = Util::dataBD($dados_cat['esocial_cat_ultDiaTrab']);          
            if (!empty($dados_cat['esocial_cat_cep'])) {
               $dados_cat['esocial_cat_cep'] = preg_replace('/\D/', '', $dados_cat["esocial_cat_cep"]);
            }
            $dados_cat['esocial_cat_dtAtendimento'] = Util::dataBD($dados_cat['esocial_cat_dtAtendimento']);
            if (!empty($dados_cat['esocial_cat_hrAtendimento'])) {
               $dados_cat['esocial_cat_hrAtendimento'] = str_replace(':', '', $dados_cat['esocial_cat_hrAtendimento']);
            }
            if (!empty($dados_cat['esocial_cat_cpf'])) {
               $dados_cat['esocial_cat_cpf'] = str_replace(array('-', '/', '.'), '', trim($dados_cat["esocial_cat_cpf"]));
            }
            $dados_cat['esocial_cat_nrLograd'] = !empty($dados_cat['esocial_cat_nrLograd']) ? $dados_cat['esocial_cat_nrLograd'] : "S/N";

            if (!empty($dados_cat['esocial_cat_nrRecCatOrig']) AND strlen($dados_cat['esocial_cat_nrRecCatOrig']) == 23) {
                $dados_cat['esocial_cat_nrRecCatOrig'] = $dados_cat['esocial_cat_nrRecCatOrig'];
            }else{
                $dados_cat['esocial_cat_nrRecCatOrig'] = '';
            }
            
            try {

                if (!empty($dados_cat['esocial_cat_dtAcid']) AND !empty($dados_cat['fk_alocacao_id']) AND !empty($dados_cat['esocial_cat_iniciatCAT']) AND !empty($dados_cat['esocial_cat_cpf'])) {

                    $EsocialCat = new Application_Model_EsocialCat();
                    $existe = $EsocialCat->obterDadosExistentes($_SESSION['contrato_id'], $dados_cat['esocial_cat_dtAcid'], $dados_cat['fk_alocacao_id']);
                    
                    if ($id>0) {
                        
                        $EsocialCat->update($dados_cat, array('esocial_cat_id = ?' => $id));
                        $feedback = array(
                                        'erro' => 0,
                                        'msg' => '<i class="icon-ok"></i>&nbsp;CAT registrada!&nbsp;<a href="/esocial/adicionar/"><i class="icon-plus-sign"></i>&nbsp;Nova CAT</a>&nbsp;<a href="/esocial/alterar/id/' . $id . '/"><i class="icon-edit"></i>&nbsp;Alterar</a>'
                                    );
                        
                    }else{                        
                       
                       if ($existe == false) {
                            $id = $dados_cat_id = $EsocialCat->insert($dados_cat);
                            $feedback = array(
                                            'erro' => 0,
                                            'msg' => '<i class="icon-ok"></i>&nbsp;CAT atualizada!&nbsp;<a href="/esocial/adicionar/"><i class="icon-plus-sign"></i>&nbsp;Nova CAT</a>&nbsp;<a href="/esocial/alterar/id/' . $id . '/"><i class="icon-edit"></i>&nbsp;Alterar</a>'
                                        );
                       }else{
                            $feedback = array(
                                            'erro' => 0,
                                            'msg' => '<i class="icon-ok"></i>&nbsp;CAT já foi registrada!&nbsp;<a href="/esocial/adicionar/"><i class="icon-plus-sign"></i>&nbsp;Nova CAT</a>&nbsp;'
                                        );
                       }                                        

                    }

                }else{

                    $feedback = array(
                                        'erro' => 1,
                                        'msg' => '<b>Favor realizar o preenchimento dos campos obrigatórios conforme orientado no formulário.</b>'
                                      );
                }
                
            } catch (Exception $e) {
                $feedback = array(
                            'erro' => 1,
                            'msg' => "<b>Ocorreu um erro no processo de cadastramento:</b> " . $e->getMessage());
            }
            
            $this->feedback($feedback);

        }

    }

    /**
     * Baixa o arquivo xml dos parametros passados
     * 
     * @author Arthur Xavier : garok102@gmail.com
    **/
    public function baixarAction() {
        if (!$this->getRequest()->isPost()) return;
        $this->_desabilitarTodoCarregamentoDeVisualizacao();

        $empresa = $_SESSION['empresa']['empresa_id'];
        $form = $this->getRequest()->getPost();
        switch ($form['evento']) {
            case 1: 
                eSocialActions::baixarXmlS2220($empresa, [
                    'inicio' => Util::dataBD($form['data_inicio']),
                    'fim'    => Util::dataBD($form['data_fim'])
                ], ($form['funcionario'] > 0 ? $form['funcionario'] : null));
                break;
            case 2:
                eSocialActions::baixarXmlS2240($empresa, ($form['funcionario'] > 0 ? $form['funcionario'] : null));
                break;
        }
        die();
    }

    public function baixarReciboAction() {
        if (($id = $this->_getParam('id', 0)) == 0 || !($this->getRequest()->isGet())) return;

        // $ESocialEnvio = new Application_Model_eSocialEnvio();
        // $xml = $ESocialEnvio->obter(['retorno'], ["esocial_envio_id = {$id}"])['retorno'];

        $xml = Zend_Db_Table::getDefaultAdapter()->fetchOne("SELECT esocial_envio_retorno FROM esocial_envio WHERE esocial_envio_id = $id");
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'. $id . '.xml"');
        die($xml);
    }
}
