<?php

class FuncionarioController extends Controller {

    const REGRA_ATIVO_COORDENACAO_PCMSO = 2;
    const REGRA_ATIVO_GERAL = 1;

    public function init() {
        parent::init();
        $this->modulo = 'portal cliente';
    }

    public function indexAction() {

        $resultadoPaginado = null;
        $buscar = $this->_getParam('like');
        $pagina = $this->_getParam('page', 1);

        try {

            $filtrocpf = trim($this->_getParam('filtrocpf'));
            $filtronome = trim($this->_getParam('filtronome'));
            $filtrocargo = trim($this->_getParam('filtrocargo'));
            $filtrostatus = trim($this->_getParam('filtrostatus'));
            $filtroperiodico = trim($this->_getParam('filtroperiodico'));

            $filtros = array(
                'filtrocpf' => $filtrocpf,
                'filtronome' => $filtronome,
                'filtrocargo' => $filtrocargo,
                'filtrostatus' => $filtrostatus,
                'filtroperiodico' => $filtroperiodico,
            );

            $where = " WHERE list.func_status IN('ATIVO','INATIVO')";
            if (strtoupper($filtrostatus) == 'ATIVO') {
                $where = " WHERE list.func_status IN('ATIVO')";
            }elseif (strtoupper($filtrostatus) == 'INATIVO') { 
                $where = " WHERE list.func_status IN('INATIVO')";  
            }
               
            if (strlen($filtrocpf) >= 11) {
                $c = str_replace(array('.', '-', '_', '/'), '', $filtrocpf);
                $where .= " AND list.pessoa_cpf = ".$c;
            }

            if (strlen($filtronome) > 0) {
                $where .= " AND list.pessoa_nome LIKE '%".$filtronome."%'";
            }

            if (strlen($filtrocargo) > 0) {
                $where .= " AND list.cargo_nome LIKE '%".$filtrocargo."%'";
            }
            
            if (strlen($filtroperiodico) > 0) {
                $where .= " AND list.periodico_status LIKE '%".$filtroperiodico."%'";
            } 

            $f = new Application_Model_Funcionario();
            $resultado = $f->RelacFunc($where);
            $resultadoPaginado = Zend_Paginator::factory($resultado);
            $resultadoPaginado->setCurrentPageNumber($pagina);

        } catch (Exception $ex) {
            die($ex->getMessage());
        }
        $this->view->itensPaginados = $resultadoPaginado;
        $this->view->parametroPesquisa = $buscar;
        $this->view->like = '';
        $this->view->filtros = $filtros;
    }

