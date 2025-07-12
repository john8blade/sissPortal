<?php

class EmpresaController extends Controller {

    public function init() {
        parent::init();
        $this->view->menu_lateral_itens = array(
            '/empresa' => '<i class="icon-th-large"></i> VISUALIZAR TODAS AS EMPRESAS',
        );
        $this->modulo = 'comercial';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
    }

    public function indexAction() {
        $this->view->menu_lateral_itens["/empresa/adicionar/"] = '<i class="icon-plus"></i> ADICIONAR EMPRESA';
        $this->view->menu_lateral_itens['/downloads/siss-comercial-empresa.pdf'] = '<i class=" icon-info-sign"></i> Manual';
        $resultadoPaginado = array();
        $buscar = $this->_getParam('like');
        $pagina = $this->_getParam('page', 1);
        $lista = array();

        $this->_log->logDetalhe = 'Listagem de todas as empresas';
        $this->_log->logTabelaNome = 'empresa';
        $this->_log->logTabelaColunaValor = '*';
        $this->_log->logTabelaColunaNome = '*';

        $customizarComando = " empresa.empresa_tipo = 'CLIENTE' ";
        $customizarComando .= "AND ( ";
        $customizarComando .= " empresa.empresa_fantasia LIKE '%{$buscar}%'";
        $customizarComando .= " OR empresa.empresa_cnae LIKE '%{$buscar}%'";
        $customizarComando .= " OR empresa.empresa_cnpj LIKE '%{$buscar}%'";
        $customizarComando .= " OR empresa.empresa_cnae LIKE '%{$buscar}%'";
        $customizarComando .= " OR empresa.empresa_identificacao LIKE '%$buscar%'";
        $customizarComando .= " OR empresa.empresa_insc_estadual LIKE '%$buscar%'";
        $customizarComando .= " OR empresa.empresa_insc_municipal LIKE '%$buscar%'";
        $codigo = (int) $buscar;
        $customizarComando .= " OR empresa.empresa_id = $codigo ";
        $customizarComando .= " ) ";

        $ordenarPor = "empresa.empresa_fantasia ASC";

        $model_empresa = new Application_Model_Empresa();

        try {
            $cont = 0;
            $consulta = $model_empresa->buscarCompletaUsandoClausula($customizarComando, $ordenarPor);
            $tabela = new Application_Model_Tabela();
            $modeloAvaliacaoRestricao = new Application_Model_AvaliacaoRestricao();
            foreach ($consulta as $k => $con) {
                $resultadoTabela = $model_empresa->buscarTabelaPorEmpresaId($con['empresa_id']);
                $consulta[$cont]['fornecedor'] = count($resultadoTabela);
                $consulta[$cont]["analiseCreditoAprovada"] = true;
                $resultadoComandoAvaliacaoRestricao = $modeloAvaliacaoRestricao->obterUltimaAvalicaoCreditoAgrupadaPorOrgaoAvaliador($con['empresa_id']);
                foreach ($resultadoComandoAvaliacaoRestricao as $itemResultado) {
                    if ($itemResultado['avaliacao_restricao_resultado'] === 'COM_RESTRICAO') {
                        $consulta[$cont]["analiseCreditoAprovada"] = false;
                        break;
                    }
                }
                $cont++;
            }
            $resultadoPaginado = Zend_Paginator::factory($consulta);
            $resultadoPaginado->setCurrentPageNumber($pagina);
        } catch (Exception $e) {
            $this->_enviarMensagemDeExcecaoParaView($e);
        }
        $this->view->pagina = $pagina;
        $this->view->lis = $consulta;
        $this->view->itensPaginados = $resultadoPaginado;
        $this->view->parametroPesquisa = $buscar;
        $this->view->like = $buscar;
    }

    private function _enviarParamentrosComunsParaFormulario() {
        $this->view->colecaoContextoCadastroEmpresa = array();
        try {
            $ModeloContextoCadastroEmpresa = new Application_Model_ContextoCadastroEmpresa();
            $resultado = $ModeloContextoCadastroEmpresa->buscarUsandoFiltro("contexto_cadastro_empresa_status = 0", "contexto_cadastro_empresa_nome ASC");
            $this->view->colecaoContextoCadastroEmpresa = $resultado;
        } catch (Exception $exc) {
            throw $exc;
        }
    }

