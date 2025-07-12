<?php

class DocumentoOperacionalController extends Controller {

    private static $_idUnidadeAtivaDoUsuarioLogado = null;

    public function init() {
        parent::init();
        $unidadeId = isset($_SESSION['usuario']['unidadeativa']['unidade_id']) ? $_SESSION['usuario']['unidadeativa']['unidade_id'] : null;
        self::$_idUnidadeAtivaDoUsuarioLogado = $unidadeId;
        $this->controlador = 'Relatórios Operacionais';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
    }

    public function indexAction() {
        $this->_log->logDetalhe = 'Listagem de opcoes de relatorios operacionais';
        $acessosLiberados = isset($_SESSION['acesso']) && is_array($_SESSION['acesso']) ? $_SESSION['acesso'] : array();
        $documentos = array();
        $documentos["operacionais"] = array();
        $documentos["gerenciais"] = array();
        /*
         * Coloque aqui parte da URL (Controller + Acao) que abaixo haverá
         * uma funcionalidade para verificar se o usuário está autorizado.
         */
        $colecaoUrlExistentes = array(
            array('href' => '/documento-operacional/imprimir-guia-exames', 'target' => '', 'texto' => 'Guia de Exames'),
            array('href' => '/documento-operacional/imprimir-lista-evidencia', 'target' => '', 'texto' => 'Lista de Evidência'),
            array('href' => '/documento-operacional/imprimir-tabela', 'target' => '', 'texto' => 'Tabela de Precificação de Produtos'),
            array('href' => '/documento-operacional/relacao-agendados', 'target' => '', 'texto' => 'Relação de Agendados'),
            array('href' => '/documento-operacional/relatorio-empresa', 'target' => '', 'texto' => 'Relatório Empresa'),
            array('href' => '/documento-operacional/relatorio-contrato', 'target' => '', 'texto' => 'Relatório Contrato'),
            array('href' => '/documento-operacional/relatorio-exames-alterados', 'target' => '', 'texto' => 'Relatório de Exames Alterados'),
            array('href' => '/documento-operacional/relatorio-procedimentos-realiz/formulario', 'target' => '', 'texto' => 'Relatório de Procedimentos Realizados'),
            array('href' => '/documento-operacional/relatorio-empregados', 'target' => '', 'texto' => 'Relatório de Empregados'),
            array('href' => '/documento-operacional/relatorio-de-funcionarios', 'target' => '', 'texto' => 'Relatório de Funcionarios'),
            array('href' => '/documento-operacional/relatorio-treinamento-realizad', 'target' => '', 'texto' => 'Relatório Treinamentos Realizados'),
            array('href' => '/documento-operacional/relatorio-proposta-contrato', 'target' => '', 'texto' => 'Relatório Contratos e Propostas'),
            array('href' => '/documento-operacional/convocacao-de-periodico', 'target' => '', 'texto' => 'Convocação de Periódicos'),
            array('href' => '/documento-operacional/emitir-relatorio-pendencia-cli', 'target' => '', 'texto' => 'Pendência Clínica'),
        );
        foreach ($colecaoUrlExistentes as $itemUrl) {
            $podeAcessar = in_array($itemUrl['href'], $acessosLiberados);
            if ($podeAcessar) {
                $documentos["operacionais"][] = $itemUrl;
            }
        }
        $this->view->listaDocumentos = $documentos;
    }

    public function relatorioPropostaContratoAction() {
        $this->_log->logDetalhe = 'Abriu formulario para emitir o relatorio de propostas e contratos';
        $this->_log->logEvento = 'relatorio-proposta-contrato';
        $this->acao = 'Relatório de Contratos e Propostas';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
        $template = "documento-operacional/relatorio-proposta-contrato/form.phtml";
        $periodoRelatorio = null;
        $colecaoResultadosRelatorios = array();
        if ($this->getRequest()->isPost()) {
            $this->_log->logDetalhe = 'Processando relatorio de propostas e contratos';
            $parametroDataInicio = $this->_getParam('param_data_inicio');
            $parametroDataTermino = $this->_getParam('param_data_termino');
            $periodoRelatorio = $parametroDataInicio . ' a ' . $parametroDataTermino;
            if ($parametroDataInicio != null && $parametroDataTermino != null) {
                $unidadeId = (int) isset($_SESSION['usuario']['unidadeativa']) ? $_SESSION['usuario']['unidadeativa']['unidade_id'] : 0;
                try {
                    $ModeloEmpresaContexto = new Application_Model_ContextoCadastroEmpresa();
                    $Resultado = $ModeloEmpresaContexto->fetchRow(array('contexto_cadastro_empresa_codigo_fixo = ?' => 'EMP_AVULSA', 'contexto_cadastro_empresa_status = ?' => 0));
                    $colecaoContextoIgnorados = array();
                    if ($Resultado != null) {
                        $colecaoContextoIgnorados[] = $Resultado->contexto_cadastro_empresa_id;
                    }
                    $ModeloOS = new Application_Model_Os();
                    $resultadoComando = $ModeloOS->obterDadosEstatisticoEmissaoOs($unidadeId, Util::dataBD($parametroDataInicio), Util::dataBD($parametroDataTermino), $colecaoContextoIgnorados);
                    if (is_array($resultadoComando) && count($resultadoComando) > 0) {
                        $colecaoResultadosRelatorios = $resultadoComando;
                        $template = "documento-operacional/relatorio-proposta-contrato/xls.phtml";
                        $this->_desabilitarTodoCarregamentoDeVisualizacao();
                    }
                } catch (Exception $exc) {
                    $this->_enviarMensagemDeExcecaoParaView($exc);
                }
            }
        }
        $this->view->periodoRelatorio = $periodoRelatorio;
        $this->view->colecaoResultadosRelatorios = $colecaoResultadosRelatorios;
        $this->renderScript($template);
    }

    public function relatorioTreinamentoRealizadAction() {
        $this->_log->logDetalhe = 'Abriu formulario para emitir o relatorio de treinamento realizados';
        $this->_log->logEvento = 'relatorio-treinamento-realizado';
        $this->acao = 'Relatório de Treinamentos Realizados';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao('Relatório', 'Operacional', 'Relatório de Treinamentos Realizados');
        $template = "documento-operacional/relatorio-treinamento-realizad/form-relatorio-treinamento-realizad.phtml";

        $colecaoUsuarioInstrutores = array();
        $colecaoEmpresas = array();
        $colecaoProdutos = array();
        $colecaoResultadoRelatorio = array();
        $paramPeriodoRelatorio = '';
        $paramFormatoSaida = '';
        try {
            $ModeloUsuario = new Application_Model_Usuario();
            $colecaoUsuarioInstrutores = $ModeloUsuario->listarInstrutoresDeTreinamento('pessoa.pessoa_nome ASC');

            $ModeloEmpresa = new Application_Model_Empresa();
            $colecaoEmpresas = $ModeloEmpresa->obterEmpresasQueJaRealizaramTreinamentos('empresa.empresa_fantasia ASC');

            $ModeloProduto = new Application_Model_Produto();
            $filtro = "cp.categoriadoproduto_codigo_fixo = '0003' AND p.produto_status = 0";
            $colecaoProdutos = $ModeloProduto->buscarCompletaUsandoClausula($filtro, 'p.produto_nome ASC');
        } catch (Exception $exc) {
            $this->_enviarMensagemDeExcecaoParaView($exc);
        }

        if ($this->getRequest()->isPost()) {
            $this->_log->logDetalhe = 'Processando relatorio de treinamento realizados';
            $paramDataInicio = $this->_getParam('treinamento_agenda_data_inicio');
            $paramDataTermino = $this->_getParam('treinamento_agenda_data_termino');
            $paramInstrutorId = $this->_getParam('treinamento_agenda_fk_usuario_id_instrutor', 0);
            $paramEmpresaId = $this->_getParam('treinamento_agenda_fk_empresa_id', 0);
            $paramProdutoId = $this->_getParam('treinamento_agenda_fk_produto_id', 0);
            $paramFormatoSaida = $this->_getParam('param_formato_saida', 'xls');
            $paramPeriodoRelatorio = $paramDataInicio . ' a ' . $paramDataTermino;
            $unidadeId = (int) isset($_SESSION['usuario']['unidadeativa']) ? $_SESSION['usuario']['unidadeativa']['unidade_id'] : 0;
            try {
                $ModeloTreinamento = new Application_Model_Treinamento();
                $inicio = Util::dataBD($paramDataInicio);
                $fim = Util::dataBD($paramDataTermino);
                $resultado = $ModeloTreinamento->obterDetalheAgendaDeTreinamentosRealizadosAgrupadosPorEmpresa($unidadeId, $inicio, $fim, $paramProdutoId, $paramInstrutorId, $paramEmpresaId);
                if (is_array($resultado) && count($resultado) > 0) {
                    $template = "documento-operacional/relatorio-treinamento-realizad/resultado-relatorio.phtml";
                    $colecaoResultadoRelatorio = $resultado;
                    $this->_desabilitarCarregamentoDoTemplate();
                }
            } catch (Exception $exc) {
                $this->_enviarMensagemDeExcecaoParaView($exc);
            }
        }

        $this->view->colecaoResultadoRelatorio = $colecaoResultadoRelatorio;
        $this->view->colecaoUsuarioInstrutores = $colecaoUsuarioInstrutores;
        $this->view->colecaoEmpresas = $colecaoEmpresas;
        $this->view->colecaoProdutos = $colecaoProdutos;
        $this->view->paramPeriodoRelatorio = $paramPeriodoRelatorio;
        $this->view->paramFormatoSaida = $paramFormatoSaida;
        $this->renderScript($template);
    }

    public function relatorioDeFuncionariosAction() {
        $this->_log->logDetalhe = 'Formulario de relatorio funcionarios - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-de-funcionario';
        $itensResultadoRelatorio = array();
        $script = "/documento-operacional/relatorio-de-funcionarios/formulario.phtml";
        $empresas = array();
        $unidade = self::$_idUnidadeAtivaDoUsuarioLogado;
        $clausulaComando = "empresa.fk_unidade_id = {$unidade}";
        $clausulaComando .= " AND empresa.empresa_status = 0";
        $clausulaComando .= " AND EXISTS (SELECT fk_empresa_id FROM contratante cntr WHERE cntr.fk_empresa_id = empresa.empresa_id)";
        try {

            $modeloEmpresa = new Application_Model_Empresa();
            $resultado = $modeloEmpresa->buscarCompletaUsandoClausula($clausulaComando);

            if (is_array($resultado) && count($resultado) > 0) {
                $empresas = $resultado;
            }
        } catch (Exception $ex) {

        }
        if ($this->getRequest()->isPost()) {
            $parametros = $this->getRequest()->getPost();
            $empresa_id = (isset($parametros['empresa_id']) && !empty($parametros['empresa_id'])) ? $parametros['empresa_id'] : null;
            $contrato_id = (isset($parametros['contrato_id']) && !empty($parametros['contrato_id'])) ? $parametros['contrato_id'] : null;
            $nome_funcionario = (isset($parametros['pessoa_nome']) && !empty($parametros['pessoa_nome'])) ? $parametros['pessoa_nome'] : null;

            try {
                $itensResultadoRelatorio = self::processarRelatorioDeFuncionarios($empresa_id, $contrato_id, $nome_funcionario);
            } catch (Exception $ex) {
                $this->_enviarMensagemDeExcecaoParaView($ex);
            }
            $this->_log->logDetalhe = 'Resultado de relatorio funcionarios - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-de-funcionario';
        }
        $this->view->itensResultadoRelatorio = $itensResultadoRelatorio;
        $this->view->empresas = $empresas;
        $this->renderScript($script);
    }

    public function relatorioProcedimentosRealizAction() {
        $this->_log->logDetalhe = 'Relatorio de procedimentos realizados - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-de-procedimentos-realizados';
        $script = "/documento-operacional/relatorio-procedimentos-realiz/formulario.phtml";
        $modelProduto = new Application_Model_Produto();
        $this->view->produtos = $modelProduto->obterTodos();


        /* ID: 2014-10-20 13:30
         * Código Chamado Sistema: 477
         * Autor: Silas Stoffel
         * Solicitado Por: Marcio
         * Escopo: Criar os seguintes filtros no relatório:
         * - Tipo de Exame
         * - Contrato
         * - Empresa
         * - CNPJ
         */
        $colecaoTipoExame = array();
        try {
            $ModeloTipoExame = new Application_Model_TipoExame();
            $ResultadoComando = $ModeloTipoExame->fetchAll(array('tipoexame_status = ?' => 0));
            if ($ResultadoComando) {
                $colecaoTipoExame = $ResultadoComando->toArray();
            }
        } catch (Exception $ex) {
            $this->_enviarMensagemDeExcecaoParaView($ex);
        }
        // Fim ID: 2014-10-20 13:30


        if ($this->getRequest()->isPost()) {

            $this->_log->logDetalhe = 'Resultado de procedimentos realizados - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-de-procedimentos-realizados';

            $data1 = Util::dataBD($this->_getParam('data1'));
            $data2 = Util::dataBD($this->_getParam('data2'));
            $produto = $this->_getParam('produtos');

            $paramEmpresaRazaoSocial = $this->_getParam('empresa_razao');
            $paramEmpresaCnpj = $this->_getParam('empresa_cnpj');
            $paramContratoNumero = $this->_getParam('contrato_numero');
            $paramTipoExameId = (int) $this->_getParam('tipoexame_id', 0);

            $unidade = self::$_idUnidadeAtivaDoUsuarioLogado;
            if ($produto > 0) {
                $produtos = " AND produto.produto_id = '{$produto}'";
            } else {
                $produtos = "";
            }

            // $paramEmpresaCnpj = '', $paramEmpresaRazaoSocial = null, $paramContratoNumero = null, $paramTipoExameId = 0

            $modelFatura = new Application_Model_Fatura();
            $this->view->resumido = $modelFatura->obterFornecedorComPendenciaComProdutosRelatorio($data1, $data2, $unidade, true, $produtos, $paramEmpresaCnpj, $paramEmpresaRazaoSocial, $paramContratoNumero, $paramTipoExameId);
            $this->view->detalhado = $modelFatura->obterFornecedorComPendenciaComProdutosRelatorio($data1, $data2, $unidade, false, $produtos, $paramEmpresaCnpj, $paramEmpresaRazaoSocial, $paramContratoNumero, $paramTipoExameId);

            //echo count($this->view->detalhado);
            //exit(0);


            $this->view->dataV = $data1;
            $this->view->dataVi = $data2;
            //$this->_helper->layout->disableLayout();
            $this->_desabilitarTodoCarregamentoDeVisualizacao();

            $script = '/documento-operacional/relatorio-procedimentos-realiz/procedimento-realizado.phtml';
        }
        $this->view->colecaoTiposExames = $colecaoTipoExame;
        $this->renderScript($script);
    }