    public function adicionarAction() {

        $cargos = $funcoes = $empresas = $contratos = array();
        $atributosContrato = $atributosSetor = $atributosPessoa = $atributosFuncionario = $atributosAlocacao = $atributosEmpresa = array();

        try {
            $model_cargo = new Application_Model_Cargo();
            $cargos = $model_cargo->obterTodos();

            $model_funcao = new Application_Model_Funcao();
            $funcoes = $model_funcao->obterTodos();

            //$model_empresa = new Application_Model_Empresa();
            //$empresas = $model_empresa->listarEmpresas();
            $contratoId = isset($_SESSION['contrato_id']) ? $_SESSION['contrato_id'] : 0;
            $ModeloContrante = new Application_Model_Contratante();
            $condicao = "contrato.contrato_id = {$contratoId}";
            $empresas = $ModeloContrante->buscarUsandoClausula($condicao, 'empresa.empresa_razao');

            $atributosFuncionario = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('funcionario');
            $atributosAlocacao = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('alocacao');
            $atributosSetor = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('setor');
            $atributosContrato = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('contrato');
            $atributosEmpresa = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('empresa');
            $atributosContrato['contrato_id'] = $_SESSION['contrato_id'];
            $atributosEmpresa['empresa_id'] = $_SESSION['empresa']['empresa_id'];
            $atributosPessoa = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('pessoa');

            # PPRA ITENS
            //$ppraModel = new Application_Model_Ppra();
            //$ppra_itens = $ppraModel->obterItensPelaEmpresaEContrato($atributosEmpresa['empresa_id'], $atributosContrato['contrato_id']);
            //$this->view->ppra_itens = $ppra_itens;
            //echo $atributosContrato['contrato_id'], ' - ',  $atributosEmpresa['empresa_id'];
            //exit(0);

            /*
             *
             * ID: 2014-12-01 16:00:00
             * ID Chamado: 524
             * Descrição: Com a criação de um novo PCMSO, o ideal que o antigo fique inativo, o que atualmente não acontece, o cliente tem acesso a todas as funções, inclusive do documento antigo,então se a função muda algum risco ou exame, fica disponível para o cliente a mesma função 2 vezes.
             */
            $ModeloItemPcmso = new Application_Model_ItemPcmso();
            $colecaoComResultados = $ModeloItemPcmso->buscarColecaoItensDoPcmsoMaisAtual($atributosContrato['contrato_id'], $atributosEmpresa['empresa_id']);
            $this->view->ppra_itens = $colecaoComResultados;
            // Fim ID 2014-12-01 16:00:00
        } catch (Exception $e) {
            $this->_enviarCapturaExcecaoParaView($e->getMessage());
        }

        $this->view->empresas = $empresas;
        $this->view->contratos = $contratos;
        $this->view->funcoes = $funcoes;
        $this->view->cargos = $cargos;
        $this->view->atributos = array_merge($atributosAlocacao, $atributosFuncionario, $atributosPessoa, $atributosSetor, $atributosContrato, $atributosEmpresa);
    }

    public function alterarAction() {
        $id = $this->_getParam("id", 0);
        $cargos = $funcoes = $empresas = $contratos = array();
        $atributosContrato = $atributosSetor = $atributosPessoa = $atributosFuncionario = $atributosAlocacao = $atributosEmpresa = $atributosRascunho = array();
        try {
            $model_cargo = new Application_Model_Cargo();
            $cargos = $model_cargo->obterTodos();

            $model_funcao = new Application_Model_Funcao();
            $funcoes = $model_funcao->obterTodos();

            $atributosAlocacao = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('alocacao');
            $atributosContrato = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('contrato');
            $atributosEmpresa = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('empresa');
            $atributosContrato['contrato_id'] = $_SESSION['contrato_id'];
            $atributosEmpresa['empresa_id'] = $_SESSION['empresa']['empresa_id'];

            $model_funcionario = new Application_Model_Funcionario();
            $atributosFuncionario = $model_funcionario->obterPortal($id);

            /*
              $model_empresa = new Application_Model_Empresa();
              $empresas = $model_empresa->listarEmpresas();
             */
            $contratoId = isset($_SESSION['contrato_id']) ? $_SESSION['contrato_id'] : 0;
            $ModeloContrante = new Application_Model_Contratante();
            $condicao = "contrato.contrato_id = {$contratoId}";
            $empresas = $ModeloContrante->buscarUsandoClausula($condicao, 'empresa.empresa_razao');

            $model_ficha = new Application_Model_FichaFispq();
            $this->view->ficha = $model_ficha->exibirPorId($id);

            # PPRA ITENS
            //$ppraModel = new Application_Model_Ppra();
            //$ppra_itens = $ppraModel->obterItensPelaEmpresaEContrato($atributosEmpresa['empresa_id'], $atributosContrato['contrato_id']);

            $ModeloItemPcmso = new Application_Model_ItemPcmso();
            $colecaoComResultados = $ModeloItemPcmso->buscarColecaoItensDoPcmsoMaisAtual($atributosContrato['contrato_id'], $atributosEmpresa['empresa_id']);


            $this->view->ppra_itens = $colecaoComResultados;

            if (isset($ppra_itens[0])) {
                
            } else {
                $model_rascunho = new Application_Model_Rascunho();
                $rasc = $model_rascunho->buscarPorId($id);
                $this->view->rascunho = $rasc;
            }
        } catch (Exception $e) {
            $this->_enviarCapturaExcecaoParaView($e->getMessage());
        }

        $this->view->empresas = $empresas;
        $this->view->contratos = $contratos;
        $this->view->funcoes = $funcoes;
        $this->view->cargos = $cargos;
        $this->view->atributos = array_merge($atributosAlocacao, $atributosFuncionario, $atributosPessoa, $atributosSetor, $atributosContrato, $atributosEmpresa);
        $this->renderScript('funcionario/adicionar.phtml');
    }