    public function adicionarAction() {
        $this->_log->logDetalhe = 'Formulário de inclusão e/ou alteração de empresa';
        $this->_log->logTabelaNome = null;
        $this->_log->logTabelaColunaValor = null;
        $this->_log->logTabelaColunaNome = null;
        $colecaoCendenteFaturamento = array();
        $this->view->colecaoContextoCadastroEmpresa = array();
        $this->view->menu_lateral_itens["/contrato/adicionar"] = '<i class="icon-plus"></i> ADICIONAR NOVO CONTRATO';
        $this->view->menu_lateral_itens["/index/"] = '<i class="icon-tasks"></i> VOLTAR PARA TELA INICIAL';
        $this->view->menu_lateral_itens['/downloads/siss-comercial-empresa.pdf'] = '<i class=" icon-info-sign"></i> Manual';
        try {
            $Modelo = new Application_Model_FaturaCedente();
            $rst = $Modelo->fetchAll(array('fatura_cedente_status = ?' => 0), 'fatura_cedente_nome ASC');
            $colecaoCendenteFaturamento = ($rst->count() > 0) ? $rst->toArray() : array();
        } catch (Exception $exc) {
            throw $exc;
        }
        $colecao = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('empresa_pessoa');
        $this->view->colecaoResponsaveisEmpresa = array($colecao);
        $this->view->colecaoResponsaveisContrato = array($colecao);
        $this->view->colecaoResponsaveisFinanceiro = array($colecao);
        $this->view->colecaoCendenteFaturamento = $colecaoCendenteFaturamento;

        try {
            $this->_enviarParamentrosComunsParaFormulario();
        } catch (Exception $exc) {
            $this->_enviarMensagemDeExcecaoParaView($exc);
        }
    }