    private static function processarRelatorioDeFuncionarios($empresa_id = null, $contrato_id = null, $nome_funcionario = null) {
        $resultado = array();
        $listaComFiltros = array();
        if (is_numeric($empresa_id)) {
            $listaComFiltros[] = "funcionario.fk_empresa_id = {$empresa_id}";
        }
        if (is_numeric($contrato_id)) {
            $listaComFiltros[] = "funcionario.fk_contrato_id = {$contrato_id}";
        }
        $listaComFiltros[] = "pessoa.pessoa_nome LIKE '%{$nome_funcionario}%' ";
        $filtros = implode(' AND ', $listaComFiltros);
        try {
            $modeloFuncinario = new Application_Model_Funcionario();
            $resultado = $modeloFuncinario->obterPeloFiltro($filtros);
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultado;
    }

    public function imprimirPcmsoAction() {

        $pcmso = new Application_Model_Pcmso();
        $this->view->pcmso = $pcmso->obter((int) $this->_getParam('id', 0));

        $funcionario = new Application_Model_Funcionario();
        $tabelaPorIdade = $funcionario->obterTotaisPorIdade((int) $this->view->pcmso['fk_contrato_id'], (int) $this->view->pcmso['fk_empresa_id']);
        $this->view->tabelaPorIdade = $tabelaPorIdade;

        $this->_helper->layout->disableLayout();
        $this->renderScript('documento-operacional/pcmso/pdf.phtml');
    }

    public function imprimirListaEvidenciaAction() {
        if ($this->getRequest()->isPost()) {
            $this->_log->logDetalhe = 'Resultado relatorio de lista de evidencia - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-de-lista-de-evidencia';
            # Validação
            $aDataInicialEstaEmFormatoCorreto = preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $this->_getParam('data_inicio', '')) ? true : false;
            $aDataFinalEstaEmFormatoCorreto = preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $this->_getParam('data_fim', '')) ? true : false;

            if ($aDataInicialEstaEmFormatoCorreto && $aDataFinalEstaEmFormatoCorreto) {
                $view = "/documento-operacional/lista-evidencia/imprimir.phtml";

                # Parâmetros
                $datainicio = $this->_getParam('data_inicio');
                $datafim = $this->_getParam('data_fim');
                $this->view->datainicio = $datainicio;
                $this->view->datafim = $datafim;
                $datainicio = preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $datainicio) ? util::databd($datainicio) : null;
                $datafim = preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $datafim) ? util::databd($datafim) : null;
                $especialistaid = (int) $this->_getParam('especialista_id', 0);
                $exameid = (int) $this->_getParam('exame_id', 0);

                $filtro = " 1 = 1 ";
                $filtro .= " AND e.fk_unidade_id = " . (int) self::$_unidadeIdEmContexto;
                $filtro .= " AND a.agenda_status = 0 ";
                if ($especialistaid > 0) {
                    $filtro .= " AND a.agenda_data_clinico BETWEEN '{$datainicio}' AND '{$datafim}' ";
                    $filtro .= " AND a.fk_pessoa_especialidade_id = {$especialistaid} ";
                } else {
                    $filtro .= " AND a.agenda_data_exame BETWEEN '{$datainicio}' AND '{$datafim}' ";
                }
                $where = $filtro;
                # Especialista
                $pessoa = new Application_Model_Pessoa();
                $especialista = $pessoa->listarMedico($especialistaid);
                $this->view->especialista = isset($especialista[0]) ? $especialista[0] : array();

                # Consulta
                $sql = "SELECT
                                ta.tipoexame_nome,
                                ta.tipoexame_sigla,
                                DATE_FORMAT(a.agenda_data_exame, '%d/%m/%Y') AS agenda_data_exame,
                                DATE_FORMAT(a.agenda_data_clinico, '%d/%m/%Y') AS agenda_data_clinico,
                                p.pessoa_nome,
                                p.pessoa_cpf,
                                e.empresa_razao,
                                f.funcao_nome,
                                pro.produto_nome
                        FROM agenda a
                                JOIN tipoexame ta ON ta.tipoexame_id = a.fk_tipoexame_id
                                JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                                JOIN empresa e ON e.empresa_id = a.fk_empresa_id
                                JOIN alocacao al ON al.alocacao_id = a.fk_alocacao_id
                                JOIN funcao f ON f.funcao_id = al.fk_funcao_id
                                JOIN (
                                        SELECT p.produto_id,p.produto_codigo_fixo,p.produto_nome,p.produto_sigla,p1.pcmso_id,p1.fk_empresa_id,p1.fk_contrato_id,ip.fk_cargo_id,ip.fk_funcao_id,ip.fk_setor_id,ipp.fk_tipoexame_id
                                        FROM pcmso p1
                                             JOIN item_pcmso ip ON(ip.fk_pcmso_id = p1.pcmso_id AND ip.item_pcmso_status = 0)
                                             JOIN item_pcmso_produto ipp ON(ipp.fk_item_pcmso_id = ip.item_pcmso_id)
                                             JOIN produto p ON(p.produto_id = ipp.fk_produto_id)
                                             JOIN tipoexame te ON (te.tipoexame_id = ipp.fk_tipoexame_id)
                                             JOIN periodo p3 ON(p3.periodo_id = ipp.fk_periodo_id)
                                        WHERE p1.pcmso_status = 0
                                ) AS pro ON (
                                              pro.produto_id = {$exameid}
                                              AND pro.fk_empresa_id = a.fk_empresa_id
                                              AND pro.fk_contrato_id = a.fk_contrato_id
                                              AND pro.pcmso_id = (SELECT p2.pcmso_id FROM pcmso p2 WHERE p2.pcmso_status = 0 AND p2.fk_contrato_id = a.fk_contrato_id AND p2.fk_empresa_id = a.fk_empresa_id ORDER BY p2.pcmso_data_validade DESC LIMIT 1)
                                              AND pro.fk_cargo_id = al.fk_cargo_id
                                              AND pro.fk_funcao_id = al.fk_funcao_id
                                              AND pro.fk_setor_id = al.fk_setor_id
                                              AND pro.fk_tipoexame_id = a.fk_tipoexame_id
                                            )
                        WHERE {$where}
                              AND e.empresa_tipo = 'CLIENTE'
                              AND a.agenda_status = 0
                        ORDER BY p.pessoa_nome ASC";
                // echo $sql;
                // exit(0);
                $adapter = Zend_Db_Table::getDefaultAdapter();
                $prepare = $adapter->prepare($sql);
                $prepare->execute();
                $lista = $prepare->fetchAll();