    public function salvarAction() {
        sleep(5);
        if ($this->getRequest()->isPost()) {
            $feedback = array("erro" => 1, "msg" => "");

            $post = $this->getRequest()->getPost();


            $post["pessoa_cpf"] = preg_replace('/\D/', '', $post["pessoa_cpf"]);
            $post["pessoa_data_nascimento"] = Util::dataBD($post["pessoa_data_nascimento"]);
            $post["funcionario_data_admissao"] = Util::dataBD($post["funcionario_data_admissao"]);

            $funcionario_id = (int) $post["funcionario_id"];
            $pessoa_id = (int) $post["fk_pessoa_id"];
            $alocacao_id = (int) $post["alocacao_id"];
            
            $pcmso_item_id = 0;
            if (isset($post["item_pcmso_id"])) {
                $pcmso_item_id = (int) $post["item_pcmso_id"];
            }
            $ppra_item_id = 0;
            if (isset($post["fk_ppra_item_id"])) {
                $ppra_item_id = (int) $post["fk_ppra_item_id"];
            }

            $model_funcionario = new Application_Model_Funcionario();
            $model_pessoa = new Application_Model_Pessoa();
            $model_alocacao = new Application_Model_Alocacao();
            $model_rascunho = new Application_Model_Rascunho();

            $existe = $model_funcionario->verificarAlocacaoPeloCpf($post["pessoa_cpf"], (int) $post["fk_empresa_id"]);

            if ($funcionario_id < 1 && !empty($existe)) {
                $feedback = array(
                    'erro' => 2,
                    'msg' => "Este CPF já está alocado nesta empresa.");
            }

            if ($feedback['erro'] != 2) {

                if (isset($post['rascunho_ppra_ghe']) || isset($post['rascunho_ppra_cargo']) || isset($post['rascunho_ppra_setor']) || isset($post['rascunho_ppra_funcao'])) {
                    $validacao = Util::validaCampos(array(
                                'fk_contrato_id' => array('tipo' => 'texto', 'nome' => 'Contrato'),
                                'pessoa_cpf' => array('tipo' => 'texto', 'nome' => 'CPF'),
                                'pessoa_nome' => array('tipo' => 'texto', 'nome' => 'Nome'),
                                'rascunho_ppra_ghe' => array('tipo' => 'texto', 'nome' => 'GHE'),
                                'rascunho_ppra_cargo' => array('tipo' => 'texto', 'nome' => 'Cargo'),
                                'rascunho_ppra_setor' => array('tipo' => 'texto', 'nome' => 'Setor'),
                                'rascunho_ppra_funcao' => array('tipo' => 'texto', 'nome' => 'Funcao'),
                                'fk_empresa_id' => array('tipo' => 'texto', 'nome' => 'Empresa')), $post);
                } else {
                    $validacao = Util::validaCampos(array(
                                'fk_empresa_id' => array('tipo' => 'texto', 'nome' => 'Empresa'),
                                'fk_contrato_id' => array('tipo' => 'texto', 'nome' => 'Contrato'),
                                'item_pcmso_id' => array('tipo' => 'texto', 'nome' => 'Alocação'),
                                'pessoa_cpf' => array('tipo' => 'texto', 'nome' => 'CPF'),
                                'pessoa_nome' => array('tipo' => 'texto', 'nome' => 'Nome'),
                                'obra' => array('tipo' => 'texto', 'nome' => 'Obra'),
                                'pessoa_data_nascimento' => array('tipo' => 'texto', 'nome' => 'Data de Nascimento')), $post);
                }
                if (count($validacao["erros"]) === 0) {

                    $ppraModel = new Application_Model_Ppra();
                    $alocacaoModel = new Application_Model_Alocacao();
                    //util::dump($post);

                    $itemPcmso = array();
                    $itemPcmso['ghe_id'] = 0;
                    $itemPcmso['setor_id'] = 0;
                    $itemPcmso['funcao_id'] = 0;
                    $itemPcmso['cargo_id'] = 0;

                    if (!isset($post['rascunho_ppra_ghe']) || !isset($post['rascunho_ppra_cargo'])) {
                        //$ppraItem = $ppraModel->obterItemDoPpra($ppra_item_id);

                        $ModeloItemPcmso = new Application_Model_ItemPcmso();
                        $resultadoItemPcmso = $ModeloItemPcmso->fetchRow(array('item_pcmso_id = ?' => $pcmso_item_id));
                        if ($resultadoItemPcmso) {
                            $itemPcmso['ghe_id'] = $resultadoItemPcmso->fk_ghe_id;
                            $itemPcmso['setor_id'] = $resultadoItemPcmso->fk_setor_id;
                            $itemPcmso['funcao_id'] = $resultadoItemPcmso->fk_funcao_id;
                            $itemPcmso['cargo_id'] = $resultadoItemPcmso->fk_cargo_id;
                        }
                    }

                    $atributos_funcionario = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor("funcionario");
                    $atributos_pessoa = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor("pessoa");
                    $atributos_alocacao = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor("alocacao");
                    $atributos_fispq = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor("ficha_fispq");
                    $atributos_racunho_ppra = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor("rascunho_ppra");

                    $dados_funcionario = array();
                    $dados_pessoa = array();
                    $dados_alocacao = array();
                    $dados_fispq = array();
                    $dados_rascunho_ppra = array();

                    foreach ($post as $campo => $valor) {
                        if (array_key_exists($campo, $atributos_funcionario)) {
                            $dados_funcionario[$campo] = $valor;
                        } else if (array_key_exists($campo, $atributos_pessoa)) {
                            $dados_pessoa[$campo] = trim($valor);
                        } else if (array_key_exists($campo, $atributos_alocacao)) {
                            $dados_alocacao[$campo] = $valor;
                        } else if (array_key_exists($campo, $atributos_fispq)) {
                            $dados_fispq[$campo] = $valor;
                        } else if (array_key_exists($campo, $atributos_racunho_ppra)) {
                            $dados_rascunho_ppra[$campo] = $valor;
                        }
                    }
                    if (!isset($post['rascunho_ppra_ghe']) || !isset($post['rascunho_ppra_cargo'])) {

                        $dados_alocacao['fk_ghe_id'] = $itemPcmso['ghe_id'];
                        $dados_alocacao['fk_setor_id'] = $itemPcmso['setor_id'];
                        $dados_alocacao['fk_funcao_id'] = $itemPcmso['funcao_id'];
                        $dados_alocacao['fk_cargo_id'] = $itemPcmso['cargo_id'];
                        $dados_alocacao["fk_empresa_id"] = $post['obra'];
                        //$dados_alocacao['fk_funcionario_id'] = $funcionario_id;
                        /*
                        $dados_alocacao["fk_ppra_item_id"] = $post['item_ppra_id'];
                        if (!empty($post['item_ppra_id'])) {
                            $model_itemPcmso = new Application_Model_ItemPcmso();
                            $item_pcmso = $model_itemPcmso->obterviaitemppra($post['item_ppra_id']);

                        }*/
                        $dados_alocacao["fk_ppra_item_id"] = $ppra_item_id;
                        $dados_alocacao["fk_item_pcmso_id"] = $pcmso_item_id;#(isset($item_pcmso['item_pcmso_id'])) ? $item_pcmso['item_pcmso_id'] : 0;
                    }
                    try {

                        $adapter = Zend_Db_Table::getDefaultAdapter();
                        $adapter->beginTransaction();

                        try {

                            if ($pessoa_id > 0) {
                                $model_pessoa->update($dados_pessoa, array('pessoa_id = ?' => $pessoa_id));
                            } else {
                                $pessoa_id = $model_pessoa->insert($dados_pessoa);
                            }

                            $dados_funcionario["fk_pessoa_id"] = $pessoa_id;

                            try {

                                if ($funcionario_id > 0) {
                                    $model_funcionario->update($dados_funcionario, array('funcionario_id = ?' => $funcionario_id));
                                } else {
                                    $funcionario_id = $model_funcionario->insert($dados_funcionario);
                                }
                                
                                if ($pcmso_item_id > 0) {
                                    $dados_alocacao["fk_funcionario_id"] = $funcionario_id;
                                    if ($alocacao_id > 0) {//util::dump($dados_alocacao);
                                        $model_alocacao->update($dados_alocacao, array('alocacao_id = ?' => $alocacao_id));
                                    } else {
                                        //util::dump($dados_alocacao);
                                        if (empty($alocacao_id)) {
                                            $alocacao_id = $model_alocacao->insert($dados_alocacao);
                                        }
                                        
                                    }
                                } else {
                                    $rasc = $model_rascunho->buscarPorId($funcionario_id);

                                    if ($rasc) {
                                        $dados_rascunho_ppra['rascunho_ppra_data'] = date('Y-m-d');
                                        $dados_rascunho_ppra['rascunho_ppra_obra'] = $post['obra'];
                                        $model_rascunho->update($dados_rascunho_ppra, array('fk_funcionario_id = ?' => $funcionario_id));
                                    } else {
                                        $dados_rascunho_ppra['fk_funcionario_id'] = $funcionario_id;
                                        $dados_rascunho_ppra['rascunho_ppra_data'] = date('Y-m-d');
                                        $dados_rascunho_ppra['rascunho_ppra_obra'] = $post['obra'];
                                        $model_rascunho->insert($dados_rascunho_ppra);
                                    }
                                }

                                //dados do FISPQ
                                $dados_fispq['ficha_fispq_status'] = 0;
                                $dados_fispq['fk_funcionario_id'] = $funcionario_id;

                                $model_ficha = new Application_Model_FichaFispq();
                                $ficha_fispq = $model_ficha->insert($dados_fispq);

                                $feedback = array(
                                    'erro' => 0,
                                    'msg' => '<i class="icon-ok"></i>&nbsp;Funcionário salvo!&nbsp;<a href="/funcionario/adicionar/"><i class="icon-plus-sign"></i>&nbsp;Novo</a>&nbsp;<a href="/funcionario/alterar/id/' . $funcionario_id . '/"><i class="icon-edit"></i>&nbsp;Alterar</a>'
                                );

                                $adapter->commit();
                            } catch (Exception $eInsFunc) {
                                $adapter->rollBack();
                                $feedback = array(
                                    'erro' => 1,
                                    'msg' => "<b>Erro[1] ao registrar o funcionário:</b> " . $eInsFunc->getMessage());
                            }
                        } catch (Exception $eInsPes) {
                            $adapter->rollBack();
                            $feedback = array(
                                'erro' => 1,
                                'msg' => "<b>Erro[2] ao registrar o funcionário:</b> " . $eInsPes->getMessage());
                        }
                    } catch (Exception $eBegTra) {
                        $feedback = array(
                            'erro' => 1,
                            'msg' => "<b>Erro na comunicação com o banco de dados:</b> " . $eBegTra->getMessage());
                    }
                } else {

                    $feedback = array(
                        'erro' => 2,
                        'msg' => implode("<br/>", $validacao["erros"]),
                        'corrigir' => $validacao["corrigir"]);
                }
            }

            $this->feedback($feedback);
        }
    }

}