    public function avaliarRestricaoCreditoAction() {
        self::$_habilitarRegistrarLog = false;
        $this->_desabilitarTodoCarregamentoDeVisualizacao();
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            try {
                $dadosOrgao = array();
                $dadosOrgao['avaliacao_restricao_data_consulta'] = Util::dataBD($post['avaliacao_restricao_data_consulta']);
                $dadosOrgao['avaliacao_restricao_resultado'] = $post['avaliacao_restricao_resultado'];
                $dadosOrgao['avaliacao_restricao_status'] = 0;
                $dadosOrgao['avaliacao_restricao_observacao'] = $post['avaliacao_restricao_observacao'];
                $dadosOrgao['fk_orgao_avaliador_id'] = $post['fk_orgao_avaliador_id'];
                $dadosOrgao['fk_empresa_id'] = $post['fk_empresa_id'];
                $dadosOrgao['fk_usuario_id'] = $post['fk_usuario_id'];
                //var_dump($dadosOrgao);die;
                try {

                    $db = Zend_Db_Table::getDefaultAdapter();
                    $db->beginTransaction();


                    $modelOrgao = new Application_Model_AvaliacaoRestricao();
                    $gravarConsultaOrgao = $modelOrgao->insert($dadosOrgao);

                    $this->_log->logDetalhe = 'Tentativa de incluir avaliacao de restricao para a empresa id: ' . $post['fk_empresa_id'];
                    $this->_log->logTabelaNome = 'avaliacao_restricao';
                    $this->_log->logTabelaColunaValor = $gravarConsultaOrgao;
                    $this->_log->logTabelaColunaNome = 'avaliacao_restricao_id';

                    $db->commit();
                    self::$_habilitarRegistrarLog = true;
                    $feedback['erro'] = 0;
                    $feedback['msg'] = '</b> Registro salvo com sucesso! <a href="/empresa/alterar/id/' . $dadosOrgao['fk_empresa_id'] . '"><i class="icon-refresh"></i> Atualizar</a>';
                } catch (Exception $exc) {
                    echo $exc->getMessage();
                    $db->rollBack();
                    $feedback['erro'] = 1;
                }
            } catch (Exception $exc) {
                echo $exc->getMessage();
            }
            $this->view->feedback = $feedback;
            $this->_helper->layout->disableLayout();
            $this->renderScript('feedback.phtml');
        }
    }

    public function alterarAction() {

        $id = $_SESSION['empresa']['empresa_id'];
        $model_empresa = new Application_Model_Empresa();
        $model_endereco = new Application_Model_Endereco();
        $model_telefone = new Application_Model_Telefone();
        $model_empresa_pessoa = new Application_Model_EmpresaPessoa();
        $empresa_pessoa = $model_empresa_pessoa->obter($id);
        $empresa = $model_empresa->obter($id);
        $atributos = array();
        $empresa_financeiro = array();
        $empresa_contrato = array();
        $empresa_responsavel = array();

        $colecao = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('empresa_pessoa');
        $this->view->colecaoResponsaveisEmpresa = array($colecao);
        $this->view->colecaoResponsaveisContrato = array($colecao);
        $this->view->colecaoResponsaveisFinanceiro = array($colecao);

        $colecaoCendenteFaturamento = array();
        try {
            $Modelo = new Application_Model_FaturaCedente();
            $rst = $Modelo->fetchAll(array('fatura_cedente_status = ?' => 0), 'fatura_cedente_nome ASC');
            $colecaoCendenteFaturamento = ($rst->count() > 0) ? $rst->toArray() : array();
        } catch (Exception $exc) {
            throw $exc;
        }
        $this->view->colecaoCendenteFaturamento = $colecaoCendenteFaturamento;


        if ($empresa) {
            /*
             * $colecaoResponsaveisEmpresa = array();
             * $colecaoResponsaveisContrato = array();
             * $colecaoResponsaveisFinanceiro = array();
             *
             */
            foreach ($empresa_pessoa as $key => $value) {

                if ($value['empresa_pessoa_tipo'] === 'FINANCEIRO') {
                    $this->view->financeiro = $value;
                    //$colecaoResponsaveisFinanceiro[] = $value;
                }
                if ($value['empresa_pessoa_tipo'] === 'CONTRATO') {
                    $this->view->contrato = $value;
                    //$colecaoResponsaveisContrato[] = $value;
                }
                if ($value['empresa_pessoa_tipo'] === 'RESPONSAVEL') {
                    $this->view->responsavel = $value;
                    //$colecaoResponsaveisEmpresa[] = $value;
                }
            }


            if ($id > 0) {
                try {
                    $Resultado = $model_empresa_pessoa->fetchAll(array('fk_empresa_id = ?' => $id, 'empresa_pessoa_tipo = ?' => 'FINANCEIRO'));
                    if ($Resultado->count() > 0) {
                        $this->view->colecaoResponsaveisFinanceiro = $Resultado->toArray();
                    }

                    $Resultado = $model_empresa_pessoa->fetchAll(array('fk_empresa_id = ?' => $id, 'empresa_pessoa_tipo = ?' => 'RESPONSAVEL'));
                    if ($Resultado->count() > 0) {
                        $this->view->colecaoResponsaveisEmpresa = $Resultado->toArray();
                    }

                    $Resultado = $model_empresa_pessoa->fetchAll(array('fk_empresa_id = ?' => $id, 'empresa_pessoa_tipo = ?' => 'CONTRATO'));
                    if ($Resultado->count() > 0) {
                        $this->view->colecaoResponsaveisContrato = $Resultado->toArray();
                    }
                } catch (Exception $exc) {
                    $this->_enviarMensagemDeExcecaoParaView($exc);
                }
            }

            $this->view->colecaoContextoCadastroEmpresa = array();
            try {
                $this->_enviarParamentrosComunsParaFormulario();
            } catch (Exception $exc) {
                $this->_enviarMensagemDeExcecaoParaView($exc);
            }

            $orgaoModel = new Application_Model_OrgaoAvaliador();
            $this->view->orgaoAvaliador = $orgaoModel->buscarUsandoFiltro();
            $endereco = $model_endereco->fetchRow(array("endereco_id = ?" => $empresa["fk_endereco_id"]))->toArray();
            $telefones = $model_telefone->fetchAll(array("fk_empresa_id = ?" => $empresa["empresa_id"]))->toArray();
            $atributos = array_merge($empresa, $endereco);
            $atributos["telefones"] = $telefones;
        }
        $this->view->atributos = $atributos;
        $this->renderScript('contrato/form.phtml');
    }

    public function salvarAction() {

        if ($this->getRequest()->isPost()) {

            $model_empresa = new Application_Model_Empresa();
            $model_endereco = new Application_Model_Endereco();
            $model_telefone = new Application_Model_Telefone();

            $empresa_atributos = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('empresa');
            $endereco_atributos = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('endereco');

            $feedback = array('erro' => 1, 'msg' => '');
            $post = $this->getRequest()->getPost();
            $empresa_id = (int) $post["empresa_id"];
            $endereco_id = (int) $post["fk_endereco_id"];

            #validacao
            $camposParaValidar = array(
                'empresa_razao' => array('vazio' => '', 'nome' => 'Razão Social', 'tipo' => 'texto'),
                'empresa_cnpj' => array('vazio' => '', 'nome' => 'CNPJ', 'tipo' => 'texto'),
//                'endereco_cep' => array('vazio' => '', 'nome' => 'CEP', 'tipo' => 'texto'),
//                'endereco_logradouro' => array('vazio' => '', 'nome' => 'Logradouro', 'tipo' => 'texto'),
//                'endereco_bairro' => array('vazio' => '', 'nome' => 'Bairro', 'tipo' => 'texto'),
//                'endereco_cidade' => array('vazio' => '', 'nome' => 'Cidade', 'tipo' => 'texto'),
//                'endereco_uf' => array('vazio' => '', 'nome' => 'UF', 'tipo' => 'texto'),
//                'endereco_pais' => array('vazio' => '', 'nome' => 'País', 'tipo' => 'texto'),
//                'fk_unidade_id' => array('vazio' => '', 'nome' => 'Unidade', 'tipo' => 'texto')
            );
            $validacao = Util::validaCampos($camposParaValidar, $post);

            if (!Util::eCnpjValido($post['empresa_cnpj'])) {
                $validacao['erros'][] = 'O <b>CNPJ</b> informado não é válido.';
            }
            //$validacao['erros'][] = 'oi...';
            //var_dump($_POST['empresa_nome_responsavel']);
            // 31/03/2015 obrigar o campo 'fk_contexto_cadastro_empresa_id'
            #if (!isset($post['fk_contexto_cadastro_empresa_id']) || (int) $post['fk_contexto_cadastro_empresa_id'] == 0) {
            #    $validacao['erros'][] = 'O campo <b>Contexto do Cadastro</b> é obrigatório.';
            #}

            if (count($validacao['erros']) > 0) {
                $feedback['erro'] = 2;
                $feedback['msg'] = implode("<br/>", $validacao['erros']);
                $feedback['corrigir'] = $validacao['corrigir'];
            } else {
#separação dos dados
                $post["endereco_cep"] = str_replace(array(".", "-"), "", $post["endereco_cep"]);
                $post["empresa_cnpj"] = str_replace(array(".", "-", "/"), "", $post["empresa_cnpj"]);

                $dados_empresa = array();
                $dados_endereco = array();
                foreach ($post as $campo => $valor) {
                    if (array_key_exists($campo, $empresa_atributos)) {
                        $dados_empresa[$campo] = $valor;
                    } else if (array_key_exists($campo, $endereco_atributos)) {
                        $dados_endereco[$campo] = $valor;
                    }
                }

                // Valida e formata alguns parametros opicionais e se for o caso adiciona
                // o valor padrão.
                $dados_empresa['empresa_iss_recolhido_fonte'] = (is_numeric($dados_empresa['empresa_iss_recolhido_fonte'])) ? $dados_empresa['empresa_iss_recolhido_fonte'] : null;
                $dados_empresa['empresa_optante_simples'] = (is_numeric($dados_empresa['empresa_optante_simples'])) ? $dados_empresa['empresa_optante_simples'] : null;
                $dados_empresa['empresa_destacar_inss_nota'] = (is_numeric($dados_empresa['empresa_destacar_inss_nota'])) ? $dados_empresa['empresa_destacar_inss_nota'] : null;
                $dados_empresa['fk_fatura_cedente_id'] = (is_numeric($dados_empresa['fk_fatura_cedente_id'])) ? $dados_empresa['fk_fatura_cedente_id'] : null;

                # CLINICA CREDENCIADA
                $dados_empresa['empresa_clinica_credenciada'] = 1;
                if (!isset($post['empresa_clinica_credenciada']) || (int) $post['empresa_clinica_credenciada'] == 0) {
                    $dados_empresa['empresa_clinica_credenciada'] = 0;
                }

                #$dados_empresa['fk_contexto_cadastro_empresa_id'] = null;
                #if (isset($post['fk_contexto_cadastro_empresa_id']) && (int) $post['fk_contexto_cadastro_empresa_id'] > 0) {
                #$dados_empresa['fk_contexto_cadastro_empresa_id'] = $post['fk_contexto_cadastro_empresa_id'];
                #}

                try {

                    $db = Zend_Db_Table::getDefaultAdapter();
                    $db->beginTransaction();

                    try {

                        #endereco
                        if ($endereco_id > 0) {
                            $model_endereco->update($dados_endereco, array("endereco_id = ?" => $endereco_id));
                        } else {
                            $endereco_id = $model_endereco->insert($dados_endereco);
                            $dados_empresa["fk_endereco_id"] = $endereco_id;
                        }

                        #logo
                        if (isset($_FILES['empresa_logo']) && $_FILES['empresa_logo']['tmp_name'] != '') {
                            $name = $_FILES['empresa_logo']['name'];
                            $type = $_FILES['empresa_logo']['type'];
                            $size = $_FILES['empresa_logo']['size'];
                            $blob = file_get_contents($_FILES['empresa_logo']['tmp_name']);
                            $dados_empresa['empresa_logo_name'] = $name;
                            $dados_empresa['empresa_logo_type'] = $type;
                            $dados_empresa['empresa_logo_size'] = $size;
                            $dados_empresa['empresa_logo_blob'] = $blob;
                        }

                        #FISPQ
                        /* $dados_empresa['empresa_fispq'] = $post['fispq'];
                          if (isset($_FILES['fispq_file']) && $_FILES['fispq_file']['tmp_name'] != '') {
                          $name = $_FILES['fispq_file']['name'];
                          $type = $_FILES['fispq_file']['type'];
                          $size = $_FILES['fispq_file']['size'];
                          $blob = file_get_contents($_FILES['fispq_file']['tmp_name']);
                          $dados_empresa['empresa_fispq_name'] = $name;
                          $dados_empresa['empresa_fispq_type'] = $type;
                          $dados_empresa['empresa_fispq_size'] = $size;
                          $dados_empresa['empresa_fispq_blob'] = $blob;
                          } */

                        #Util::dump($dados_empresa);
                        #empresa
                        if ($empresa_id > 0) {
                            $model_empresa->update($dados_empresa, array("empresa_id = ?" => $empresa_id));
                        } else {
                            // Toda empresa cadastrada no formulário do sistema é definida como 'CLIENTE'.
                            $dados_empresa['empresa_tipo'] = 'CLIENTE';
                            $empresa_id = $model_empresa->insert($dados_empresa);
                        }

                        #telefones
                        //Util::dump($post["telefone"]);
                        if (isset($post["telefone"])) {
                            $model_telefone->delete(array("fk_empresa_id = ?" => $empresa_id));
                            $ddi = $post["telefone"][0];
                            $post["telefone"][1] = str_replace(array('(', ')', ' ', '-'), "", $post["telefone"][1]);
                            $ddd = substr($post["telefone"][1], 0, 2);
                            $numero = substr($post["telefone"][1], 2);
                            $ramal = $post["telefone"][2];
                            $model_telefone->insert(array(
                                "telefone_ddi" => $ddi,
                                "telefone_ddd" => $ddd,
                                "telefone_numero" => $numero,
                                "telefone_ramal" => $ramal,
                                "fk_empresa_id" => $empresa_id,
                            ));
                        }

                        $colecaProcurarPor = array('(', ')', '?', '-', '*', ' ', '.', '_');
                        /*
                         * AUTOR: Silas Stoffel
                         * ID: 2014-10-22 14:000
                         * SOLICITANTE:  NATÁLIA MARIA RIBEIRO BRAZ
                         * CHAMADO ID: 461
                         * FINALIDADE: Permitir adicionar mais campos para inserção na seção de responsáveis pela
                         * empresa
                         */
                        if (isset($post['empresa_nome_responsavel'])) {
                            $model_empresa_pessoa = new Application_Model_EmpresaPessoa();
                            $model_empresa_pessoa->delete(array('fk_empresa_id = ?' => $empresa_id, 'empresa_pessoa_tipo = ?' => 'RESPONSAVEL'));

                            $colecaoNomes = $_POST['empresa_nome_responsavel'];
                            $colecaoTelefones = $_POST['empresa_telefone_responsavel'];
                            $colecaoCelulares = $_POST['empresa_celular_responsavel'];
                            $colecaoEmails = $_POST['empresa_email_responsavel'];

                            foreach ($colecaoNomes as $indexador => $valor) {
                                if (strlen($colecaoNomes[$indexador]) == 0) {
                                    continue;
                                }
                                $inserir = array(
                                    "empresa_pessoa_nome" => $colecaoNomes[$indexador],
                                    "empresa_pessoa_telefone" => str_replace($colecaProcurarPor, '', $colecaoTelefones[$indexador]),
                                    "empresa_pessoa_celular" => str_replace($colecaProcurarPor, '', $colecaoCelulares[$indexador]),
                                    "empresa_pessoa_email" => $colecaoEmails[$indexador],
                                    "empresa_pessoa_tipo" => 'RESPONSAVEL',
                                    "fk_empresa_id" => $empresa_id,
                                );
                                $model_empresa_pessoa->insert($inserir);
                            }
                        }
                        // FIM ID: 2014-10-22 14:000

                        /*
                         * AUTOR: Silas Stoffel
                         * ID: 2014-11-06 13:31
                         * SOLICITANTE:  NATÁLIA MARIA RIBEIRO BRAZ
                         * CHAMADO ID: 461
                         * FINALIDADE: Permitir adicionar mais campos para inserção na seção de responsáveis pelo contrato
                         */
                        if (isset($post['empresa_nome_contrato'])) {
                            $model_empresa_pessoa = new Application_Model_EmpresaPessoa();
                            $model_empresa_pessoa->delete(array('fk_empresa_id = ?' => $empresa_id, 'empresa_pessoa_tipo = ?' => 'CONTRATO'));

                            $colecaoNomes = $_POST['empresa_nome_contrato'];
                            $colecaoTelefones = $_POST['empresa_telefone_contrato'];
                            $colecaoCelulares = $_POST['empresa_celular_contrato'];
                            $colecaoEmails = $_POST['empresa_email_contrato'];

                            foreach ($colecaoNomes as $indexador => $valor) {
                                if (strlen($colecaoNomes[$indexador]) == 0) {
                                    continue;
                                }
                                $inserir = array(
                                    "empresa_pessoa_nome" => $colecaoNomes[$indexador],
                                    "empresa_pessoa_telefone" => str_replace($colecaProcurarPor, '', $colecaoTelefones[$indexador]),
                                    "empresa_pessoa_celular" => str_replace($colecaProcurarPor, '', $colecaoCelulares[$indexador]),
                                    "empresa_pessoa_email" => $colecaoEmails[$indexador],
                                    "empresa_pessoa_tipo" => 'CONTRATO',
                                    "fk_empresa_id" => $empresa_id,
                                );
                                $model_empresa_pessoa->insert($inserir);
                            }
                        }
                        // FIM: ID: 2014-11-06 13:31

                        /*
                         * AUTOR: Silas Stoffel
                         * ID: 2014-11-06 14:10
                         * SOLICITANTE:  NATÁLIA MARIA RIBEIRO BRAZ
                         * CHAMADO ID: 461
                         * FINALIDADE: Permitir adicionar mais campos para inserção na seção de responsáveis pelo FINANCEIRO
                         */
                        if (isset($post['empresa_nome_financeiro'])) {
                            $model_empresa_pessoa = new Application_Model_EmpresaPessoa();
                            $model_empresa_pessoa->delete(array('fk_empresa_id = ?' => $empresa_id, 'empresa_pessoa_tipo = ?' => 'FINANCEIRO'));

                            $colecaoNomes = $_POST['empresa_nome_financeiro'];
                            $colecaoTelefones = $_POST['empresa_telefone_financeiro'];
                            $colecaoCelulares = $_POST['empresa_celular_financeiro'];
                            $colecaoEmails = $_POST['empresa_email_financeiro'];

                            foreach ($colecaoNomes as $indexador => $valor) {
                                if (strlen($colecaoNomes[$indexador]) == 0) {
                                    continue;
                                }
                                $inserir = array(
                                    "empresa_pessoa_nome" => $colecaoNomes[$indexador],
                                    "empresa_pessoa_telefone" => str_replace($colecaProcurarPor, '', $colecaoTelefones[$indexador]),
                                    "empresa_pessoa_celular" => str_replace($colecaProcurarPor, '', $colecaoCelulares[$indexador]),
                                    "empresa_pessoa_email" => $colecaoEmails[$indexador],
                                    "empresa_pessoa_tipo" => 'FINANCEIRO',
                                    "fk_empresa_id" => $empresa_id,
                                );
                                $model_empresa_pessoa->insert($inserir);
                            }
                        }
                        // FIM ID: 2014-11-06 14:10
                        /*
                          if (isset($post['empresa_nome_contrato'])) {
                          $model_empresa_pessoa = new Application_Model_EmpresaPessoa();
                          $model_empresa_pessoa->insert(array(
                          "empresa_pessoa_nome" => $post['empresa_nome_contrato'],
                          "empresa_pessoa_telefone" => $post['empresa_telefone_contrato'],
                          "empresa_pessoa_celular" => $post['empresa_celular_contrato'],
                          "empresa_pessoa_email" => $post['empresa_email_contrato'],
                          "empresa_pessoa_tipo" => 'CONTRATO',
                          "fk_empresa_id" => $empresa_id,
                          ));
                          }
                          if (isset($post['empresa_nome_financeiro'])) {
                          $model_empresa_pessoa = new Application_Model_EmpresaPessoa();
                          $model_empresa_pessoa->insert(array(
                          "empresa_pessoa_nome" => $post['empresa_nome_financeiro'],
                          "empresa_pessoa_telefone" => $post['empresa_telefone_financeiro'],
                          "empresa_pessoa_celular" => $post['empresa_celular_financeiro'],
                          "empresa_pessoa_email" => $post['empresa_email_financeiro'],
                          "empresa_pessoa_tipo" => 'FINANCEIRO',
                          "fk_empresa_id" => $empresa_id,
                          ));
                          }
                         */
                        #salva
                        $db->commit();

                        $feedback['erro'] = 0;
                        $feedback['msg'] = 'Empresa <b>' . $dados_empresa['empresa_razao'] . '</b> salva com sucesso! &ndash; <a href="/empresa/alterar"><i class="fa fa-refresh"></i> Atualizar</a>';
                    } catch (Zend_Exception $e) {
                        $db->rollBack();
                        $feedback['erro'] = 1;
                        $feedback['msg'] = 'Empresa <b>' . $dados_empresa['empresa_razao'] . '</b> não pode ser salva!';
                        $feedback['detalhes'] = $e->getMessage();
                    }
                } catch (Zend_Exception $ee) {
                    $feedback['erro'] = 1;
                    $feedback['msg'] = 'Não foi possível estabelecer uma comunicação com o banco de dados!';
                    $feedback['detalhes'] = $ee->getMessage();
                }
            }
            $this->view->feedback = $feedback;
            $this->_helper->layout->disableLayout();
            $this->renderScript('feedback.phtml');
        }
    }

}