                if (count($lista) == 0) {
                    $view = "/documento-operacional/lista-evidencia/lista-evidencia.phtml";

                    # Médicos
                    $pessoa = new Application_Model_Pessoa();
                    $medicos = $pessoa->listarMedicoEEspecialidade();
                    $this->view->medicos = $medicos;

                    # Exames
                    $produto = new Application_Model_Produto();
                    $exames = $produto->obterExames();
                    $this->view->exames = $exames;

                    $this->view->erro2 = "Nenhum resultado correspondentes ao seu filtro";
                } else {
                    $this->view->lista = $lista;
                    $this->_helper->layout->disableLayout();
                }
            } else {

                $view = "/documento-operacional/lista-evidencia/lista-evidencia.phtml";

                # Médicos
                $pessoa = new Application_Model_Pessoa();
                $medicos = $pessoa->listarMedicoEEspecialidade();
                $this->view->medicos = $medicos;

                # Exames
                $produto = new Application_Model_Produto();
                $exames = $produto->obterExames();
                $this->view->exames = $exames;

                $this->view->erro2 = "Verifique as datas informadas";
            }
        } else {
            $this->_log->logDetalhe = 'Formulario relatorio de lista de evidencia - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-de-lista-de-evidencia';
            $view = "/documento-operacional/lista-evidencia/lista-evidencia.phtml";

            # Médicos
            $pessoa = new Application_Model_Pessoa();
            $medicos = $pessoa->listarMedicoEEspecialidade();
            $this->view->medicos = $medicos;

            # Exames
            $produto = new Application_Model_Produto();
            $exames = $produto->obterExames();
            $this->view->exames = $exames;
        }
        $this->renderScript($view);
    }

    public function imprimirFaturaAction() {
        #$this->_log->logDetalhe = 'Resultado relatorio de fatura - Relatorio Operacional';
        #$this->_log->logEvento = 'relatorio-impressao-fatura';
        $faturaId = (int) $this->_getParam('faturaid', 0);
        try {
            $ModeloFatura = new Application_Model_Fatura();
            $Fatura = $ModeloFatura->fetchRow(array('fatura_id = ?' => $faturaId));
            if (!$Fatura) {
                throw new Exception('Fatura não encontrada');
            }
            $versao = $Fatura->fatura_versao;
            if (version_compare($versao, '1.0', '=')) {
                $this->_imprimirFaturaV1($faturaId);
            } else if (version_compare($versao, '2.0', '=')) {
                $this->_imprimirFaturaV2($faturaId);
            } else {
                throw new Exception('Fatura não encontrada');
            }
        } catch (Exception $exc) {
            throw $exc;
        }
    }

    private function _imprimirFaturaV2($paramFaturaId) {
        require_once(APPLICATION_PATH . '/business/FaturamentoBusiness.php');
        $fatura = null;
        $detalhamentos = array();
        try {
            $F = new FaturamentoBusiness();
            $fatura = $F->obterColecaoProdutosFaturados($paramFaturaId);
            if (!$fatura) {
                throw new Exception('Fatura não localizada');
            }

            $comando = "SELECT *
                        FROM (
                                SELECT
                                        p.pessoa_cpf AS funcionario_cpf,
                                        p.pessoa_nome AS funcionario_nome,
                                        pro.produto_nome AS procedimento_nome,
                                        pa.produto_agenda_data_programada AS data_execucao,
                                        pf.produto_fatura_valor AS valor,
                                        pf.produto_fatura_quantidade AS quantidade,
                                        pf.produto_fatura_valor_total AS valor_total
                                FROM produto_fatura AS pf
                                     JOIN fatura AS f ON (f.fatura_id = pf.fk_fatura_id AND f.fatura_status = 0)
                                     JOIN produto_agenda AS pa ON pa.produto_agenda_id = pf.fk_produto_agenda_id
                                     JOIN agenda AS a ON a.agenda_id = pa.fk_agenda_id
                                     JOIN pessoa AS p ON p.pessoa_id = a.fk_pessoa_id
                                     JOIN produto AS pro ON pro.produto_id = pa.fk_produto_id
                                WHERE   f.fatura_id  = ?
                                        AND pf.produto_fatura_status = 0
                                UNION ALL
                                SELECT
                                       p.pessoa_cpf AS funcionario_cpf,
                                       p.pessoa_nome AS funcionario_nome,
                                       pro.produto_nome AS procedimento_nome,
                                       esf.execucao_servico_funcionario_data AS data_execucao,
                                       es.execucao_servico_valor_unitario AS valor,
                                       es.execucao_servico_quantidade AS quantidade,
                                       es.execucao_servico_valor_total AS valor_total
                                FROM execucao_servico AS es
                                     JOIN execucao_servico_funcionario AS esf ON (esf.fk_execucao_servico_id = es.execucao_servico_id AND esf.execucao_servico_funcionario_status = 0 AND esf.execucao_servico_funcionario_presente = 1)
                                     JOIN pessoa AS p ON p.pessoa_id = esf.fk_pessoa_id
                                     JOIN produto AS pro ON pro.produto_id = es.fk_produto_id
                                     JOIN produto_fatura AS pf ON pf.fk_execucao_servico_id = es.execucao_servico_id
                                WHERE es.execucao_servico_status = 0
                                      AND pf.fk_fatura_id = ?
                        ) AS comando_processado
                        ORDER BY comando_processado.funcionario_nome ASC";
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $detalhamentos = $Cnx->fetchAll($comando, array($paramFaturaId, $paramFaturaId));
        } catch (Exception $exc) {
            throw $exc;
        }

        $this->view->fatura = $fatura;
        $this->view->detalhamentos = $detalhamentos;
        $visualizacao = '/documento-operacional/imprimir-fatura/imprimir-v2.phtml';
        $this->_desabilitarCarregamentoDoTemplate();
        $this->renderScript($visualizacao);
    }

    private function _imprimirFaturaV1($paramfaturaId) {
        $visualizacao = '/documento-operacional/imprimir-fatura/imprimir.phtml';
        $parametroFaturaId = $paramfaturaId;
        #$uni = $_SESSION['usuario']['unidadeativa']['unidade_id'];
        $consultaFatura = array();
        $unidade = array();
        $valoresConsolidados = array(
            'fatura' => array(),
            'produtosContidos' => array(),
        );
        try {
            // Resgata os dados da fatura
            $filtro = "fatura_id = {$parametroFaturaId}";
            $f = new Application_Model_Fatura();
            #$consultaFatura = $f->buscaCompletaUsandoClausula($filtro);
            $consultaFatura = $f->buscarProdutosFaturados($parametroFaturaId);
            if (is_array($consultaFatura) && count($consultaFatura) > 0) {
                $valoresConsolidados["fatura"] = $consultaFatura;
                $produtoFatura = new Application_Model_ProdutoFatura();
                $faturaid = $consultaFatura['fatura']['fatura_id'];
                //$produtoFatura->obterProdutosAgrupadosComQuantidade($faturaId);
                $ModeloImpostoFatura = new Application_Model_FaturaImposto();
                $filtrar = "fatura_imposto.fatura_imposto_status = 0";
                $filtrar .= " AND fatura_imposto.fk_fatura_id = {$parametroFaturaId}";
                $consultar = $ModeloImpostoFatura->buscaCompletaUsandoClausula($filtrar);
                $consultaFatura['impostos'] = (is_array($consultar) && count($consultar) > 0) ? $consultar : array();
            }

            $u = new Application_Model_Unidade();
            $unidade = $u->obterEnderecoUnidade($consultaFatura['contrato']['UnidadeContrato']);
        } catch (Exception $e) {
            throw $e;
        }
        $this->view->unidade = $unidade;
        $this->view->atributos = $consultaFatura;
        $this->_desabilitarCarregamentoDoTemplate();
        $this->renderScript($visualizacao);
    }

    public function imprimirGuiaAtendimentoAction() {
        $visualizacao = '/documento-operacional/guia-atendimento/pdf.phtml';
        $id = (int) $this->_getParam('agenda_id', 0);
        $this->_desabilitarCarregamentoDoTemplate();
        $rst = [];
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $a = new Application_Model_Agenda();
            $rst = $a->buscaGuiaAtendimento($id);

            if (!$rst or ( $rst && is_array($rst) && count($rst) == 0)) {
                throw new Exception('Não foi localizado registro no banco de fila para este agendamento!');
            }
            $rstCmd = NULL;
        } catch (Exception $exc) {
            throw $exc;
        }
        if (empty($rstCmd)) {
            try {
                $adapter = Zend_Db_Table::getDefaultAdapter();

                $sql = "SELECT DISTINCT *
                        FROM
                        (
                        SELECT pro.produto_nome FROM agenda a
                        JOIN pcmso p ON p.fk_contrato_id = a.fk_contrato_id AND p.fk_empresa_id = a.fk_empresa_id AND p.pcmso_status = 0
                        JOIN item_pcmso i ON i.fk_pcmso_id = p.pcmso_id AND i.item_pcmso_status = 0
                        JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id AND pp.ppra_item_status = 0
                        JOIN item_pcmso_produto ipp ON ipp.fk_item_pcmso_id = i.item_pcmso_id AND ipp.fk_tipoexame_id = a.fk_tipoexame_id
                        JOIN produto pro ON pro.produto_id = ipp.fk_produto_id AND pro.produto_status = 0
                        JOIN alocacao alc ON alc.alocacao_id = a.fk_alocacao_id
                        AND alc.fk_cargo_id = i.fk_cargo_id
                        AND alc.fk_setor_id = i.fk_setor_id
                        AND alc.fk_funcao_id = i.fk_funcao_id
                        AND alc.fk_ghe_id = i.fk_ghe_id
                        WHERE a.agenda_status = 0 AND a.agenda_id = ?

                        UNION ALL

                        SELECT p.produto_nome FROM produto_agenda pa
                        JOIN produto p ON p.produto_id = pa.fk_produto_id
                        WHERE pa.fk_agenda_id = ? AND pa.produto_agenda_status = 0) AS x

                        ORDER BY x.produto_nome ASC";
                $prepare = $adapter->prepare($sql);
                $prepare->execute(array($id, $id));
                $exames = $prepare->fetchALL();
                $rstCmd = $exames;
            } catch (Exception $e) {
                throw new Exception('Ocorreu erro ao buscar exames do funcionario!');
            }

        }

        $this->view->agenda = $rst;
        $this->view->procedimentos = $rstCmd;
        $this->renderScript($visualizacao);
    }

    public function imprimirGuiaExamesAction() {
        /*
         * Foi necessário atualizar a guia para um template e uma regra nova,
         * para não perder a versão anterior
         */
        $this->_log->logDetalhe = 'Resultado relatorio de guia de exames - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-guia-de-exames';

        //echo $unidade_id = isset($_SESSION['usuario']['unidadeativa']['unidade_id']) ? (int) $_SESSION['usuario']['unidadeativa']['unidade_id'] : 0;
        $visualizacao = '/documento-operacional/guia-exames/formulario.phtml';
        $colecaoEmpresasFornecedoras = array();
        // Se for method igual a POST é gerado o PDF
        if ($this->getRequest()->isPost()) {
            $dadosForm = $this->getRequest()->getPost();
            $visualizacao = '/documento-operacional/guia-exames/imprimir-versao2.phtml';
            $atributos = array();
            $this->view->atributos = $atributos;
            $this->processarEmissaoGuiaExame($dadosForm);
            $this->_helper->layout->disableLayout();
        } else {
            try {
                $p = new Application_Model_Produto();
                $produtosExame = $p->obterExames();

                $ModeloEmpresa = new Application_Model_Empresa();
                $unidade = self::$_idUnidadeAtivaDoUsuarioLogado;
                $condicao = " empresa.fk_unidade_id = {$unidade} AND tabela.tabela_status = 0";
                $resultado = $ModeloEmpresa->obterEmpresasFornecedorasComClausula($condicao);
                //var_dump($resultado);
                if (is_array($resultado)) {
                    $colecao = array();
                    foreach ($resultado as $item) {
                        if (!in_array($item['empresa_id'], $colecao)) {
                            $colecaoEmpresasFornecedoras[] = $item;
                            $colecao[] = $item['empresa_id'];
                        }
                    }
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            $this->view->produtos = $produtosExame;
        }
        $this->view->colecaoEmpresasFornecedoras = $colecaoEmpresasFornecedoras;
        $this->renderScript($visualizacao);
    }

    public function processarEmissaoGuiaExame($dadosForm) {
        $dadosForm = $this->getRequest()->getPost();
        $parametroIdsProdutos = $this->getParam('produto_id', array());
        $stringIdsProdutos = implode(',', $parametroIdsProdutos);
        $parametroDataInicio = Util::dataBD($this->getParam('data_inicio'));
        $parametroDataFim = Util::dataBD($this->getParam('data_fim'));
        $parametroColecaoFornecedorId = (isset($dadosForm['empresa_id']) && (int) $dadosForm['empresa_id'] > 0 ) ? array($dadosForm['empresa_id']) : array();
        $resultadoAgenda = array();
        $unidade_id = isset($_SESSION['usuario']['unidadeativa']['unidade_id']) ? (int) $_SESSION['usuario']['unidadeativa']['unidade_id'] : 0;
        try {
            $agenda = new Application_Model_Agenda();
            $filtro = " agenda.agenda_data_exame BETWEEN '{$parametroDataInicio}' AND '{$parametroDataFim}' ";
            $filtro .= " AND agenda.agenda_status = 0";
            $filtro .= " AND empresa.fk_unidade_id = {$unidade_id}";
            $resultadoAgenda = $agenda->recuperarAgendaComOsExamesProgramadosPelosIdsExames($stringIdsProdutos, $filtro, 'agenda.agenda_id', '0,99999999999', $parametroColecaoFornecedorId);
        } catch (Exception $e) {
            echo "<pre>{$e->getMessage()}</pre>";
            exit(0);
        }
        $this->view->agendas = $resultadoAgenda;
        $this->view->parametros = $dadosForm;
    }

    public function modeloRelatorioAction() {
        $visualizacao = '/documento-operacional/modelo-relatorio/formulario.phtml';
        // Se for method igual a POST é gerado o PDF
        if ($this->getRequest()->isPost()) {
            $dadosForm = $this->getRequest()->getPost();
            $visualizacao = '/documento-operacional/modelo-relatorio/imprimir.phtml';
            $this->processarRelatorioEmpresa($dadosForm);
            $this->_helper->layout->disableLayout();
        } else {
            try {
                $empresa = new Application_Model_Empresa();
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        $this->renderScript($visualizacao);
    }

    public function relatorioEmpresaAction() {
        $visualizacao = '/documento-operacional/relatorio-empresa/formulario.phtml';

        # EMPRESA
        $empresa = new Application_Model_Empresa();
        $empresas = $empresa->listarEmpresas();
        $this->view->empresas = $empresas;

        # UNIDADE
        $unidade = new Application_Model_Unidade();
        $unidades = $unidade->obterTodos();
        $this->view->unidades = $unidades;

        $this->_log->logDetalhe = 'Formulario relatorio de relatorio empresas - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-de-empresas';

        # POST
        if ($this->getRequest()->isPost()):
            $this->_log->logDetalhe = 'Resultado relatorio de empresas - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-de-empresas';
            # ERROS
            $erros = array();

            # PARAMETROS
            $emp = (int) $this->_getParam('empresa');
            $uni = implode(",", $this->_getParam('unidades', array()));
            $tip = $this->_getParam('tipos', array('c', 'f'));
            $mod = (int) $this->_getParam('modo');

            if ($emp == 0):

                if (is_null($tip)):
                    $erros[] = "Selecione ao menos um tipo de empresa.";
                endif;

                if (empty($uni)):
                    $erros[] = "Selecione ao menos uma unidade.";
                endif;

            endif;

            if (empty($erros)):
                # FILTRO
                $filtro = $emp == 0 ? "" : " AND empresa.empresa_id = $emp";
                $filtro = $filtro . ($uni == "" ? "" : " AND unidade.unidade_id IN ($uni)");
                if (count($tip) == 1):
                    if ($tip[0] == "f"):
                        $filtro = $filtro . "AND EXISTS (SELECT * FROM empresa e JOIN tabela ON tabela.fk_empresa_id = e.empresa_id WHERE e.empresa_status = 0 AND e.empresa_id = empresa.empresa_id GROUP BY e.empresa_id)";
                    else:
                        $filtro = $filtro . " AND empresa.empresa_tipo = 'CLIENTE' AND NOT EXISTS (SELECT * FROM empresa e JOIN tabela ON tabela.fk_empresa_id = e.empresa_id WHERE e.empresa_status = 0 AND e.empresa_id = empresa.empresa_id GROUP BY e.empresa_id)";
                    endif;
                endif;

                $filtros = array();
                if ($emp > 0):
                    $tmp = $empresa->obter($emp);
                    $filtros['EMPRESA'] = $tmp['empresa_razao'];
                else:
                    $filtros['EMPRESA'] = "TODAS";
                endif;

                $filtros['UNIDADES'] = "";
                foreach (explode(",", $uni) as $u):
                    $tmp = $unidade->obter($u);
                    $filtros['UNIDADES'] = $filtros['UNIDADES'] . ($filtros['UNIDADES'] == "" ? "{$tmp['unidade_sigla']}" : ", {$tmp['unidade_sigla']}");
                endforeach;

                $filtros['TIPOS'] = "";
                foreach ($tip as $tipo):
                    $t = $tipo == "c" ? "CLIENTE" : "FORNECEDOR";
                    $filtros['TIPOS'] = $filtros['TIPOS'] . ($filtros['TIPOS'] == "" ? "$t" : ", $t");
                endforeach;

                $this->view->filtro = $filtros;

                # CONSULTA
                $relatorio = new Application_Model_Relatorio();
                $this->view->resultado = $relatorio->empresas($filtro);

                # GERAR VISUALIZACAO
                $visualizacao = '/documento-operacional/relatorio-empresa/documento.phtml';
            else:
                $this->view->erros = '<div class="alert alert-danger">' . implode('<br/>', $erros) . '</div>';
            endif;

            if ($mod == 1):
                $this->_helper->layout->disableLayout();
            endif;
            $this->view->modo = $mod;
        endif;

        # RENDERIZAR
        $this->renderScript($visualizacao);
    }

    public function processarRelatorioEmpresa($dadosForm) {
        $dadosForm = $this->getRequest()->getPost();
        try {
            $empresa = new Application_Model_Empresa();
        } catch (Exception $e) {
            echo "<pre>{$e->getMessage()}</pre>";
            exit(0);
        }
        $this->view->parametros = $dadosForm;
    }

    public function imprimirSolicitacaoLaudoAction() {
        $this->_log->logDetalhe = 'Resultado relatorio de solicitacao de laudo - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-solicitacao-laudo';
        $parametroAgendaId = (int) $this->_getParam('agendaid', 0);
        $atributos = array(
            'agenda' => array(),
            'encaminhamento' => array(),
        );
        if ($parametroAgendaId > 0) {
            try {
                $agenda = new Application_Model_Agenda();
                $clausulaComando = "agenda_id = {$parametroAgendaId}";
                $resultadoAgenda = $agenda->buscaCompletaUsandoClausula($clausulaComando);
                $atributos["agenda"] = $resultadoAgenda;

                // Recupera a ficha médica pelo id da agenda
                $fichaMedica = new Application_Model_FichaMedica();
                $clausulaComando = "fichamedica.fk_agenda_id = '$parametroAgendaId' AND fichamedica_status = 0";
                $resultadoFichaMedica = $fichaMedica->buscaCompletaUsandoClausula($clausulaComando, "fichamedica_id DESC", "0,1");
                $idFichaMedica = (count($resultadoFichaMedica) > 0) ? $resultadoFichaMedica[0]['fichamedica_id'] : 0;

                // Resgata Laudos atribuidos para o paciênte
                $encaminhamento = new Application_Model_EncaminhamentoEspecialidade();
                $clausulaComando = "encaminhamento_especialidade.fk_fichamedica_id = {$idFichaMedica}";
                $resultadoEncaminhamento = $encaminhamento->buscaCompletaUsandoClausula($clausulaComando);
                $atributos['encaminhamento'] = $resultadoEncaminhamento;
            } catch (Exception $e) {
                echo "<pre>", $e->getMessage(), "</pre>";
                exit(0);
            }
        }
        $this->view->atributos = $atributos;
        $this->renderScript('/documento-operacional/laudo/imprimir-solicitacao-laudo.phtml');
        $this->_helper->layout->disableLayout();
    }

    public function imprimirAltaRacAction() {

        $this->_log->logDetalhe = 'Resultado relatorio de Alta de Rac - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-alta-de-rac';

        $parametroAgendaId = $this->_getParam("agendaid", 0);
        $atributos = array(
            'agenda' => array(),
            'atividadesCriticas' => array(),
            'atividadesCriticasLiberadas' => array(),
            'fichaMedica' => array(),
            'medico' => array(),
            'especialista' => array(),
            'alocacao' => array(),
            'funcionario' => array()
        );

        try {
            $adapter = Zend_Db_Table::getDefaultAdapter();

            $sql = "SELECT *
                FROM fichamedica
                JOIN agenda ON agenda.agenda_id = fichamedica.fk_agenda_id
                JOIN tipoexame ON tipoexame.tipoexame_id = agenda.fk_tipoexame_id
                JOIN empresa ON empresa.empresa_id = agenda.fk_empresa_id
                JOIN pessoa ON pessoa.pessoa_id = agenda.fk_pessoa_id
                JOIN funcionario ON funcionario.fk_pessoa_id = pessoa.pessoa_id AND funcionario.fk_empresa_id = empresa.empresa_id
                JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
                JOIN setor ON setor.setor_id = alocacao.fk_setor_id
                JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
                JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
                WHERE fichamedica.fichamedica_status = 0 AND agenda.agenda_id = ?";
            $prepare = $adapter->prepare($sql);
            $prepare->execute(array($parametroAgendaId));
            $atributos = $prepare->fetch();



            // Resgata informações das atividades críticas
            $atividadesCriticas = new Application_Model_AtividadeCritica();
            $resultadoAtividadeCritica = $atividadesCriticas->fetchAll("atividadecritica_status = 0")->toArray();
            $atributos["atividadesCriticas"] = $resultadoAtividadeCritica;

            // Resgata dados da  agenda
            $agenda = new Application_Model_Agenda();
            $filtro = "agenda.agenda_id = {$parametroAgendaId}";
            $resultadoAgenda = $agenda->buscaCompletaUsandoClausula($filtro);
            $atributos["agenda"] = $resultadoAgenda;
            $pessoaEspecialistaId = (isset($resultadoAgenda[0]['fk_pessoa_especialidade_id'])) ? $resultadoAgenda[0]['fk_pessoa_especialidade_id'] : 0;
            $alocacaoId = (isset($resultadoAgenda[0]['fk_alocacao_id'])) ? $resultadoAgenda[0]['fk_alocacao_id'] : 0;

            // Resgata os dados da ficha médica
            $fichaMedica = new Application_Model_FichaMedica();
            $filtro = "fichamedica.fichamedica_status = 0 AND fichamedica.fk_agenda_id = '{$parametroAgendaId}' ";
            $resultadoFichaMedica = $fichaMedica->buscaCompletaUsandoClausula($filtro, "fichamedica.fichamedica_id DESC");
            $fichaMedicaId = (is_array($resultadoFichaMedica) && count($resultadoFichaMedica) > 0) ? (int) $resultadoFichaMedica[0]['fichamedica_id'] : 0;
            $atributos["fichaMedica"] = $resultadoFichaMedica;

            // Resgata as atividades críticas que o funcionario está apto para exercer.
            $atividadeCriticaLiberada = new Application_Model_LiberacaoAtividadeCritica();
            $filtro = "liberacaoatividadecritica.fk_fichamedica_id = {$fichaMedicaId} AND  liberacaoatividadecritica.liberacaoatividadecritica_status = 0";
            $resultadoAtividadeCriticaLiberada = $atividadeCriticaLiberada->buscarCompletoUsandoClausula($filtro);
            $atributos['atividadesCriticasLiberadas'] = $resultadoAtividadeCriticaLiberada;

            // Resgata dados do médico
            $especialista = new Application_Model_PessoaEspecialidade();
            $resultadoPessoaEspecialista = $especialista->buscaCompletaUsandoClausula("pessoa_especialidade_id = '{$pessoaEspecialistaId}'");
            $atributos['especialista'] = $resultadoPessoaEspecialista;

            // Resgata dados da Alocação
            $alocacao = new Application_Model_Alocacao();
            $atributos["alocacao"] = $alocacao->buscaCompletaUsandoClausula("alocacao_id = $alocacaoId");
            $funcionarioId = isset($atributos["alocacao"][0]['fk_funcionario_id']) ? $atributos["alocacao"][0]['fk_funcionario_id'] : 0;

            // Resgata dados do funcionário
            $funcionario = new Application_Model_Funcionario();
            $resultadoFuncionario = $funcionario->obter($funcionarioId);
            $atributos["funcionario"] = $resultadoFuncionario;


            $pa = new Application_Model_ProdutoAgenda();
            $filtro = "agenda.agenda_id = {$parametroAgendaId} AND produto_agenda_status = 0";
            $itensRetornados = $pa->buscaCompletaUsandoClausula($filtro, "produto.produto_nome ASC");
            $atributos['exames'] = $itensRetornados;
        } catch (Exception $ex) {
            echo "<pre>Erro ao executar comando no banco de dados: " . $ex->getMessage(), "</p>";
        }

        $this->view->atributos = $atributos;
        $this->_helper->viewRenderer->setNoRender();
        $this->renderScript('/documento-operacional/rac/imprimir-alta-rac.phtml');
        $this->_helper->layout->disableLayout();
    }

    public function emitirRelatorioPendenciaCliAction() {
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao('Relatório', 'Operacional', 'Pendentes no Clínico');
        $colecaoResultadoRelatorio = array();
        $this->_helper->viewRenderer->setNoRender();
        $this->_log->logDetalhe = 'Renderização do formulário para relatório de pendência no clínico';
        $form = array(
            'ColecaoEmpresa' => array()
        );
        try {
            $ModeloEmpresa = new Application_Model_Empresa();
            $Resultado = $ModeloEmpresa->fetchAll(array('empresa_status = ?' => 0, 'fk_unidade_id = ?' => self::$_idUnidadeAtivaDoUsuarioLogado, 'EXISTS(SELECT * FROM contratante c WHERE c.fk_empresa_id = empresa.empresa_id )'), 'empresa_fantasia ASC');
            if ($Resultado->count() > 0) {
                $form['colecaoEmpresa'] = $Resultado->toArray();
            }
        } catch (Exception $exc) {
            throw $exc;
        }
        $paramEmpresaId = $paramContratoId = 0;
        $modoRenderizacao = (int) $this->_getParam('paramRenderizar', 0);
        if ($modoRenderizacao > 0) {
            $this->_log->logDetalhe = 'Renderização do resultado para relatório de pendência no clínico';
            $paramDataInicio = Util::dataBD($this->_getParam('paramDataInicio'));
            $paramDataTermino = Util::dataBD($this->_getParam('paramDataTermino'));
            $paramEmpresaId = (int) $this->_getParam('paramEmpresaId', 0);
            $paramContratoId = (int) $this->_getParam('paramContratoId', 0);
            $colecaoEmpresas = $colecaoContratos = array();
            if ($paramEmpresaId > 0)
                $colecaoEmpresas[] = $paramEmpresaId;

            if ($paramContratoId > 0)
                $colecaoContratos[] = $paramContratoId;

            try {
                $ModeloFichaMedica = new Application_Model_FichaMedica();
                $colecaoResultado = array();
                $x = $ModeloFichaMedica->obterColecaoAtendimentoComPendencia($paramDataInicio, $paramDataTermino, $colecaoEmpresas, $colecaoContratos, array(self::$_unidadeIdEmContexto));
                $colecaoResultadoRelatorio = (count($x) > 0) ? $x : array();
            } catch (Exception $exc) {
                throw $exc;
            }
        }
        $this->view->colecaoResultadoRelatorio = $colecaoResultadoRelatorio;

        $script = '/documento-operacional/relatorio-pendencia-clinico/form.phtml';
        if (count($colecaoResultadoRelatorio) > 0) {
            $form = array();
            $form['paramDataInicio'] = $this->_getParam('paramDataInicio');
            $form['paramDataTermino'] = $this->_getParam('paramDataTermino');
            $form['paramEmpresaRazao'] = 'Todas';
            $form['paramContratoNumero'] = 'Todos';
            if ($paramEmpresaId > 0 or $paramContratoId > 0) {
                try {
                    if ($paramEmpresaId > 0) {
                        $ModeloEmpresa = new Application_Model_Empresa();
                        $ResultadoComando = $ModeloEmpresa->fetchRow(array('empresa_id = ?' => $paramEmpresaId));
                        if ($ResultadoComando) {
                            $r = $ResultadoComando->toArray();
                            $form['paramEmpresaRazao'] = $r['empresa_razao'];
                        }
                    }

                    if ($paramContratoId > 0) {
                        $ModeloContrato = new Application_Model_Contrato();
                        $ResultadoComando = $ModeloContrato->fetchRow(array('contrato_id = ?' => $paramContratoId));
                        if ($ResultadoComando) {
                            $r = $ResultadoComando->toArray();
                            $form['paramContratoNumero'] = $r['contrato_numero'];
                        }
                    }
                } catch (Exception $exc) {
                    throw $exc;
                }
            }
            $script = '/documento-operacional/relatorio-pendencia-clinico/pdf.phtml';
            $this->_helper->layout->disableLayout();
        }
        $this->view->form = $form;
        $this->renderScript($script);
    }

    public function gerarOsAction() {
        $script = '/documento-operacional/os/2pnt0/pdf.phtml';
        $this->_log->logDetalhe = 'Gerado PDF de Proposta / OS';
        $paramOsId = (int) $this->_getParam('osid', 0);
        $paramOsTipo = strtolower(trim($this->_getParam('ostipo')));
        $this->view->mostrarValores = ($paramOsTipo == 'e') ? true : false;
        $clcTipo = array('e', 'i');
        $resultado = array(
            'EmpresaContratante' => null,
            'Contrato' => null,
            'OrdemServico' => null,
            'ColecaoProdutoContratado' => array(),
            'ColecaoDescricaoProduto' => array(),
            'UnidadeComercial' => null
        );
        try {
            if ($paramOsId <= 0 or in_array($paramOsTipo, $clcTipo) == false) {
                throw new Exception('Os argumentos OS_ID e OS_TIPO não são válidos ou não foram informados.');
            }
            // Resgata OS
            $ModeloOs = new Application_Model_Os();
            $RstOs = $ModeloOs->fetchRow(array('os_id = ?' => $paramOsId));
            if (!$RstOs)
                throw new Exception('Proposta/Ordem de Serviço não localizada!');
            $resultado['OrdemServico'] = $RstOs->toArray();

            // Resgata o Contrato
            $ModeloContrato = new Application_Model_Contrato();
            $resultado['Contrato'] = $ModeloContrato->fetchRow(array('contrato_id = ?' => $RstOs->fk_contrato_id));

            // Resgata a empresa
            $ModeloEmpresa = new Application_Model_Empresa();
            $RstEmp = $ModeloEmpresa->obterEmpresaPrincipalDoContrato($RstOs->fk_contrato_id);
            if (!$RstEmp)
                throw new Exception('Não foi possível localizar a empresa vinculada a proposta!');
            $resultado['EmpresaContratante'] = $RstEmp;

            $clcCategoriaId = array();
            $clcOsId = array($paramOsId);

            // Resgatando os produtos contratados agrupando os produtos por categoria.
            $RstClcProdutosCategorizados = array();
            $rstClcCategoria = Application_Model_CategoriaProduto::obterColecaoCategoriaDosProdutoContratados(array(), array(), array(), array($paramOsId));
            foreach ($rstClcCategoria as $item) {
                $RstClcProdutosCategorizados[] = Application_Model_ProdutoContratado::obterColecaoProdutoContratado(array(), $clcOsId, array((int) $item['categoriadoproduto_id']));
            }
            $resultado['ColecaoProdutoContratado'] = (count($RstClcProdutosCategorizados) > 0) ? $RstClcProdutosCategorizados : array();
            //$tem_que_trabalhar_na_view_agora = '';
            $clcCategoriaId = array();
            $RstClcDescProd = Application_Model_ProdutoContratado::obterColecaoDescricaoProdutosContidosProposta(array('produto_nome'), $clcOsId, $clcCategoriaId);
            $resultado['ColecaoDescricaoProduto'] = (count($RstClcDescProd) > 0) ? $RstClcDescProd : array();

            // Resgata informações em qual unidade e endereco foi feito o contrato
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Comando = $Cnx->select();
            $Comando->from(array('c' => 'contrato'), array())
                    ->join(array('u' => 'unidade'), 'u.unidade_id = c.fk_unidade_id', '*')
                    ->join(array('e' => 'endereco'), 'e.endereco_id = u.fk_endereco_id', '*')
                    ->where('c.contrato_status = ?', 0)
                    ->where('c.contrato_id = ?', $RstOs->fk_contrato_id);
            $clcResultado = $Comando->query()->fetch();
            $resultado['UnidadeComercial'] = (count($clcResultado) > 0) ? $clcResultado : null;
        } catch (Exception $exc) {
            throw $exc;
        }
        $this->view->colecaoItemResultado = $resultado;
        $this->_desabilitarTodoCarregamentoDeVisualizacao();
        $this->renderScript($script);
    }

    public function imprimirFichaMedicaAction() {
        $this->_log->logDetalhe = 'Resultado relatorio de ficha medica - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-ficha-medica';
        $parametroAgendaId = (int) $this->_getParam('agendaid', 0);
        $atributos = array();
        $renderizar = false;
        /* @var $parametroAgendaId int */
        if ($parametroAgendaId > 0) {
            $filtrar = "fichamedica.fichamedica_status = 0 ";
            $filtrar .= " AND fichamedica.fk_agenda_id = '{$parametroAgendaId}'";
            try {
                $ficha = new Application_Model_FichaMedica();
                $resultadoConsulta = $ficha->buscaCompletaUsandoClausula($filtrar);
                if (is_array($resultadoConsulta) && count($resultadoConsulta) > 0) {
                    $atributos = $resultadoConsulta[0];

                    $pessoaId = (int) $atributos['fk_pessoa_id'];
                    $empresaId = (int) $atributos['fk_empresa_id'];
                    $contratoId = (int) $atributos['fk_contrato_id'];
                    $especialistaId = (int) $atributos['fk_pessoa_especialidade_id'];
                    $tipoExameId = (int) $atributos['fk_tipoexame_id'];
                    $agendaId = (int) $atributos['agenda_id'];

                    // Recupera dados da empresa
                    $emp = new Application_Model_Empresa();
                    $resultadoConsulta = $emp->obter($empresaId);
                    $atributos["empresa"] = $resultadoConsulta;

                    // Recupera o tipo de exame
                    $tipoExame = new Application_Model_TipoExame();
                    $atributos["tipoexame"] = $tipoExame->obter($tipoExameId);

                    // Recupera os dados do médico
                    $atributos["medico"] = array();
                    $pessoaEspecialidade = new Application_Model_PessoaEspecialidade();
                    $filtrar = "pessoa_especialidade.pessoa_especialidade_id = {$especialistaId}";
                    $resultadoPessoaEspecialidade = $pessoaEspecialidade->buscaCompletaUsandoClausula($filtrar);
                    $atributos["medico"] = isset($resultadoPessoaEspecialidade[0]) ? $resultadoPessoaEspecialidade[0] : array();

                    // Recupera dados do funcionario
                    $funcionario = new Application_Model_Funcionario();
                    $filtrar = "funcionario.fk_contrato_id = {$contratoId}";
                    $filtrar .= " AND funcionario.fk_empresa_id = {$empresaId}";
                    $filtrar .= " AND funcionario.fk_pessoa_id = {$pessoaId}";
                    $resultadoFuncionario = $funcionario->obterPeloFiltro($filtrar);
                    $atributos['funcionario'] = $resultadoFuncionario[0];

                    // Recuperando os exames alterados da agenda
                    $produtoExameFichaMedicaAlterado = new Application_Model_ProdutoAlteradoFichaMedica();
                    $filtro = "fichamedica.fk_agenda_id = {$parametroAgendaId} AND fichamedica.fichamedica_status = 0";
                    $resultadoItensAlterados = $produtoExameFichaMedicaAlterado->buscaCompletaUsandoClausula($filtro);
                    $atributos['produto_alterado_fichamedica'] = $resultadoItensAlterados;

                    // Recupera riscos do PCMSO
                    /*
                      $model = new Application_Model_Pcmso();
                      $atributos['pcmso_risco'] = $model->obterRiscosPelaAgenda($agendaId);
                     */

                    $atributos['riscos'] = array();
                    $comando = "SELECT *
                    FROM ficha_medica_risco fmr, classerisco cr
                    WHERE fmr.ficha_medica_risco_status = 0
                          AND fmr.fk_fichamedica_id = ?
                          AND cr.classerisco_id = fmr.fk_classerisco_id
                    ORDER BY classerisco_nome ASC ";
                    $Cnx = Zend_Db_Table::getDefaultAdapter();
                    $clcRst = $Cnx->fetchAll($comando, array($atributos['fichamedica_id']));
                    if (count($clcRst) > 0) {
                        $atributos['riscos'] = $clcRst;
                    }
                    $renderizar = (count($resultadoFuncionario) > 0) ? true : false;
                }
            } catch (Exception $e) {
                echo "<pre>{$e->getMessage()}</pre>";
                exit(0);
            }
        }
        $this->view->atributos = $atributos;
        if ($renderizar) {
            $this->renderScript('documento-operacional/ficha-medica/imprimirFicha.phtml');
        } else {
            $this->_helper->viewRenderer->setNoRender();
        }
        $this->_helper->layout->disableLayout();
    }

    public function imprimirTabelaAction() {
        $this->_log->logDetalhe = 'Formulario relatorio tabela de produto - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-tabela-produto';



        //Se for method igual a POST é gerado o PDF
        if ($this->getRequest()->isPost()) {
            $colecaoInformacoesUnidade = array();
            $jsonColecaoInformacoesUnidade = (isset($_SESSION['usuario']['unidadeativa']['unidade_descricao']) && strlen($_SESSION['usuario']['unidadeativa']['unidade_descricao']) > 0) ? $_SESSION['usuario']['unidadeativa']['unidade_descricao'] : null;
            if ($jsonColecaoInformacoesUnidade != null) {
                $colecaoInformacoesUnidade = json_decode($jsonColecaoInformacoesUnidade, true);
            }

            $dadosForm = $this->getRequest()->getPost();
            $this->_log->logDetalhe = 'Resultado relatorio tabela de produto - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-tabela-produto';
            $visualizacao = '/documento-operacional/tabela-produto/imprimirTabela.phtml';
            $atributos = array();
            $Empresa = new Application_Model_Empresa();
            $empresa = $Empresa->obter((int) $dadosForm['empresa_id']);
            $atributos['empresa'] = $empresa;
            $this->view->atributos = $atributos;
            $this->view->colecaoInformacoesUnidade = $colecaoInformacoesUnidade;
            $this->processarEmissaoTabelaProduto($dadosForm);
            $this->_helper->layout->disableLayout();
            $this->renderScript($visualizacao);
        }

        $visualizacao = '/documento-operacional/tabela-produto/formulario.phtml';
        $categorias = array();

        try {
            $c = new Application_Model_CategoriaProduto();
            $categorias = $c->obterTodos();
            // $categorias = $c->fetchAll(null, 'categoriadoproduto_nome ASC')->toArray();
            // var_dump($categorias);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $this->view->categorias = $categorias;
        $this->renderScript($visualizacao);
    }

    #AQUI -------------------------------------------------------------------------

    public function processarEmissaoTabelaProduto($dadosForm) {

        if ($this->getRequest()->isPost()) {
            $dadosForm = $this->getRequest()->getPost();
        }

        $produtosPorCategoria = array();

        try {
            $categ = new Application_Model_CategoriaProduto();
            foreach ($dadosForm['listaCategoria'] as $i => $value) {
                $cat[$i] = $categ->obtercateg($value);
                $cat[$i]["produtos"] = $categ->obterMaiorValorProdutoCategoria($value, $dadosForm['empresa_id']);
            }
        } catch (Exception $e) {
            echo "<pre>{$e->getMessage()}</pre>";
            exit(0);
        }
        $this->view->form = $dadosForm;
        $this->view->cat = $cat;
    }

    public function imprimirDocumentoContratoAction() {
        $id = (int) $this->_getParam('id', 0);
        $usuario_id = ($_SESSION['usuario']['usuario_id']);
        $proposta = new Application_Model_Proposta();
        $nomeUsuario = $proposta->obterNomeUsuario($usuario_id);

        $this->_log->logDetalhe = 'Resultado relatorio documento de contrato - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-documento-de-contrato';
        $this->_log->logTabelaNome = 'contrato';
        $this->_log->logTabelaColunaNome = 'contrato_id';
        $this->_log->logTabelaColunaValor = $id;


        try {
            $adapter = Zend_Db_Table::getDefaultAdapter();
            $produtoscategorizados = array();

            $sql_contrato = "SELECT *
                                FROM contrato c
                                INNER JOIN contratante con
                                    ON (con.`fk_contrato_id` = c.`contrato_id`)
                                INNER JOIN empresa e
                                    ON (e.`empresa_id` = con.`fk_empresa_id`)
                                INNER JOIN endereco en
                                    ON (en.`endereco_id` = e.`fk_endereco_id`)
                            WHERE c.`contrato_id`= {$id}";

            $prepare = $adapter->prepare($sql_contrato);
            $prepare->execute();
            $listaContrato = $prepare->fetch();
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
        $this->view->user = $nomeUsuario;
        $this->view->contrato = $listaContrato;
        $this->renderScript('documento-operacional/contrato/documento-contrato.phtml');
        $this->_helper->layout->disableLayout();
    }

    public function imprimirAsoAction() {
        $agendaid = $this->_getParam('agendaid', 0);
        $this->view->modo = $this->_getParam('modo');
        $atributos = array();
        $this->_log->logDetalhe = 'Resultado relatorio documento ASO - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-documento-aso';
        $this->_log->logTabelaNome = 'fichamedica';
        $this->_log->logTabelaColunaNome = 'fk_agenda_id';
        $this->_log->logTabelaColunaValor = $agendaid;
        #consulta
        $adapter = Zend_Db_Table::getDefaultAdapter();

        $sql = "SELECT *
                FROM fichamedica
                JOIN agenda ON agenda.agenda_id = fichamedica.fk_agenda_id
                JOIN tipoexame ON tipoexame.tipoexame_id = agenda.fk_tipoexame_id
                JOIN empresa ON empresa.empresa_id = agenda.fk_empresa_id
                JOIN pessoa ON pessoa.pessoa_id = agenda.fk_pessoa_id
                JOIN funcionario ON (funcionario.fk_pessoa_id = pessoa.pessoa_id AND funcionario.fk_empresa_id = empresa.empresa_id)
                JOIN alocacao ON(alocacao.fk_funcionario_id = funcionario.funcionario_id AND agenda.fk_alocacao_id = alocacao.alocacao_id)
                JOIN setor ON setor.setor_id = alocacao.fk_setor_id
                JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
                JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
                WHERE fichamedica.fichamedica_status = 0 AND agenda.agenda_id = ?";
        $prepare = $adapter->prepare($sql);
        $prepare->execute(array($agendaid));
        $atributos = $prepare->fetch();

        #medico
        $sql = "SELECT coordenacao.*
        FROM agenda
        JOIN pcmso ON pcmso.fk_empresa_id = agenda.fk_empresa_id AND pcmso.fk_contrato_id = agenda.fk_contrato_id
        JOIN coordenacao ON coordenacao.coordenacao_id = pcmso.fk_coordenacao_id
        WHERE agenda.agenda_id = '$agendaid'
        AND agenda.agenda_status = 0
        AND pcmso.pcmso_status = 0
        order by pcmso.pcmso_data_validade desc";
        $prepare = $adapter->prepare($sql);
        $prepare->execute();
        $atributos['medico'] = $prepare->fetch();

        #exames
        $pa = new Application_Model_ProdutoAgenda();
        $filtro = "agenda.agenda_id = {$agendaid} AND produto_agenda_status = 0";
        $itensRetornados = $pa->buscaCompletaUsandoClausula($filtro, "produto.produto_nome ASC");
        /* $sql = "SELECT * FROM produto_agenda
          JOIN produto ON produto.produto_id = produto_agenda.fk_produto_id
          WHERE produto_agenda.fk_agenda_id = ?";
          $prepare = $adapter->prepare($sql);
          $prepare->execute(array($agendaid));
          $atributos['exames'] = $prepare->fetchAll(); */
        $atributos['exames'] = $itensRetornados;

        /*
          Modo Antigo
          #riscos
          $pcmso = new Application_Model_Pcmso();
          $atributos['riscos'] = $pcmso->obterRiscosPelaAgenda($agendaid);
         */
        $atributos['riscos'] = array();
        $comando = "SELECT *
                    FROM ficha_medica_risco fmr, classerisco cr
                    WHERE fmr.ficha_medica_risco_status = 0
                          AND fmr.fk_fichamedica_id = ?
                          AND cr.classerisco_id = fmr.fk_classerisco_id
                    ORDER BY classerisco_nome ASC ";
        $Cnx = Zend_Db_Table::getDefaultAdapter();
        $fm = isset($atributos['fichamedica_id']) ? $atributos['fichamedica_id'] : 0;
        $clcRst = $Cnx->fetchAll($comando, array($fm));
        if (count($clcRst) > 0) {
            $atributos['riscos'] = $clcRst;
        }
        $atributos['ModeloEnderecoUnidade'] = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('endereco');
        if (isset($atributos['empresa_id']) && (int) $atributos['empresa_id'] > 0) {
            try {
                $Comando = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
                $Comando->from(array('emp' => 'empresa'), array())
                        ->join(array('uni' => 'unidade'), 'uni.unidade_id = emp.fk_unidade_id', array())
                        ->join(array('e' => 'endereco'), 'e.endereco_id = uni.fk_endereco_id')
                        ->where('emp.empresa_id = ?', $atributos['empresa_id']);
                // $Comando->assemble();
                // exit(0)
                $resultado = $Comando->query()->fetch();
                if (is_array($resultado) && count($resultado) > 0) {
                    $atributos['ModeloEnderecoUnidade'] = $resultado;
                }
            } catch (Exception $Exc) {
                throw $Exc;
            }
        }

        #util::dump($atributos);
        #view
        $this->view->atributos = $atributos;
        $this->renderScript('/documento-operacional/aso/imprimirAso.phtml');
        $this->_helper->layout->disableLayout();
    }

    #AQUI -------------------------------------------------------------------------

    public function imprimirPropostaAction() {
        $os_id = (int) $this->_getParam('osid', 0);
        $osModel = new Application_Model_Os();
        $vigenciaModel = new Application_Model_Vigencia();
        $empresaModel = new Application_Model_EmpresaResponsabilidade();
        $contratoModel = new Application_Model_Contrato();
        $localEntregaModel = new Application_Model_ProdutoContratado();
        $usuarioModel = new Application_Model_Usuario();
        $configuracaoExame = new Application_Model_ConfiguracaoExameOs();
        $unidadeAtiva = $_SESSION['usuario']['unidadeativa']['unidade_id'];


        $this->_log->logDetalhe = 'Resultado relatorio documento de proposta - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-documento-proposta';
        $this->_log->logTabelaNome = 'os';
        $this->_log->logTabelaColunaNome = 'os_id';
        $this->_log->logTabelaColunaValor = $os_id;


        $resultado['os'] = $osModel->obterTudo($os_id);
        $resultado['dtVigencia'] = $vigenciaModel->buscaVigenciaContrato($os_id);
        $resultado['empResponsabilidade'] = $empresaModel->obterEmpresaParaFaturamento($resultado['os'][0]['fk_contrato_id']);
        $resultado['usuario'] = $usuarioModel->obterPessoaPeloId($resultado['os'][0]['fk_usuario_vendedor_id']);

        $produtosContratados = $localEntregaModel->listarProdutosParaProposta($os_id);
        $comando = " unidade.unidade_id = {$unidadeAtiva}";
        $configuracaoExame = $configuracaoExame->buscarProposta($comando);
        $itens = array();
        foreach ($produtosContratados as $key => $produtosConForeach) {
            $itens[] = $produtosConForeach['produto_id'];
        }
        //verifica se o produto foi contratado
        //verifica se o produto foi contratado
        foreach ($configuracaoExame as $key => $configuracaoForeach) {
            if (in_array($configuracaoForeach['produto_id'], $itens)) {
                $itensListaExames[] = array(
                    "produto_nome" => $configuracaoForeach['produto_nome'],
                    "produto_valor" => $configuracaoForeach['precificacao_valor_venda']
                );
            } else {
                $produtoNaoEncontrado = $localEntregaModel->listarProdutosMaisCarosDaTabela($configuracaoForeach['produto_id']);
                $itensListaExames[] = array(
                    "produto_nome" => $produtoNaoEncontrado['produto_nome'],
                    "produto_valor" => $produtoNaoEncontrado['precificar']
                );
            }
        }
        $this->view->produtosExames = $itensListaExames;
        //        var_dump($produtoNaoEncontrado);
        //        die;
        $id_Contrato = $resultado['empResponsabilidade']['fk_contrato_id'];
        $resultado['contrato'] = $contratoModel->obterPelaId((int) $id_Contrato);
        $resultado['locaisEntrega'] = $localEntregaModel->obterProdutosContratadosDaOsSeparadosPorCategoriaLocais($os_id);
        $resultadoProduto = array();
        $resultadoMedia = array();
        //        foreach ($resultado['locaisEntrega'] as $value) {
        //            foreach ($value['produtos'] as $key => $produtos) {
        //                //var_dump($produtos['categoriadoproduto_id']);
        //                if (isset($produtos['categoriadoproduto_id'])) {
        //                    $categoria = $produtos['categoriadoproduto_id'];
        //                    $resultadoProduto[$categoria] = $resultadoProduto[$categoria] + $produtos['produto_contratado_valor_venda'];
        //                }
        //            }
        //            $resultadoMedia[$categoria] = $resultadoProduto[$categoria] / (int) $value['cobranca']['cobrancaos_quantidade_parcela'];
        //        }
        try {
            $adapter = Zend_Db_Table::getDefaultAdapter();
            $sql_empr = "SELECT *
                                 FROM os o, `usuario` u, `contrato` c, `empresa` e, `contratante` ct, `pessoa` p
		 WHERE u.`usuario_id` = o.`fk_usuario_vendedor_id`
                                              AND u.`fk_pessoa_id` = p.`pessoa_id`
				AND o.`fk_contrato_id` = c.`contrato_id`
				AND c.`contrato_id` = ct.`fk_contrato_id`
				AND ct.`fk_empresa_id` =  e.`empresa_id`
                                AND ct.contratante_empresa_principal = 1
				AND o.`os_id` = {$os_id}";

            $prepare = $adapter->prepare($sql_empr);
            $prepare->execute();
            $empresa = $prepare->fetch();
            $atributos = array(
                'os' => $resultado['os'][0],
                'dtVigencia' => $resultado['dtVigencia'][0],
                'empResponsabilidade' => $resultado['empResponsabilidade'],
                'contrato' => $resultado['contrato'][0],
                'locaisEntrega' => $resultado['locaisEntrega'],
                'usuario' => $resultado['usuario']
            );
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }


        $this->view->media = $resultadoMedia;
        $this->view->atributos = $atributos;
        $this->view->proposta = $empresa;
        $this->view->acao = 'acao';
        $this->_helper->layout->disableLayout();
        $this->renderScript('/documento-operacional/proposta/imprimir.phtml');
    }

    public function imprimirRomaneioAction() {

        $this->_log->logDetalhe = 'Resultado relatorio documento de romaneio - Relatorio Operacional';
        $this->_log->logEvento = 'relatorio-documento-de-romaneio';
        $this->_helper->layout->disableLayout();

        if (!is_null($this->_getParam('fornecedor'))) {
            $empresa = $this->_getParam('fornecedor');
            $data1 = Util::dataBD($this->_getParam('data1'));
            $data2 = Util::dataBD($this->_getParam('data2'));
            $modelFatura = new Application_Model_Fatura();
            $this->view->detalhado = $modelFatura->obterFornecedorComPendenciaComProdutos($empresa, $data1, $data2, false);
            $this->view->resumido = $modelFatura->obterFornecedorComPendenciaComProdutos($empresa, $data1, $data2, true);
            $this->renderScript('/documento-operacional/imprimir-romaneio/pdf-fornecedor.phtml');
        } else {
            $fatura_id = (int) $this->_getParam('id', 68);
            $model_fatura = new Application_Model_Fatura();
            $dados = $model_fatura->obterDetalhesDeProdutosFaturados($fatura_id);


            $model_unidade = new Application_Model_Unidade();
            if (isset($_SESSION['usuario']))
                $unidade = $model_unidade->obterEnderecoUnicoUnidade($_SESSION['usuario']['unidadeativa']['unidade_id']);
            else
                $unidade = null;

            $this->view->unidade = $unidade;
            $this->view->atributos = $dados;

            $this->renderScript('/documento-operacional/imprimir-romaneio/pdf.phtml');
        }
    }

    public function relacaoAgendadosAction() {
        $erros = array();
        $filtro = "1";
        self::$_habilitarRegistrarLog = false;
        if ($this->getRequest()->isPost()) :
            $this->_log->logDetalhe = 'Resultado relatorio relacao de agendados - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-relacao-agendados';
            self::$_habilitarRegistrarLog = true;
            $data1 = Util::dataBD($this->_getParam('data1'));
            $data2 = Util::dataBD($this->_getParam('data2'));
            $modo = (int) $this->_getParam('modo');
            $fase = (int) $this->_getParam('fase');
            $tipo = (int) $this->_getParam('tipo');
            $produto = (int) $this->_getParam('produto');
            $especialista = (int) $this->_getParam('especialista');
            $empresa = (int) $this->_getParam('empresa');

            $this->_log->logDetalhe = 'Resultado relatorio relação de agendados - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-relacao-agendados';


            if ($data1 == "" || $data2 == "") :
                $erros[] = "Informe um período.";
            endif;
            if (empty($erros)) :
                $Tipo = null;
                $Produto = null;
                $Especialista = null;
                if ($tipo > 0):
                    $tipoexameModel = new Application_Model_TipoExame();
                    $Tipo = $tipoexameModel->obter($tipo);
                endif;
                if ($produto > 0):
                    $produtoModel = new Application_Model_Produto();
                    $Produto = $produtoModel->obter($produto);
                endif;
                if ($especialista > 0):
                    $especialistaModel = new Application_Model_PessoaEspecialidade();
                    $Especialista = $especialistaModel->obter($especialista);
                endif;
                switch ($fase) :
                    case 0:
                        $filtro .= " AND (agenda.agenda_data_exame BETWEEN '$data1' AND '$data2' OR agenda.agenda_data_clinico BETWEEN '$data1' AND '$data2')";
                        break;
                    case 1:
                        $filtro .= " AND (agenda.agenda_data_exame BETWEEN '$data1' AND '$data2')";
                        break;
                    case 2:
                        $filtro .= " AND (agenda.agenda_data_clinico BETWEEN '$data1' AND '$data2')";
                        break;
                endswitch;
                $filtro .= is_null($Tipo) ? "" : " AND tipoexame.tipoexame_id = '$tipo'";
                $filtro .= ($produto == 0) ? "" : " AND produto.produto_id = '$produto'";
                $filtro .= ($especialista == 0) ? "" : " AND pessoa_especialidade.pessoa_especialidade_id = '$especialista'";
                $filtro .= ($empresa == 0) ? "" : " AND empresa.empresa_id = '$empresa'";
                $filtro .= " AND empresa.fk_unidade_id = " . self::$_idUnidadeAtivaDoUsuarioLogado;
                $relatorio = new Application_Model_Relatorio();
                $this->view->resultado = $relatorio->relacaodeagendados($filtro);
                $this->view->filtro = array(
                    'PERÍODO', Util::dataBR($data1) . ' à ' . Util::dataBR($data2),
                    'FASE', $fase == 0 ? 'TODAS' : ($fase == 1 ? 'EXAMES' : 'CLÍNICO'),
                    'TIPO', is_null($Tipo) ? "TODOS" : strtoupper($Tipo['tipoexame_nome']),
                    'PRODUTO', is_null($Produto) ? "TODOS" : strtoupper($Produto['produto_nome']),
                    'ESPECIALISTA', is_null($Especialista) ? "TODOS" : strtoupper($Especialista['pessoa_nome']));
                $this->view->modo = $modo;
                $this->renderScript('/documento-operacional/relacao-agendados/excel.phtml');
                if ($modo == 1) :
                    $this->_helper->layout->disableLayout();
                endif;
            else:
                $this->view->erros = $erros;
                $this->renderScript('/documento-operacional/relacao-agendados/index.phtml');
            endif;
        else:
            $this->view->erros = $erros;
            $this->renderScript('/documento-operacional/relacao-agendados/index.phtml');
        endif;
    }

    public function relatorioContratoAction() {
        $visualizacao = '/documento-operacional/relatorio-contrato/formulario.phtml';

        # UNIDADES
        $unidade = new Application_Model_Unidade();
        $unidades = $unidade->obterTodos();
        $this->view->unidades = $unidades;
        self::$_habilitarRegistrarLog = false;
        if ($this->getRequest()->isPost()) {

            # PARAMETROS
            $empresa = (int) $this->_getParam('empresa');
            $unidades = $this->_getParam('unidades', array());
            $data1 = $this->_getParam('data1');
            $data2 = $this->_getParam('data2');
            $prosta = $this->_getParam('proposta');
            $contrato = $this->_getParam('contrato');
            $this->view->modo = $modo = $this->_getParam('modo');
            //var_dump($contrato);
            $this->_log->logDetalhe = 'Resultado relatorio de contrato - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-de-contrato';
            $this->_log->logTabelaNome = 'contrato';
            $this->_log->logTabelaColunaNome = 'contrato_id';
            $this->_log->logTabelaColunaValor = $contrato;
            self::$_habilitarRegistrarLog = true;

            if (Util::validaData($data1) && Util::validaData($data2)) {

                $data1 = Util::dataBD($data1);
                $data2 = Util::dataBD($data2);

                # WHERE
                $where = "";
                $where = $where . (empty($unidades) ? "" : (" AND unidade.unidade_id IN (" . implode(', ', $unidades) . ")"));
                $where = $where . ($empresa == 0 ? "" : (" AND empresa.empresa_id = $empresa"));
                if ($contrato == 'on' && $prosta == 'on') {

                } else
                if ($contrato == "on") {
                    $where = $where . " AND os.`os_aprovada` = 1";
                } else {
                    $where = $where . " AND os.`os_aprovada` = 0";
                }

                $where = $where . " AND os.os_data_hora_aprovacao BETWEEN '$data1' AND '$data2'";

                # CONSULTA
                $resultado = array();
                $relatorio = new Application_Model_Relatorio();
                $resultado['lista'] = $relatorio->contratos($where);
                $resultado['dados']['TOTAL'] = count($resultado['lista']);

                # FILTRO
                $filtro = array('UNIDADE' => "", 'EMPRESA' => "");
                $unidade = new Application_Model_Unidade();
                foreach ($unidades as $unidade_id):
                    $tmp = $unidade->obter($unidade_id);
                    $filtro['UNIDADE'] = $filtro['UNIDADE'] . (empty($filtro['UNIDADE']) ? $tmp['unidade_sigla'] : ", {$tmp['unidade_sigla']}");
                    $resultado['dados'][$tmp['unidade_sigla']] = count($relatorio->contratos($where . " AND unidade.unidade_id = '{$tmp['unidade_id']}'"));
                endforeach;
                if ($empresa > 0):
                    $empresamodel = new Application_Model_Empresa();
                    $tmp = $empresamodel->obter($empresa);
                    $filtro['EMPRESA'] = $tmp['empresa_razao'];
                endif;

                # VIEW
                $this->view->resultado = $resultado;
                $this->view->filtro = $filtro;
                if ($modo == 1) {
                    $this->_helper->layout->disableLayout();
                    $visualizacao = '/documento-operacional/relatorio-contrato/documento.phtml';
                } else {
                    $visualizacao = '/documento-operacional/relatorio-contrato/documento.phtml';
                }
            } else {
                $this->view->erros = '<div class="alert alert-danger">Corrija o período.</div>';
            }
        }

        $this->renderScript($visualizacao);
    }

    public function relatorioExamesAlteradosAction() {
        ini_set('max_execution_time', -1);
        $visualizacao = '/documento-operacional/relatorio-exames-alterados/formulario.phtml';
        self::$_habilitarRegistrarLog = false;
        if ($this->getRequest()->isPost()) {
            $this->_log->logDetalhe = 'Resultado relatorio de exames alterar - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-de-exames-alterados';
            self::$_habilitarRegistrarLog = true;

            # PARAMETROS
            $modo = (int) $this->_getParam('modo', 1);
            $data1 = $this->view->data1 = Util::dataBD($this->_getParam('data1'));
            $data2 = $this->view->data2 = Util::dataBD($this->_getParam('data2'));
            $idEspecialista = (int) $this->_getParam('especialista', 0);
            $idEmpresa = (int) $this->_getParam('empresa', 0);
            $idContrato = (int) $this->_getParam('contrato', 0);
            $idPaciente = (int) $this->_getParam('paciente', 0);
            $unidades = $this->_getParam('unidades', array());
            $tiposdeexame = $this->_getParam('tiposdeexame', array());

            # DECLARAÇÕES E MODELS
            $where = "AND (agenda.agenda_data_exame BETWEEN '$data1' AND '$data2')";
            $filtro = array('UNIDADE' => "", 'TIPOS DE EXAME' => "");
            $resultado = array('dados' => array(), 'lista' => array());
            $modelRelatorio = new Application_Model_Relatorio();
            $modelUnidade = new Application_Model_Unidade();
            $modelTipoExame = new Application_Model_TipoExame();
            $modelEmpresa = new Application_Model_Empresa();
            $modelPessoa = new Application_Model_Pessoa();
            $modelContrato = new Application_Model_Contrato();

            # FILTROS
            if ($idEspecialista > 0):
                $where = $where . " AND pessoa_especialidade.fk_pessoa_id = '$idEspecialista'";
                $tmp = $modelPessoa->obter($idEspecialista);
                $filtro['ESPECIALISTA'] = $tmp['pessoa_nome'];
                $this->view->especialista = $tmp;
            endif;
            if ($idEmpresa > 0):
                $where = $where . " AND empresa.empresa_id = '$idEmpresa'";
                $tmp = $modelEmpresa->obter($idEmpresa);
                $filtro['EMPRESA'] = $tmp['empresa_razao'];
                $this->view->empresa = $tmp;
            endif;
            if ($idContrato > 0):
                $where = $where . " AND contrato.contrato_id = '$idContrato'";
                $tmp = $modelContrato->obterContratoCompletoComEmpresa($idContrato, $idEmpresa);
                $filtro['CONTRATO'] = $tmp['contrato_numero'];
                $this->view->contrato = $tmp;
            endif;
            if ($idPaciente > 0):
                $where = $where . " AND pessoa.pessoa_id = '$idPaciente'";
                $tmp = $modelPessoa->obter($idPaciente);
                $filtro['PACIENTE'] = $tmp['pessoa_nome'];
                $this->view->paciente = $tmp;
            endif;
            if (!empty($unidades)):
                $where = $where . " AND unidade.unidade_id IN (" . implode(",", $unidades) . ")";
            endif;
            if (!empty($tiposdeexame)):
                $where = $where . " AND tipoexame.tipoexame_id IN (" . implode(",", $tiposdeexame) . ")";
            endif;

            switch ($modo):
                case 2:
                    if ((int) $idEmpresa == 0 && (int) $idContrato == 0) {
                        $this->view->erros = "<div class='alert alert-warning'>Este tipo de relatório exige a seleção de empresa e contrato.</div>";
                        $visualizacao = 'documento-operacional/relatorio-exames-alterados/formulario.phtml';
                        break;
                    }
                    $where2 = $where;
                    $where2 .= " AND agenda.fk_empresa_id = {$idEmpresa} AND agenda.fk_contrato_id = {$idContrato} ";
                    $where2 .= " AND funcionario.fk_empresa_id = {$idEmpresa} AND funcionario.fk_contrato_id = {$idContrato}";
                    $this->view->quadro3nr07 = $modelRelatorio->relatorioAnualQuadro3NR07($where2);
                    $this->view->relacaoExames = $modelRelatorio->relatorioAnualRelacaoPorExames($where2);
                    $this->view->relacaoExamesClinicos = $modelRelatorio->relatorioAnualRelacaoPorExamesClinicos($where2);
                    $this->_helper->layout->disableLayout();
                    $visualizacao = '/documento-operacional/relatorio-exames-alterados/relatorio-anual.phtml';
                    break;
                case 0:
                case 1:
                default:
                    # LISTA
                    $r = $modelRelatorio->relatorioAnualExamesAlterados($where);
                    $resultado['lista'] = $r;
                    $resultado['dados']['TOTAL'] = count($resultado['lista']);

                    # VIEW
                    $this->view->resultado = $resultado;
                    $visualizacao = '/documento-operacional/relatorio-exames-alterados/documento.phtml';
            endswitch;

            # DADOS
            foreach ($unidades as $unidade):
                $tmp = $modelUnidade->obter($unidade);
                $r = $modelRelatorio->relatorioAnualExamesAlterados($where . " AND unidade.unidade_id = '{$tmp['unidade_id']}'");
                $resultado['dados'][$tmp['unidade_sigla']] = count($r);
                $filtro['UNIDADE'] .= (empty($filtro['UNIDADE'])) ? $tmp['unidade_sigla'] : ", {$tmp['unidade_sigla']}";
            endforeach;
            foreach ($tiposdeexame as $item):
                try {
                    $tmp = $modelTipoExame->obter($item);
                } catch (Exception $ex) {
                    Util::dump($item, false, false);
                    die($ex->getMessage());
                }
                $r = $modelRelatorio->relatorioAnualExamesAlterados($where . " AND tipoexame.tipoexame_id = '{$tmp['tipoexame_id']}'");
                $resultado['dados'][$tmp['tipoexame_nome']] = count($r);
                $filtro['TIPOS DE EXAME'] .= (empty($filtro['TIPOS DE EXAME'])) ? $tmp['tipoexame_nome'] : ", {$tmp['tipoexame_nome']}";
            endforeach;
            $this->view->filtro = $filtro;
        }
        $this->renderScript($visualizacao);
    }

    /*
      // Comentada em 07/01/2015
      public function relatorioEmpregadosAction() {
      $script = "/documento-operacional/relatorio-empregados/formulario-empregado.phtml";
      $empresaModel = new Application_Model_Empresa();
      $this->view->empresas = $empresaModel->listarEmpresas();
      $this->acao = 'Relatório de Empregados';
      $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
      self::$_habilitarRegistrarLog = false;
      if ($this->getRequest()->isPost()) {
      $this->_log->logDetalhe = 'Resultado relatorio de exames alterados - Relatorio Operacional';
      $this->_log->logEvento = 'relatorio-de-empregados';
      self::$_habilitarRegistrarLog = true;
      $ativo = $_POST['ativo'];
      $cpf = $_POST['cpf'];
      $nome = $_POST['nome'];
      $contrato = $_POST['contrato'];
      $empresa_id = $this->_getParam('empresa', 0);

      $where = "pessoa.pessoa_nome LIKE '%{$nome}%' ";

      if ($cpf !== "") {
      $cpfFormatado = str_replace(array('.', '-'), "", $cpf);
      $where .= " AND pessoa.`pessoa_cpf` = {$cpfFormatado} ";
      }
      //            if ($ativo !== "") {
      //                $where .= "AND funcionario_status = {$ativo}";
      //            }
      if ($contrato !== "") {
      $contratoModel = new Application_Model_Contrato();
      $where .= " AND contrato.contrato_numero = {$contrato} ";
      }

      if ($ativo == 1) {
      $where .= " AND te.`tipoexame_nome` <> 'Demissional'";
      } else {
      if ($ativo == 3) {
      $where .= "AND te.`tipoexame_nome` = 'Demissional'";
      }
      }

      $where = $where . ($empresa_id > 0 ? " AND funcionario.fk_empresa_id = {$empresa_id}" : "");
      $model_funcionario = new Application_Model_Funcionario();
      $resultados = $model_funcionario->obterPeloFiltroRelatorio($where);
      $this->view->resultados = $resultados;
      $this->_helper->layout->disableLayout();
      $script = "/documento-operacional/relatorio-empregados/index.phtml";
      }

      $this->renderScript($script);
      }
     */

    public function relatorioEmpregadosAction() {
        /*
         * - Nota Importante para o Desenvolvedor.
         * Este método foi atualizado, a versão antiga foi mantida comentada.
         * A versão comentada está neste mesmo arquivo.
         */
        $script = "/documento-operacional/relatorio-empregados/formulario-empregado.phtml";
        $empresaModel = new Application_Model_Empresa();
        $this->view->empresas = $empresaModel->listarEmpresas('empresa.empresa_fantasia ASC');
        $this->acao = 'Relatório de Empregados';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
        self::$_habilitarRegistrarLog = false;
        $resultados = array();
        $titulo = '';
        $paramFiltroUsados = array('filtro_empresa_razao' => 'Todas', 'filtro_contrato_numero' => 'Todos', 'filtro_tipo_relatorio' => 'Funcionarios Ativos');

        if ($this->getRequest()->isPost()) {
            $this->_log->logDetalhe = 'Resultado relatorio de exames alterados - Relatorio Operacional';
            $this->_log->logEvento = 'relatorio-de-empregados';
            self::$_habilitarRegistrarLog = true;
            //$ativo = $_POST['ativo'];
            $ativo = (int) $this->getParam('ativo', 1);
            //$cpf = $_POST['cpf'];
            //$nome = $_POST['nome'];
            $contrato = $_POST['contrato'];
            $empresa_id = $this->_getParam('empresa', 0);


            require_once("../application/business/FuncionarioBusiness.php");
            try {
                $NegocioFuncionario = new FuncionarioBusiness();
                $colecaoContratosIds = $colecaoEmpresasIds = array();
                if (is_numeric($contrato) && (int) $contrato > 0) {
                    $colecaoContratosIds[] = $contrato;
                }
                if (is_numeric($empresa_id) && (int) $empresa_id > 0) {
                    $colecaoEmpresasIds[] = $empresa_id;
                }
                if ($ativo == 1) {
                    $titulo = 'RELATÓRIO DE EMPREGADOS ATIVOS';
                    $resultados = $NegocioFuncionario->obterColecaoFuncionariosAtivos($colecaoEmpresasIds, $colecaoContratosIds);
                    //$resultados = $NegocioFuncionario->obterColecaoFuncionariosAtivos($colecaoEmpresasIds, $colecaoContratosIds, array(), '2010-01-01', '2018-01-01', FuncionarioBusiness::REGRA_ATIVO_COORDENACAO_PCMSO);
                } else {
                    $paramFiltroUsados['filtro_tipo_relatorio'] = 'Funcionários Inativos';
                    $resultados = $NegocioFuncionario->obterColecaoFuncionariosInativos($colecaoEmpresasIds, $colecaoContratosIds);
                    $titulo = 'RELATÓRIO DE EMPREGADOS INATIVOS';
                }

                if (is_numeric($contrato) && (int) $contrato > 0) {
                    $ModeloContrato = new Application_Model_Contrato();
                    $Resultado = $ModeloContrato->fetchRow(array('contrato_id = ?' => $contrato));
                    $paramFiltroUsados['filtro_contrato_numero'] = ($Resultado) ? $Resultado->contrato_numero : 'Todos';
                }

                if (is_numeric($empresa_id) && (int) $empresa_id > 0) {
                    $ModeloEmpresa = new Application_Model_Empresa();
                    $Resultado = $ModeloEmpresa->fetchRow(array('empresa_id = ?' => $empresa_id));
                    $paramFiltroUsados['filtro_empresa_razao'] = ($Resultado) ? $Resultado->empresa_razao : 'Todas';
                }
            } catch (Exception $exc) {
                throw $exc;
            }
            $this->view->paramFiltroUsados = $paramFiltroUsados;
            $this->view->relatorioTitulo = $titulo;
            $this->view->resultados = $resultados;
            $this->_helper->layout->disableLayout();
            $script = "/documento-operacional/relatorio-empregados/index.phtml";
        }
        $this->renderScript($script);
    }

    public function convocacaoDePeriodicoAction() {

        $erros = array();
        $empresaModel = new Application_Model_Empresa();
        $this->view->empresas = $empresaModel->listarEmpresas('empresa.empresa_fantasia ASC');
        $filtro = "1";
        self::$_habilitarRegistrarLog = false;
        if ($this->getRequest()->isPost()) :
            $this->_log->logDetalhe = 'Resultado Convocação de Periódico - Relatorio Operacional';
            $this->_log->logEvento = 'Convocação de Periódico';
            self::$_habilitarRegistrarLog = true;
            $data1 = Util::dataBD($this->_getParam('data1'));
            $data2 = Util::dataBD($this->_getParam('data2'));
            $modo = (int) $this->_getParam('modo');
            $tipo = (int) $this->_getParam('tipo');
            $empresa = (int) $this->_getParam('empresa');
            $contrato = (int) $this->_getParam('contrato');
            $tipoTEXTO = NULL;
            $diff = NULL;

            if ($data1 == "" || $data2 == "") :
                #$erros[] = "Informe um período.";
                $msgtexto = "<span style='text-transform: initial'>Informe um período válido</span>!";
                $this->view->alertamsg = $alertamsg = "<div class='alert alert-warning fade in' role='alert' id='msgalerta'><p style='color:red;'>Atenção:</p> ". $msgtexto ."<div style='text-transform: lowercase'> </div></div>";
            else :
                $dateinicio = date_create($data1);
                $datefim = date_create($data2);
                $diff = date_diff($dateinicio,$datefim);
                $dias = $diff->format("%a");
                if ($dias > 365) {
                   $msgtexto = "<span style='text-transform: initial'>Período excedido! Intervalo máximo de um ano.</span>!";
                    $this->view->alertamsg = $alertamsg = "<div class='alert alert-warning fade in' role='alert' id='msgalerta'><p style='color:red;'>Atenção:</p> ". $msgtexto ."<div style='text-transform: lowercase'> </div></div>";
                }
            endif;



            if (empty($msgtexto)) :

                switch ($tipo) {
                    case 0:
                        #PERIÓDICOS
                        $filtro = " (consulta.proximoexame BETWEEN '$data1' AND '$data2')";
                        if ($empresa > 0) {
                            $filtro .= "AND (consulta.empresa_id = '$empresa')";
                        }
                        if ($contrato > 0) {
                            $filtro .= "AND (consulta.contrato_id = '$contrato')";
                        }
                        $DocumentoOperacional = new Application_Model_DocumentoOperacional();
                        $resultado = $DocumentoOperacional->obterFiltroPeriodicos($filtro, $data1, $data2);
                        $tipoTEXTO = 'PERIÓDICOS';
                        break;

                    default:

                        break;
                }

                if (empty($resultado)) {
                    $msgtexto = "<span style='text-transform: initial'>Não há dados para esta consulta</span>!";
                    $this->view->alertamsg = $alertamsg = "<div class='alert alert-warning fade in' role='alert' id='msgalerta'><p style='color:red;'>Atenção:</p> ". $msgtexto ."<div style='text-transform: lowercase'> </div></div>";
                    $this->view->dadospesquisa = $dadosPesquisa = array('data1' => $data1,
                                                                        'data2' => $data2,
                                                                        'tipo' => $tipo,
                                                                        'empresa' => $empresa,
                                                                        'contrato' => $contrato,
                                                                        );
                    $this->renderScript('/documento-operacional/convocacao-de-periodico/2019/index.phtml');
                }else{

                    $emp = new Application_Model_Empresa();
                    $dadosemp = $emp->obter($empresa);

                    $contr = new Application_Model_Contrato();
                    $dadoscontr = $contr->obter($contrato);

                    $dados = array('PERÍODO: ' => Util::dataBR($data1) . " À " . Util::dataBR($data2),
                                   'FILTRO: ' => $tipoTEXTO,
                                   'EMPRESA: ' => empty($dadosemp) ? "TODAS" : strtoupper($dadosemp['empresa_fantasia']),
                                   'CONTRATO: ' => empty($dadoscontr) ? "TODOS" : strtoupper($dadoscontr['contrato_numero']),
                                   #'TOTAL: ' => empty($resultado) ? "0" : count($resultado),
                                  );
                    $this->view->filtro = $dados;
                    $this->view->resultado = $resultado;
                    $this->view->modo = $modo;
                    $this->view->tipo = $tipo;
                    $this->renderScript('/documento-operacional/convocacao-de-periodico/2019/excel.phtml');
                    if ($modo == 1) :
                        $this->_helper->layout->disableLayout();
                    endif;

                }
            else:
                $this->view->erros = $erros;
                $this->renderScript('/documento-operacional/convocacao-de-periodico/2019/index.phtml');
            endif;
        else:
            $this->view->erros = $erros;
            $this->renderScript('/documento-operacional/convocacao-de-periodico/2019/index.phtml');
        endif;
    }

    public function _convocacaoDePeriodicoAction() {
        $this->_log->logDetalhe = 'Carregando formulário para emissao do relatorio de convocacao de periodico';
        $script = $this->getRequest()->isPost() ? 'documento-operacional/convocacao-de-periodico/relatorio.phtml' : 'documento-operacional/convocacao-de-periodico/formulario.phtml';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao('Relatório', 'Operacional', 'Convocacão de Periódico');
        $Setor = new Application_Model_Setor();
        $Empresa = new Application_Model_Empresa();
        $Contrato = new Application_Model_Contrato();
        $Funcionario = new Application_Model_Funcionario();

        if ($this->getRequest()->isPost()) {
            $this->_desabilitarCarregamentoDoTemplate();
            $this->_log->logDetalhe = 'Processando formulário para emissao do relatorio de convocacao de periodico';
            $data1 = $this->_getParam('data1');
            $data2 = $this->_getParam('data2');
            $setor = (int) $this->_getParam('setor');
            $empresa = (int) $this->_getParam('empresa');
            $contrato = (int) $this->_getParam('contrato');
            $funcionario = (int) $this->_getParam('funcionario');

            $erros = array();
            if ($data1 == "")
                $erros[] = "Corrija a data inicial.";
            if ($data2 == "")
                $erros[] = "Corrija a data final.";

            if (count($erros) == 0) {

                $filtros = array(
                    'data1' => $data1,
                    'data2' => $data2,
                    'setor' => null,
                    'empresa' => null,
                    'contrato' => null,
                    'funcionario' => null,
                );

                if ($empresa > 0) {
                    $dados = $Empresa->obter($empresa);
                    $filtros['empresa'] = $dados['empresa_fantasia'];
                }

                if ($contrato > 0) {
                    $dados = $Contrato->obter($contrato);
                    $filtros['contrato'] = $dados['contrato_numero'];
                }

                if ($setor > 0) {
                    $dados = $Setor->obter($setor);
                    $filtros['setor'] = $dados['setor_nome'];
                }

                if ($funcionario > 0) {
                    $dados = $Funcionario->obter($funcionario);
                    $filtros['funcionario'] = $dados['pessoa_nome'];
                }

                $this->view->filtros = $filtros;

                $where = "fm.fichamedica_data_proximo_exame BETWEEN STR_TO_DATE('$data1', '%d/%m/%Y') AND STR_TO_DATE('$data2', '%d/%m/%Y')";
                $where = $where . ($setor > 0 ? " AND se.setor_id = '$setor'" : "");
                $where = $where . ($empresa > 0 ? " AND e.empresa_id = '$empresa'" : "");
                $where = $where . ($contrato > 0 ? " AND c.contrato_id = '$contrato'" : "");
                $where = $where . ($funcionario > 0 ? " AND f.funcionario_id = '$funcionario'" : "");
                $where .= " AND c.fk_unidade_id = " . (int) self::$_unidadeIdEmContexto;

                $sql = "SELECT
                                *
                        FROM
                        (
                            SELECT
                                fm.fichamedica_id,
                                e.empresa_id,
                                c.contrato_id,
                                p.pessoa_id,
                                p.pessoa_cpf,
                                fu.funcao_nome,
                                p.pessoa_nome,
                                te.tipoexame_nome,
                                te.tipoexame_sigla,
                                a.agenda_data_exame,
                                a.agenda_data_clinico,
                                fm.fichamedica_data_proximo_exame,
                                fm.fichamedica_resultado_aptidao,
                                m.pessoa_nome AS medico,
                                (SELECT GROUP_CONCAT(es.especialidade_nome) FROM encaminhamento_especialidade ee JOIN especialidade es ON es.especialidade_id = ee.fk_especialidade_id WHERE ee.fk_fichamedica_id = fm.fichamedica_id) AS encaminhado
                            FROM fichamedica fm
                                JOIN agenda a ON (a.agenda_id = fm.fk_agenda_id AND a.agenda_status = 0)

                                /*@author: Silas Stoffel
                                 *
                                 * Esta é a maneira como estava antes.
                                 * O que fiz foi jogar este mesmo comando como LEFT JOIN nas ultimas
                                 * Linhas do bloco de JOIN
                                 * JOIN pessoa_especialidade pe ON (pe.pessoa_especialidade_id = a.fk_pessoa_especialidade_id)
                                 * JOIN pessoa m ON (m.pessoa_id = pe.fk_pessoa_id)
                                 */
                                JOIN alocacao al ON (al.alocacao_id = a.fk_alocacao_id)
                                JOIN funcionario f ON (f.funcionario_id = al.fk_funcionario_id AND f.funcionario_status = 0 AND (f.funcionario_motivo_inativacao = '' OR f.funcionario_motivo_inativacao IS NULL))
                                JOIN funcao fu ON (fu.funcao_id = al.fk_funcao_id)
                                JOIN cargo ca ON (ca.cargo_id = al.fk_cargo_id)
                                JOIN tipoexame te ON (te.tipoexame_id = a.fk_tipoexame_id)
                                JOIN pessoa p ON (p.pessoa_id = f.fk_pessoa_id)
                                JOIN empresa e ON (e.empresa_id = f.fk_empresa_id)
                                JOIN contrato c ON (c.contrato_id = f.fk_contrato_id)
                                LEFT JOIN setor se ON (se.setor_id = al.fk_setor_id)
                                /*
                                 * Este bloco estava acima e usava join e para funcionar foi
                                 * preciso colocar o LEFT JOIN
                                 */
                                LEFT JOIN pessoa_especialidade pe ON (pe.pessoa_especialidade_id = a.fk_pessoa_especialidade_id)
                                LEFT JOIN pessoa m ON (m.pessoa_id = pe.fk_pessoa_id)

                            WHERE fm.fichamedica_status = 0
                                  AND $where
                            ORDER BY p.pessoa_nome ASC
                        ) AS sub
                    WHERE (
				SELECT ste.tipoexame_sigla
				FROM agenda AS sa, tipoexame AS ste
				WHERE sa.agenda_status = 0
                                      AND sa.fk_tipoexame_id = ste.tipoexame_id
                                      AND sa.fk_contrato_id = sub.contrato_id
                                      AND sa.fk_empresa_id = sub.empresa_id
                                      AND sa.fk_pessoa_id = sub.pessoa_id
				ORDER BY sa.agenda_id DESC
			  LIMIT 1
			) <> 'DEM'
                    HAVING sub.tipoexame_sigla <> 'DEM'";

                try {
                    $pre = Zend_Db_Table::getDefaultAdapter()->prepare($sql);
                    $pre->execute();
                    $this->view->resultado = $pre->fetchAll();
                } catch (Exception $ex) {
                    $this->view->resultado = $ex->getMessage();
                }
            } else {
                $this->view->resultado = implode("<br>", $erros);
            }
        } else {
            $this->view->empresas = $Empresa->buscarCompletaUsandoClausula(null, 'empresa.empresa_fantasia ASC');
        }
        $this->renderScript($script);
    }

    public function controleEvolutivoAudAction() {

    }

    public function imprimirFichaDeEpisAction() {
        $id = (int) $this->_getParam('funcionario');
        $this->_desabilitarCarregamentoDoTemplate();
        $Funcionario = new Application_Model_Funcionario();
        $FuncionarioEpi = new Application_Model_FuncionarioEpi();
        $this->view->atributos = $Funcionario->obter($id);
        $this->view->lista = $FuncionarioEpi->obterPeloFuncionario($id);
        $this->renderScript('documento-operacional/epi/ficha-de-epis.phtml');
    }

    public function esocialReciboAction() {
        $url      = $_SERVER['REQUEST_URI'];
        $urlParts = explode('/', $url);
        $id       = end($urlParts);
        $tipo     = 'S2220';

        $ModelEsocialDetalheEnvio = new Application_Model_EsocialDetalheEnvio();
        $dados = $ModelEsocialDetalheEnvio->dadosEventosEnviado($id, $tipo);
        
        $this->view->dados = $dados;
        $this->renderScript('documento-operacional/recibo-esocial/index.phtml');
        $this->_helper->layout->disableLayout();
    }

}
