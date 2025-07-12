<?php

class RelatorioController extends Controller {

    public function init() {
        parent::init();
        $this->modulo = 'portal cliente';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
    }

    public function indexAction() {

    }

    public function acessoNegadoAction() {
        $c = $this->_getParam("c");
        $a = $this->_getParam("a");
        $model = new Application_Model_Acao();
        $acao = $model->obterPeloFiltro("acao.acao_nome = '{$a}'");
        $model = new Application_Model_Servico();
        $serv = $model->obterPeloFiltro("servico.servico_nome = '{$c}'");
        $this->view->c = $serv['servico_apelido'];
        $this->view->a = $acao['acao_apelido'];
    }

    public function convocacaoPeriodicoAction() {

        $erros = array();
        $empresaModel = new Application_Model_Empresa();
        $this->view->empresas = $empresaModel->listarEmpresas('empresa.empresa_fantasia ASC');
        $filtro = "1";
        if ($this->getRequest()->isPost()) :
            $data1 = Util::dataBD($this->_getParam('data1'));
            $data2 = Util::dataBD($this->_getParam('data2'));
            $modo = 1;
            $tipo = 0;
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

    public function _convocacaoPeriodicoAction() {
        $script = 'documento-operacional/convocacao-de-periodico/2016/formulario.phtml';

        # MODELS
        $Empresa = new Application_Model_Empresa();
        $DocumentoOperacional = new Application_Model_DocumentoOperacional();

        # LISTAS
        #$this->view->empresas = $Empresa->buscarCompletaUsandoClausula(null, 'empresa.empresa_fantasia ASC');
        # POST
        if ($this->getRequest()->isPost()) {

            # CONSULTA (PARAMETROS OBTIDOS NO MODELO) [RETORNO: ARRAY('lista' => {lista}, 'info' => {filtros})]
            $this->view->resultado = $DocumentoOperacional->convocacaoDePeriodico();

            # VIEW
            $this->_desabilitarCarregamentoDoTemplate();
            $script = 'documento-operacional/convocacao-de-periodico/2016/relatorio.phtml';
        }

        # FIM
        $this->renderScript($script);
    }

    public function agendamentoAction() {

        if ($this->getRequest()->isPost()) {

            # Obtendo dados
            $data_inicio = $this->_getParam('data_inicio');
            $data_fim = $this->_getParam('data_fim');
            $funcionario_id = (int) $this->_getParam('funcionario_id', 0);
            $tipoexame_id = (int) $this->_getParam('tipoexame_id', 0);

            # Validação
            $podecontinuar = Util::eregDataBR($data_inicio) && Util::eregDataBR($data_fim) ? true : false;

            # Se NÃO pode continuar, verifica o que está errado
            if (!$podecontinuar) {
                $erros = array();
                $corrigir = array();
                if (!Util::eregDataBR($data_inicio)) {
                    $erros[] = 'Corrija a data de início';
                    $corrigir[] = 'data_inicio';
                }
                if (!Util::eregDataBR($data_fim)) {
                    $erros[] = 'Corrija a data de fim';
                    $corrigir[] = 'data_fim';
                }
                $feedback = array(
                    'erro' => 2,
                    'msg' => implode("<br/>", $erros),
                    'corrigir' => $corrigir);
                $this->_helper->layout->disableLayout();
                $this->view->feedback = $feedback;
                $this->renderScript("feedback.phtml");
            } else {

                # Se estiver tudo OK
                $model_funcionario = new Application_Model_Funcionario();
                $model_tipoexame = new Application_Model_TipoExame();
                $filtro = array();
                $filtro['funcionario'] = isset($model_funcionario->obterComoObjeto($funcionario_id)->pessoa_nome) ? $model_funcionario->obterComoObjeto($funcionario_id)->pessoa_nome : "";
                $filtro['tipoexame'] = isset($model_tipoexame->obterComoObjeto($tipoexame_id)->tipoexame_nome) ? $model_tipoexame->obterComoObjeto($tipoexame_id)->tipoexame_nome : "";
                $filtros = "FUNCIONÁRIO: {$filtro['funcionario']} | "
                        . "TIPO DE EXAME: {$filtro['tipoexame']} | "
                        . "PERÍODO: DE {$data_inicio} ATÉ {$data_fim}";
                $data_inicio = Util::dataBD($data_inicio);
                $data_fim = Util::dataBD($data_fim);
                $where = "AND 2 = 2";
                $where = $where . ($funcionario_id > 0 ? " AND funcionario.funcionario_id = {$funcionario_id}" : "");
                $where = $where . ($tipoexame_id > 0 ? " AND tipoexame.tipoexame_id = {$tipoexame_id}" : "");
                $sql = "SELECT *
                FROM agenda
                JOIN tipoexame ON tipoexame.tipoexame_id = agenda.fk_tipoexame_id
                JOIN pessoa ON pessoa.pessoa_id = agenda.fk_pessoa_id
                JOIN alocacao ON alocacao.alocacao_id = agenda.fk_alocacao_id
                JOIN funcionario ON funcionario.funcionario_id = alocacao.fk_funcionario_id
                LEFT JOIN horario_global ON horario_global.horario_global_id = agenda.fk_horario_global_id
                WHERE 1 = 1
                AND agenda.fk_contrato_id = {$_SESSION['contrato_id']}
                AND agenda.fk_empresa_id = {$_SESSION['empresa']['empresa_id']}
                AND agenda.agenda_data_exame BETWEEN '{$data_inicio}' AND '{$data_fim}'
                {$where}
                ORDER BY pessoa.pessoa_nome ASC";
                $adapter = Zend_Db_Table::getDefaultAdapter();
                $prep = $adapter->prepare($sql);
                $prep->execute();
                $_agendas = $prep->fetchAll();

                $agendas = array();
                foreach ($_agendas as $agenda) {
                    $sql = "SELECT *
                    FROM produto_agenda
                    JOIN produto ON produto.produto_id = produto_agenda.fk_produto_id
                    WHERE produto_agenda.fk_agenda_id = {$agenda['agenda_id']}";
                    $prep = $adapter->prepare($sql);
                    $prep->execute();
                    $agenda['produtos'] = $prep->fetchAll();
                    $agendas[] = $agenda;
                }

                $this->view->agendas = $agendas;
                $this->view->filtrosUtilizados = $filtros;

                $this->_helper->layout->disableLayout();
                $this->renderScript('relatorio/agendamento/excel.phtml');
            }
        } else {

            # Tipos de Exame
            $model_tipoexame = new Application_Model_TipoExame();
            $tiposdeexame = $model_tipoexame->obterTodos();
            $this->view->tiposexame = $tiposdeexame;

            # Funcionários
            $model_funcionario = new Application_Model_Funcionario();
            $funcionarios = $model_funcionario->listarFuncionarioPeloContrato($_SESSION['contrato_id'], $_SESSION['empresa']['empresa_id']);
            $this->view->funcionarios = $funcionarios;

            $this->renderScript('relatorio/agendamento/agendamento.phtml');
        }
    }

    public function funcionarioAction() {
        $atributos = $_SESSION;
        //var_dump($atributos['contrato_id']);
        $funcionario = new Application_Model_Funcionario();
        $cargo = new Application_Model_Cargo();
        $funcao = new Application_Model_Funcao();

        $resultadoFuncionario = $funcionario->listarFuncionarioPeloContrato($atributos['contrato_id'], $atributos['empresa']['empresa_id']);
        $resultadoCargo = $cargo->obterTodos();
        $resultadoFuncao = $funcao->obterTodos();


        $this->view->funcionarios = $resultadoFuncionario;
        $this->view->cargo = $resultadoCargo;
        $this->view->funcao = $resultadoFuncao;
        $view = "/relatorio/funcionario/index.phtml";
        $this->renderScript($view);
    }

    public function graficosAction() {
        $produto = new Application_Model_ProdutoContratado();
        $produtos = $produto->obterProduto();

        $this->view->produtos = $produtos;
        $view = "/relatorio/graficos/index.phtml";
        $this->renderScript($view);
    }

    public function graficoAction() {
        $ano = ($_GET['dataAno'] != "") ? $_GET['dataAno'] : 2014;
        $anoBuscado = $ano;
        unset($ano);
        //var_dump($anoBuscado);die;
        $agenda = new Application_Model_Agenda();

        $table = array();
        $rows = array();
        $flag = true;

        $table['cols'] = array(
            array('label' => 'MEIO', 'type' => 'string'),
            array('label' => 'ADMISSIONAL', 'type' => 'number'),
            array('label' => 'PERIODICO', 'type' => 'number'),
            array('label' => 'MUDANÇA DE FUNÇÃO', 'type' => 'number'),
            array('label' => 'RETORNO AO TRABALHO', 'type' => 'number'),
            array('label' => 'DEMISSIONAL', 'type' => 'number'),
        );
        for ($index = 1; $index < 13; $index++) {
            $resultado = $agenda->obterPorTipo($index, $anoBuscado);
            //var_dump($resultado); die;
            switch ((int) $index) {
                case 1: $mes = 'Jan';
                    break;
                case 2: $mes = 'Fev';
                    break;
                case 3: $mes = 'Mar';
                    break;
                case 4: $mes = 'Abr';
                    break;
                case 5: $mes = 'Maio';
                    break;
                case 6: $mes = 'Jun';
                    break;
                case 7: $mes = 'Jul';
                    break;
                case 8: $mes = 'Ago';
                    break;
                case 9: $mes = 'Set';
                    break;
                case 10: $mes = 'Out';
                    break;
                case 11: $mes = 'Nov';
                    break;
                case 12: $mes = 'Dez';
                    break;
            }
            $indexMes = $mes;
            $temp = array();

            if (isset($resultado[0]['QNT'])) {
                $temp[] = array('v' => (string) $indexMes);
                foreach ($resultado as $row) {
                    switch ($row['SGL']) {
                        case "ADM": $temp[1] = array('v' => (int) $row['QNT']);
                            break;
                        case "PER": $temp[2] = array('v' => (int) $row['QNT']);
                            break;
                        case "MUD": $temp[3] = array('v' => (int) $row['QNT']);
                            break;
                        case "RET": $temp[4] = array('v' => (int) $row['QNT']);
                            break;
                        case "DEM": $temp[5] = array('v' => (int) $row['QNT']);
                            break;
                    }
                }
            } else {
                $temp[] = array('v' => (string) $indexMes);
                $temp[] = array('v' => (int) 0);
                $temp[] = array('v' => (int) 0);
                $temp[] = array('v' => (int) 0);
                $temp[] = array('v' => (int) 0);
                $temp[] = array('v' => (int) 0);
            }
            $rows[] = array('c' => $temp);
        }
        $table['rows'] = $rows;
        $jsonTable = json_encode($table);
        echo $jsonTable;
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function graficoFaltasAction() {
        $ano = ($_GET['dataAno'] != "") ? $_GET['dataAno'] : 2014;
        $fases = ($_GET['fases'] != "") ? $_GET['fases'] : 0;
        $anoBuscado = $ano;
        $fase = $fases;
        unset($ano);
        unset($fases);

        $agenda = new Application_Model_Agenda();

        $table = array();
        $rows = array();
        $flag = true;

        $table['cols'] = array(
            array('label' => 'MEIO', 'type' => 'string'),
            array('label' => 'PRESENTES', 'type' => 'number'),
            array('label' => 'FALTOSOS', 'type' => 'number'),
        );
        for ($index = 1; $index < 13; $index++) {
            $resultado = $agenda->obterGraficoFaltas($index, $anoBuscado, $fase);
            switch ((int) $index) {
                case 1: $mes = 'Jan';
                    break;
                case 2: $mes = 'Fev';
                    break;
                case 3: $mes = 'Mar';
                    break;
                case 4: $mes = 'Abr';
                    break;
                case 5: $mes = 'Maio';
                    break;
                case 6: $mes = 'Jun';
                    break;
                case 7: $mes = 'Jul';
                    break;
                case 8: $mes = 'Ago';
                    break;
                case 9: $mes = 'Set';
                    break;
                case 10: $mes = 'Out';
                    break;
                case 11: $mes = 'Nov';
                    break;
                case 12: $mes = 'Dez';
                    break;
            }
            $indexMes = $mes;
            $temp = array();

            if (isset($resultado[0]['QNT'])) {
                $temp[] = array('v' => (string) $indexMes);
                foreach ($resultado as $row) {
                    switch ($row['MEIO']) {
                        case 1: $temp[1] = array('v' => (int) $row['QNT']);
                            break;
                        case 0: $temp[2] = array('v' => (int) $row['QNT']);
                            break;
                    }
                }
            } else {
                $temp[] = array('v' => (string) $indexMes);
                $temp[] = array('v' => (int) 0);
                $temp[] = array('v' => (int) 0);
            }
            $rows[] = array('c' => $temp);
        }
        $table['rows'] = $rows;
        $jsonTable = json_encode($table);
        echo $jsonTable;
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function graficoPeriodoAction() {
        //var_dump($_GET);die;
        $anoInicio = ($_GET['dataInicio'] != "") ? $_GET['dataInicio'] : date("Y");
        $anoFim = ($_GET['dataFim'] != "") ? $_GET['dataFim'] : date("Y");
        $anoBuscadoInicio = $anoInicio;
        $anoBuscadoFim = $anoFim + 1;
        unset($anoInicio);
        unset($anoFim);
        $tipoExame = "";

        $agenda = new Application_Model_Agenda();

        $table = array();
        $rows = array();
        $lista = array();
        $flag = true;

        for ($index1 = $anoBuscadoInicio; $index1 < $anoBuscadoFim; $index1++) {
            if ($index1 == $anoBuscadoInicio) {
                $lista[] = array('label' => 'MEIO', 'type' => 'string');
            }
            $lista[] = array('label' => $index1, 'type' => 'number');
        }

        $table['cols'] = $lista;
        for ($i = 1; $i < 6; $i++) {
            switch ($i) {
                case 1: $tipoExame = "ADM";
                    break;
                case 2: $tipoExame = "PER";
                    break;
                case 3: $tipoExame = "MUD";
                    break;
                case 4: $tipoExame = "RET";
                    break;
                case 5: $tipoExame = "DEM";
                    break;
            }
            $contador = 1;


            $temp = array();

            if ($i == 1) {
                $temp[] = array('v' => (string) "Admissional");
                for ($index = $anoBuscadoInicio; $index < $anoBuscadoFim; $index++) {
                    $resultado = $agenda->obterGraficoPeriodo($index, $tipoExame);
                    foreach ($resultado as $row) {
                        $temp[$contador] = array('v' => (int) $row['QNT']);
                    }
                    $contador++;
                }
            }
            if ($i == 2) {
                $temp[] = array('v' => (string) "Periódico");
                for ($index = $anoBuscadoInicio; $index < $anoBuscadoFim; $index++) {
                    $resultado = $agenda->obterGraficoPeriodo($index, $tipoExame);
                    foreach ($resultado as $row) {
                        $temp[$contador] = array('v' => (int) $row['QNT']);
                    }
                    $contador++;
                }
            }
            if ($i == 3) {
                $temp[] = array('v' => (string) "Mudança de Função");
                for ($index = $anoBuscadoInicio; $index < $anoBuscadoFim; $index++) {
                    $resultado = $agenda->obterGraficoPeriodo($index, $tipoExame);
                    foreach ($resultado as $row) {
                        $temp[$contador] = array('v' => (int) $row['QNT']);
                    }
                    $contador++;
                }
            }
            if ($i == 4) {
                $temp[] = array('v' => (string) "Retorno ao Trabalho");
                for ($index = $anoBuscadoInicio; $index < $anoBuscadoFim; $index++) {
                    $resultado = $agenda->obterGraficoPeriodo($index, $tipoExame);
                    foreach ($resultado as $row) {
                        $temp[$contador] = array('v' => (int) $row['QNT']);
                    }
                    $contador++;
                }
            }
            if ($i == 5) {
                $temp[] = array('v' => (string) "Demissional");
                for ($index = $anoBuscadoInicio; $index < $anoBuscadoFim; $index++) {
                    $resultado = $agenda->obterGraficoPeriodo($index, $tipoExame);
                    foreach ($resultado as $row) {
                        $temp[$contador] = array('v' => (int) $row['QNT']);
                    }
                    $contador++;
                }
            }

            $rows[] = array('c' => $temp);
        }
        $table['rows'] = $rows;
        $jsonTable = json_encode($table);
        echo $jsonTable;
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function gerarAction() {
        $atributos = $_SESSION;
        $funcionario = new Application_Model_Funcionario();
        $pessoa = new Application_Model_Pessoa();
        $cargo = new Application_Model_Cargo();
        $funcao = new Application_Model_Funcao();

        if ($this->getRequest()->isPost()) {
            $onde = "";
            $filtro = "";
            $post = $this->getRequest()->getPost();
            //var_dump($post); die;
            if ($post['funcionario_id'] != 0) {
                $onde .= " AND p.`pessoa_id` = {$post['funcionario_id']} ";
                $resultadoNome = $pessoa->listarPessoa($post['funcionario_id']);
                $filtro .= "Funcionario: {$resultadoNome[0]['pessoa_nome']} ";
            }

            if ($post['setor'] != "") {
                $onde .= " AND s.`setor_nome` = '{$post['setor']}' ";
                $filtro .= "Setor: {$post['setor']}";
            }

            if ($post['cargo_id'] != 0) {
                $onde .= " AND c.`cargo_id` = {$post['cargo_id']} ";
                $resultadoCargo = $cargo->obter($post['cargo_id']);
                $filtro .= " Cargo: {$resultadoCargo['cargo_nome']} ";
            }

            if ($post['funcao_id'] != 0) {
                $onde .= " AND fc.`funcao_id` = {$post['funcao_id']} ";
                $resultadoFuncao = $funcao->obter($post['funcao_id']);
                $filtro .= "Função: {$resultadoFuncao['funcao_nome']} ";
            }
            $onde .= " AND co.`contrato_id` = {$atributos['contrato_id']} ";
            $onde .= " AND e.`empresa_id` = {$atributos['empresa']['empresa_id']} ";


            $resultadoFuncionario = $funcionario->listarFuncionarioRelatorio($onde);

            //var_dump($filtro);die;
            $this->view->filtro = $filtro;
            $this->view->funcionarios = $resultadoFuncionario;
            $this->_helper->layout->disableLayout();
            $view = "/relatorio/funcionario/gerar.phtml";
        }
        $this->renderScript($view);
    }

    public function graficoRealizadosProgramadosAction() {
        $ano = ($_GET['dataAno'] != "") ? $_GET['dataAno'] : 2014;
        $exame = $_GET['fases'];
        $tipoExame = $exame;
        $anoBuscado = $ano;
        unset($ano);
        unset($exame);
        //var_dump($anoBuscado);die;
        $agenda = new Application_Model_Agenda();
        $tipoExameModel = new Application_Model_TipoExame();
        $resultadoExames = $tipoExameModel->obter($tipoExame);

        $table = array();
        $rows = array();
        $flag = true;

        $table['cols'] = array(
            array('label' => 'MEIO', 'type' => 'string'),
            array('label' => "PROGRAMADO", 'type' => "number"),
            array('label' => "REALIZADO", 'type' => 'number'),
        );
        for ($index = 1; $index < 13; $index++) {
            $resultado = $agenda->obterGraficoProgramadoRealizado($index, $anoBuscado, $tipoExame, 1);
            $resultado2 = $agenda->obterGraficoProgramadoRealizado($index, $anoBuscado, $tipoExame, 2);

            $resultados = array_merge($resultado, $resultado2);
            //var_dump($resultados);
            //var_dump($resultado); die;
            switch ((int) $index) {
                case 1: $mes = 'Jan';
                    break;
                case 2: $mes = 'Fev';
                    break;
                case 3: $mes = 'Mar';
                    break;
                case 4: $mes = 'Abr';
                    break;
                case 5: $mes = 'Maio';
                    break;
                case 6: $mes = 'Jun';
                    break;
                case 7: $mes = 'Jul';
                    break;
                case 8: $mes = 'Ago';
                    break;
                case 9: $mes = 'Set';
                    break;
                case 10: $mes = 'Out';
                    break;
                case 11: $mes = 'Nov';
                    break;
                case 12: $mes = 'Dez';
                    break;
            }
            $indexMes = $mes;
            $temp = array();

            if (isset($resultados[0])) {
                $temp[] = array('v' => (string) $indexMes);
                $temp[1] = array('v' => (int) (isset($resultados[0]['QNTP']) ? $resultados[0]['QNTP'] : 1));
                $temp[2] = array('v' => (int) (isset($resultados[1]['QNTR']) ? $resultados[1]['QNTR'] : 1));
            } else {
                $temp[] = array('v' => (string) $indexMes);
                $temp[] = array('v' => (int) 0);
                $temp[] = array('v' => (int) 0);
            }
            $rows[] = array('c' => $temp);
        }
        $table['rows'] = $rows;
        $jsonTable = json_encode($table);
        echo $jsonTable;
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function graficoExamesRealizadosPeriodoAction() {
        $anoInicio = ($_GET['dataInicio'] != "") ? $_GET['dataInicio'] : date("Y");
        $anoFim = ($_GET['dataFim'] != "") ? $_GET['dataFim'] : date("Y");
        $anoBuscadoInicio = $anoInicio;
        $anoBuscadoFim = $anoFim + 1;
        unset($anoInicio);
        unset($anoFim);
        $exame = $_GET['fases'];

        $agenda = new Application_Model_Agenda();
        $produto = $agenda->produtoID($exame);

        $table = array();
        $rows = array();
        $lista = array();
        $flag = true;


        $lista[] = array('label' => 'MEIO', 'type' => 'string');
        $lista[] = array('label' => $produto[0]['produto_nome'], 'type' => 'number');

        $table['cols'] = $lista;
        $cont = 1;
        for ($index1 = $anoBuscadoInicio; $index1 < $anoBuscadoFim; $index1++) {
            $temp = array();
            $temp[] = array('v' => (string) $index1);
            $resultado = $agenda->obterGraficoExamePeriodo($index1, $exame);
            $resultados = (isset($resultado[0]['QNT']) ? $resultado[0]['QNT'] : 0);
            $temp[$cont] = array('v' => (int) $resultados);
            $rows[] = array('c' => $temp);
            $cont++;
        }
        $table['rows'] = $rows;
        $jsonTable = json_encode($table);
        echo $jsonTable;
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function graficoExamesRealizadosAnualAction() {
        $ano = ($_GET['dataAno'] != "") ? $_GET['dataAno'] : 2014;
        $exame = $_GET['fases'];
        $tipoExame = $exame;
        $anoBuscado = $ano;
        unset($ano);
        unset($exame);

        $agenda = new Application_Model_Agenda();
        $produto = $agenda->produtoID($tipoExame);

        $table = array();
        $rows = array();
        $lista = array();
        $flag = true;


        $lista[] = array('label' => 'MEIO', 'type' => 'string');
        $lista[] = array('label' => $produto[0]['produto_nome'], 'type' => 'number');
        $table['cols'] = $lista;
        for ($index = 1; $index < 13; $index++) {

            $resultado = $agenda->obterGraficoExamePeriodo($anoBuscado, $tipoExame, $index, 2);

            switch ((int) $index) {
                case 1: $mes = 'Jan';
                    break;
                case 2: $mes = 'Fev';
                    break;
                case 3: $mes = 'Mar';
                    break;
                case 4: $mes = 'Abr';
                    break;
                case 5: $mes = 'Maio';
                    break;
                case 6: $mes = 'Jun';
                    break;
                case 7: $mes = 'Jul';
                    break;
                case 8: $mes = 'Ago';
                    break;
                case 9: $mes = 'Set';
                    break;
                case 10: $mes = 'Out';
                    break;
                case 11: $mes = 'Nov';
                    break;
                case 12: $mes = 'Dez';
                    break;
            }
            $indexMes = $mes;
            $temp = array();

            if (isset($resultado[0])) {
                $temp[] = array('v' => (string) $indexMes . "1");
                $temp[] = array('v' => (int) $resultado[0]['QNT']);
            } else {
                $temp[] = array('v' => (string) $indexMes . "2");
                $temp[] = array('v' => (int) 0);
                $temp[] = array('v' => (int) 0);
            }
            $rows[] = array('c' => $temp);
        }
        $table['rows'] = $rows;
        $jsonTable = json_encode($table);
        echo $jsonTable;
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function biometriaAction() {
        $erros = array();
        $filtro = "1";
        $contrato = $_SESSION['contrato_id'];
        if ($this->getRequest()->isPost()) :
            $data1 = Util::dataBD($this->_getParam('data1'));
            $data2 = Util::dataBD($this->_getParam('data2'));
            $modo = (int) $this->_getParam('modo');
            $empresa = (int) $this->_getParam('empresa');

            if ($data1 == "" || $data2 == "") :
                $erros[] = "Informe um período válido.";
            endif;
            if (empty($erros)) :

                $filtro .= " AND a.agenda_data_clinico >= '$data1 00:00:00'";
                $filtro .= " AND a.agenda_data_clinico <= '$data2 23:59:59'";
                $filtro .= ($empresa == 0) ? "" : " AND e.empresa_id = '$empresa'";
                $filtro .= " AND a.fk_contrato_id = '$contrato'";
                $relatorio = new Application_Model_Agenda();
                $this->view->resultado = $relatorio->relacaodebiometria($filtro);
                $Empresas = new Application_Model_Empresa();
                $emp = $Empresas->obter($empresa);
                $this->view->filtro = array(
                    'PERÍODO', Util::dataBR($data1) . ' à ' . Util::dataBR($data2),
                    'EMPRESA', $empresa == 0 ? 'TODAS' : $emp['empresa_fantasia']);
                $this->view->modo = $modo;
                $this->renderScript('/documento-operacional/relatorio-biometria/excel.phtml');
                if ($modo == 1) :
                    $this->_helper->layout->disableLayout();
                endif;
            else:
                $this->view->erros = $erros;
                $this->renderScript('/documento-operacional/relatorio-biometria/index.phtml');
            endif;
        else:
            $Empresas = new Application_Model_Empresa();
            $emp = $Empresas->obterEmpresaPeloContrato($contrato);
            $this->view->empresa = $emp;
            $this->view->erros = $erros;
            $this->renderScript('/documento-operacional/relatorio-biometria/index.phtml');
        endif;
    }

    public function analiticoPcmsoActionFuncaoDesativada() {

        $ano = (int) $this->_getParam('ano', 0);

        $anovigente = date('Y');
        $modelo_pa = new Application_Model_ProdutoAgenda();
        $listAnos = $modelo_pa->obterListAnos($anovigente);        

        if ($ano > 0) {

            $contratoId       = $_SESSION['contrato_id'];

            $modelo_contrato  = new Application_Model_Contrato();
            $dadosmedico      = $modelo_contrato->obterMedicoContrato($contratoId);

            $nexameclinico    = $modelo_pa->obterExamesClinicos($ano, $contratoId);
            $nexamecomp       = $modelo_pa->obterTipoExamesComplementares($ano, $contratoId);
            $nexamecompalter  = $modelo_pa->obterResultadosAnormaisComplementares($ano, $contratoId);

            $modelo_cat       = new Application_Model_EsocialCat();
            $catdoencas       = $modelo_cat->obterCatDoencas($ano, $contratoId);
            $cattipos         = $modelo_cat->obterCatTipo($ano, $contratoId);

            $ant_examecli     = $modelo_pa->obterExamesClinicos($ano - 1, $contratoId);
            $total_examecli   = 0;
            if ($ant_examecli['qtd_exames'] != NULL) {
                $total_examecli   = $ant_examecli['qtd_exames'];
            }
            $anoanterior['CLINICO']      = $total_examecli;

            $ant_compcli      = $modelo_pa->obterTipoExamesComplementares($ano - 1, $contratoId);
            $total_compcli    = 0;
            foreach ($ant_compcli as $key => $item) {
                $total_compcli += $item['qtd_exames'];
            }
            $anoanterior['COMPLEMENTAR'] = $total_compcli;

            $ant_alterados    = $modelo_pa->obterResultadosAnormaisComplementares($ano - 1, $contratoId);
            $sum              = 0;
            $talterado        = 0;
            $total_alterados  = 0;
            foreach ($ant_alterados as $key => $item) {
                $tnormal = array_count_values(array_column($ant_alterados, 'alterado'))[$item['alterado']];
                $talterado = array_count_values(array_column($ant_alterados, 'alterado'))[$item['alterado'] == 0];
                $sum = $tnormal + $talterado;
            }
            $anoanterior['ALTERADOS_A']  = $talterado;
            $resultperc = 0;
            if (!empty($talterado) AND !empty($sum)) {
                $resultperc = number_format($talterado * 100 / $sum, 2, ',', '.');
            }
            $anoanterior['ALTERADOS_%']  = $resultperc;

            $ant_doencas      = $modelo_cat->obterCatDoencas($ano - 1, $contratoId);
            $total_doenca     = 0;
            foreach ($ant_doencas as $key => $item) {
                if ($ant_doencas[0]['qts_cat'] != 0) {
                    $total_doenca = count($ant_doencas);
                }
            }
            $anoanterior['DOENÇAS'] = $total_doenca;


            $ant_cat          = $modelo_cat->obterCatTipo($ano - 1, $contratoId);
            $anoanterior['CAT']          = count($ant_cat);
            #ano anterior 
            
            $this->view->ano             = $ano;
            $this->view->anoanterior     = $anoanterior;
            $this->view->dadosmedico     = $dadosmedico;
            $this->view->nexameclinico   = $nexameclinico;
            $this->view->nexamecomp      = $nexamecomp;
            $this->view->nexamecompalter = $nexamecompalter;
            $this->view->catdoencas      = $catdoencas;
            $this->view->cattipos        = $cattipos;
            #$this->renderScript('/documento-operacional/analitico-pcmso/pdf.phtml');
            $this->renderScript('/documento-operacional/analitico-pcmso/pdf2.phtml');
        }else{
            $this->view->listanos = $listAnos;
            $this->renderScript('/documento-operacional/analitico-pcmso/index.phtml');
        }

    }

    // analitico-pcmso-V2
    public function analiticoPcmsoAction()
    {
        $ano        = (int) $this->_getParam('ano', 0);
        $anovigente = date('Y');
        $ProdutoAgendaModel  = new Application_Model_ProdutoAgenda();
        $listAnos   = $ProdutoAgendaModel->obterListAnos($anovigente);
        

        if ($ano > 0) {
            
            $ContratoModel       = new Application_Model_Contrato();
            $CatModel            = new Application_Model_EsocialCat();
            $contratoId          = $_SESSION['contrato_id'];
            $empresaId           = $_SESSION['empresa']['empresa_id'];
            
            $dadosmedico      = $ContratoModel->obterMedicoContrato($contratoId);
            $nexameclinico    = $ProdutoAgendaModel->obterExamesClinicosV2($ano, $contratoId, $empresaId);
            $nexamecomp       = $ProdutoAgendaModel->obterTipoExamesComplementares($ano, $contratoId);
            $nexamecompalter  = $ProdutoAgendaModel->obterResultadosAnormaisComplementares($ano, $contratoId);
            $catdoencas       = $CatModel->obterCatDoencas($ano, $contratoId);
            $cattipos         = $CatModel->obterCatTipo($ano, $contratoId);
            $ant_examecli     = $ProdutoAgendaModel->obterExamesClinicos($ano - 1, $contratoId);
            
            $tiposExamesComplementares = $ProdutoAgendaModel->obterTipoExamesComplementaresV2($ano, $contratoId, $empresaId);
            $resultadosAnormaisComplementares = $ProdutoAgendaModel->obterResultadosAnormaisComplementaresV2($ano, $contratoId, $empresaId);
            
            $valorObterExames = $ProdutoAgendaModel->obterExames($ano - 1, $contratoId, $empresaId);
            $valorExamesComplementares = $ProdutoAgendaModel->obterTipoExamesComplementaresV2($ano - 1, $contratoId, $empresaId);
            $valorExamesAlterados = $ProdutoAgendaModel->obterResultadosAnormaisComplementaresV2($ano - 1, $contratoId, $empresaId);

            $totalExamesClinicos = 0;
            foreach($valorObterExames as $cval) {
                $totalExamesClinicos += $cval['qtd_exames'];
            }
            $anoanterior2['CLINICO'] = $totalExamesClinicos;

            $totalExamesComplementares = 0;
            foreach($valorExamesComplementares as $cval) {
                $totalExamesComplementares += $cval['qtd_exames'];
            }
            $anoanterior2['COMPLEMENTAR'] = $totalExamesComplementares;

            $sum              = 0;
            $talterado        = 0;
            $total_alterados  = 0;
            foreach ($valorExamesAlterados as $key => $cval) {
                $tnormal = array_count_values(array_column($valorExamesAlterados, 'alterado'))[$cval['alterado']];
                $talterado = count(array_filter($valorExamesAlterados, function($element) {
                    return $element['alterado'] == 0;
                }));  
                $sum = $tnormal + $talterado;
            }
            $anoanterior2['ALTERADOS_A']  = $talterado;

            $resultperc = 0;
            if (!empty($talterado) AND !empty($sum)) {
                $resultperc = number_format($talterado * 100 / $sum, 2, ',', '.');
            }
            $anoanterior2['ALTERADOS_%']  = $resultperc;

            $ant_doencas      = $CatModel->obterCatDoencas($ano - 1, $contratoId);
            $total_doenca     = 0;
            foreach ($ant_doencas as $key => $item) {
                if ($ant_doencas[0]['qts_cat'] != 0) {
                    $total_doenca = count($ant_doencas);
                }
            }
            $anoanterior2['DOENÇAS'] = $total_doenca;


            $ant_cat          = $CatModel->obterCatTipo($ano - 1, $contratoId);
            $anoanterior2['CAT']          = count($ant_cat);
            #ano anterior 
            
            $this->view->ano             = $ano;
            $this->view->anoanterior     = $anoanterior2;
            $this->view->dadosmedico     = $dadosmedico;
            $this->view->nexameclinico   = $nexameclinico;
            $this->view->nexamecomp      = $nexamecomp;
            $this->view->nexamecompalter = $nexamecompalter;
            $this->view->catdoencas      = $catdoencas;
            $this->view->cattipos        = $cattipos;
            $this->view->tiposExamesComplementares = $tiposExamesComplementares;
            $this->view->resultadosAnormaisComplementares = $resultadosAnormaisComplementares;
            #$this->renderScript('/documento-operacional/analitico-pcmso/pdf.phtml');
            $this->renderScript('/documento-operacional/analitico-pcmso/pdf3.phtml');
            $this->_helper->layout->disableLayout();
        }else{
            $this->view->listanos = $listAnos;
            $this->renderScript('/documento-operacional/analitico-pcmso/index.phtml');
        }
    }

}
