<?php

class AjaxController extends Controller
{

    public function eventoEnviado() {
        try {

            $id = (int) $this->_getParam('id', 0);
            $tipo = $this->_getParam('tipo');

            $EsocialDetalhe = new Application_Model_EsocialDetalheEnvio();
            $dadosLotes = $EsocialDetalhe->dadosEventosEnviado($id, $tipo);

            $this->json($dadosLotes);
        } catch (Exception $ex) {
            $this->json(['mensagem' => $ex->getMessage()]);
        }
    }

    public function obtercepApi() {
        $cep = $this->_getParam('cep', 0);
        
        $excecao = null;
        try {

            $cep = preg_replace("/[^0-9]/", "", $cep);

            $url = "http://viacep.com.br/ws/$cep/xml/";
            $xml = simplexml_load_file($url);
            
        } catch (Exception $exc) {
            $excecao = $exc->getMessage();
        }
        $imprimirRetorno = array(
            'dados' => $xml,
            'excecao' => $excecao,
        );
        echo $this->json($imprimirRetorno);
    }

    public function aceitarTermosDeUtilizacaoDoSistema() {
        try {

            $UsuarioPortal = new Application_Model_UsuarioPortal();
            $n = $UsuarioPortal->update(['usuario_portal_aceitou_termos' => date("Y-m-d H:i:s")], ['usuario_portal_id = ?' => $_SESSION['usuario_portal_id']]);

            if ($n > 0) {
                $this->json(1);
            } else {
                $this->json(0);
            }
        } catch (Exception $ex) {
            $this->json(['mensagem' => $ex->getMessage()]);
        }
    }

    public function aceitarTermosEsocial() {
        try {
            $dados = $this->getRequest()->getPost();
            //util::dump($dados);exit();
            $id = $dados['id'];
            $nome = $dados['nome'];
            $cpf = preg_replace('/\D/', '', $dados['cpf']);

            $UsuarioPortal = new Application_Model_UsuarioPortal();
            if ($id > 0) {
                $n = $UsuarioPortal->update(['usuario_portal_esocial_aceitou_termos' => date("Y-m-d H:i:s"),
                                             'usuario_portal_esocial_autoriza' => $id,
                                             'usuario_portal_esocial_nome' => $nome,
                                             'usuario_portal_esocial_cpf' => $cpf,
                                            ], ['usuario_portal_id = ?' => $_SESSION['usuario_portal_id']]);
            }            

            if ($n > 0) {
                $this->json(1);
            } else {
                $this->json(0);
            }
        } catch (Exception $ex) {
            $this->json(['mensagem' => $ex->getMessage()]);
        }
    }

    public function obterFuncionariosPelaEmpresaEContrato() {
        $empresa = $this->_getParam('empresa');
        $unidade = $this->_getParam('contrato');
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $prepare = $adapter->prepare("SELECT * FROM funcionario JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id WHERE funcionario.fk_empresa_id = ? AND funcionario.fk_contrato_id = ? AND funcionario.funcionario_status = 0 ORDER BY pessoa.pessoa_nome ASC");
        $prepare->execute(array($empresa, $unidade));
        $itens = $prepare->fetchAll();
        echo json_encode($itens);
    }

    public function obterSetoresPelaEmpresa() {
        $empresa = $this->_getParam('empresa');
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $prepare = $adapter->prepare("SELECT *, CONCAT(funcao_nome, ' - ', cargo_nome, ' - ', setor_nome) AS alocacao FROM alocacao
            JOIN funcao ON funcao_id = fk_funcao_id
            JOIN cargo ON cargo_id = fk_cargo_id
            JOIN setor ON setor_id = fk_setor_id
            WHERE fk_empresa_id = ? ORDER BY setor_nome ASC");
        $prepare->execute(array($empresa));
        $itens = $prepare->fetchAll();
        echo json_encode($itens);
    }

    # ATESTADO

    public function obterAtestadoDoFuncionario()
    {
        $this->view->mensagem = "";
        $atestado_id = (int) $this->_getParam('atestado');
        $Atestado = new Application_Model_Atestado();
        $atestado = $Atestado->obterPeloId($atestado_id);
        $this->json($atestado);
    }

    public function obterAtestadosDoFuncionario()
    {
        $this->view->mensagem = "";
        $Atestado = new Application_Model_Atestado();
        $funcionario_id = (int) $this->_getParam('funcionario');
        $this->view->atestados = $Atestado->obterPeloFuncionario($funcionario_id);
        $this->renderScript('ajax/funcionario/lista-atestados.phtml');
        $this->_desabilitarCarregamentoDoTemplate();
    }

    public function adicionarAtestadoParaFuncionario()
    {
        $dados = $this->getRequest()->getPost();
        $this->view->mensagem = "";
        try {
            $Atestado = new Application_Model_Atestado();
            $data = array(
                'atestado_cid' => $dados['cid'],
                'fk_funcionario_id' => (int) $dados['funcionario'],
                'atestado_data_inicial' => strlen($dados['data_inicial']) ? Util::dataBD($dados['data_inicial']) : null,
                'atestado_data_termino' => strlen($dados['data_termino']) ? Util::dataBD($dados['data_termino']) : null);
            if ((int) $dados['atestado'] == 0) {
                $existe = $Atestado->fetchRow(array('atestado_cid = ?' => $dados['cid'], 'fk_funcionario_id = ?' => $dados['funcionario'], 'atestado_status = ?' => 0));
                if ($existe) {
                    $this->view->mensagem = "Este Atestado já está incluso.";
                } else {
                    $Atestado->insert($data);
                }
            } else {
                $Atestado->update($data, array('atestado_id = ?' => (int) $dados['atestado']));
            }
        } catch (Exception $ex) {
            $this->view->mensagem = "Não foi possível salvar o Atestado.<pre style='display:none;'>{$ex->getMessage()}</pre>";
        }
        $this->view->atestados = $Atestado->obterPeloFuncionario((int) $dados['funcionario']);
        $this->renderScript('ajax/funcionario/lista-atestados.phtml');
        $this->_desabilitarCarregamentoDoTemplate();
    }

    # FIM ATESTADO

    public function obterEpiDoFuncionario()
    {
        $this->view->mensagem = "";
        $FuncionarioEpi = new Application_Model_FuncionarioEpi();
        $funcionarioepi = $FuncionarioEpi->obterPeloId((int) $this->_getParam('funcionario_epi'));
        $this->json($funcionarioepi);
    }

    public function obterEpisDoFuncionario()
    {
        $this->view->mensagem = "";
        $FuncionarioEpi = new Application_Model_FuncionarioEpi();
        $this->view->epis = $FuncionarioEpi->obterPeloFuncionario((int) $this->_getParam('funcionario'));
        $this->renderScript('ajax/funcionario/lista-epi.phtml');
        $this->_desabilitarCarregamentoDoTemplate();
    }

    public function adicionarEpiParaFuncionario()
    {
        $dados = $this->getRequest()->getPost();
        $this->view->mensagem = "";
        try {
            $FuncionarioEpi = new Application_Model_FuncionarioEpi();
            $data = array(
                'funcionario_epi_ca' => $dados['ca'],
                'funcionario_epi_nome' => $dados['nome'],
                'fk_funcionario_id' => (int) $dados['funcionario'],
                'funcionario_epi_data_entrega' => strlen($dados['data_entrega']) ? Util::dataBD($dados['data_entrega']) : null,
                'funcionario_epi_data_devolucao' => strlen($dados['data_devolucao']) ? Util::dataBD($dados['data_devolucao']) : null,
                'funcionario_epi_responsavel_entrega' => strlen($dados['responsavel_entrega']) ? $dados['responsavel_entrega'] : null,
                'funcionario_epi_data_vencimento' => strlen($dados['data_vencimento']) ? Util::dataBD($dados['data_vencimento']) : null,
                'funcionario_epi_responsavel_devolucao' => strlen($dados['responsavel_devolucao']) ? $dados['responsavel_devolucao'] : null);
            if ((int) $dados['funcionario_epi'] == 0) {
                $existe = $FuncionarioEpi->fetchRow(array('funcionario_epi_nome = ?' => $dados['nome'], 'fk_funcionario_id = ?' => $dados['funcionario'], 'funcionario_epi_status = ?' => 0));
                if ($existe) {
                    $this->view->mensagem = "Este EPI já está incluso.";
                } else {
                    $FuncionarioEpi->insert($data);
                }
            } else {
                $FuncionarioEpi->update($data, array('funcionario_epi_id = ?' => (int) $dados['funcionario_epi']));
            }
        } catch (Exception $ex) {
            $this->view->mensagem = "Não foi possível salvar o EPI.<pre style='display:none;'>{$ex->getMessage()}</pre>";
        }
        $this->view->epis = $FuncionarioEpi->obterPeloFuncionario((int) $dados['funcionario']);
        $this->renderScript('ajax/funcionario/lista-epi.phtml');
        $this->_desabilitarCarregamentoDoTemplate();
    }

    public function obterColecaoAnexoFatura()
    {
        $resposta = array('Mensagem' => null, 'Resultado' => null, 'Metodo' => 'obterColecaoItensFaturados', 'status' => 'SUCESSO');
        header("Content-type: application/json; charset=utf-8");
        $faturaId = (int) $this->_getParam('fatura_id', 0);
        if ($faturaId > 0) {
            try {
                $Cnx = Zend_Db_Table::getDefaultAdapter();
                $Cmd = $Cnx->select()
                    ->from(array('af' => 'anexo_fatura'))
                    ->join(array('au' => 'arquivo_upload'), 'af.fk_arquivo_upload_id = au.arquivo_upload_id')
                    ->where('af.anexo_fatura_status = ?', 0)
                    ->where('af.fk_fatura_id = ?', $faturaId)
                    ->order('af.anexo_fatura_descricao ASC');
                $ResultadoComBLOB = $Cmd->query()->fetchAll();

                $resposta['Resultado'] = array();
                foreach ($ResultadoComBLOB as $item) {
                    $item['arquivo_upload_binario'] = null;
                    $resposta['Resultado'][] = $item;
                }
            } catch (Exception $exc) {
                $resposta['Mensagem'] = 'Aconteceu um erro ao processar o comando. Detalhe: ' . $exc->getMessage();
                $resposta['status'] = 'ERRO';
            }
        }

        echo json_encode($resposta);
    }

    /**
     * COPIADO DO SISS
     * Exclui um anexo de um procedimento do prontuário.<br/>
     * POST: /ajax/json/servico/excluir-anexo-procedimento-prontuario.<br/>
     * PARAMS: (int) anx_proc_id
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     */
    public function excluirAnexoProcedimentoProntuario()
    {
        $id = str_replace(array('.', '/', '*', '-', '_', '=', '+', ' '), '', (int) $this->_getParam('anx_proc_id', 0));
        $resposta = array('Mensagem' => null, 'Resultado' => null, 'Metodo' => 'excluirAnexoProcedimentoProntuario', 'anx_proc_id' => $id);
        try {
            $P = new Application_Model_AnexoProcedimento();
            $up = $P->update(array('anx_proc_status' => 2), array('anx_proc_id = ?' => $id));
            $resposta['Resultado']['Excluido'] = false;
            if ($up > 0) {
                $resposta['Resultado']['Excluido'] = true;
                #self::$_habilitarRegistrarLog = true;
                #$this->_log->logEvento = 'excluir';
                #$this->_log->logTabelaNome = 'anx_proc';
                #$this->_log->logTabelaColunaNome = 'anx_proc_id';
                #$this->_log->logTabelaColunaValor = $id;
                #$this->_log->logDetalhe = 'Tentativa de excluir um anexo de procedimento de um prontuário';
            }
        } catch (Exception $exc) {
            $resposta['Mensagem'] = $exc->getMessage();
        }
        header("Content-type: application/json; charset=utf-8");
        echo json_encode($resposta);
    }

    /**
     * Imprimi uma coleção de resultados de consulta nos anexos pelo id
     * do procedimento.<br/>
     * GET: /ajax/json/servico/resgatar-anexo-pelo-procedimento.<br/>
     * PARAMS: (int) procedimento_id
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     */
    public function resgatarAnexoPeloProcedimento()
    {
        $id = str_replace(array('.', '/', '*', '-', '_', '=', '+', ' '), '', (int) $this->_getParam('procedimento_id', 0));
        $resposta = array('Mensagem' => null, 'Resultado' => null, 'Metodo' => 'resgatarAnexoPeloProcedimento', 'procedimento_id' => $id);
        try {
            $P = new Application_Model_AnexoProcedimento();
            $Rst = $P->fetchAll(array('fk_procedimento_id = ?' => $id, 'anx_proc_status = ?' => 0));
            if ($Rst->count() > 0) {
                $resposta['Resultado'] = $Rst->toArray();
            }
        } catch (Exception $exc) {
            $resposta['Mensagem'] = $exc->getMessage();
        }
        header("Content-type: application/json; charset=utf-8");
        echo json_encode($resposta);
    }

    public function excluirAction()
    {
        $tabela = $this->_getParam('registro');
        $id = $this->_getParam('id');

        $feedback = array('erro' => 1, 'msg' => 'Não foi possível excluir registro');
        switch ($tabela) {
            case 'produto_contratado':
                $produtocontratado = new Application_Model_ProdutoContratado();
                $excluiu = $produtocontratado->excluirProdutoContratado($id);
                if ($excluiu['erro'] == 0) {
                    $feedback = array('erro' => 0, 'msg' => $excluiu['mensagem']);
                } else {
                    $feedback = array('erro' => 1, 'msg' => $excluiu['mensagem']);
                }
                break;
            case 'treinamento_agendado':
                $treinamentoModel = new Application_Model_Treinamento();
                $status = $treinamentoModel->obterAgendadoAprovados($id);

                if ($status == false) {
                    $excluiu = Util::excluir($tabela, $id);
                    if ($excluiu) {
                        $feedback = array('erro' => 0, 'msg' => 'Registro excluído com sucesso. <meta http-equiv="refresh" content = "3"/>');
                        ##############################################################
                        # Registrando no Log
                        ##############################################################
                        $log = array(
                            'log_evento' => 'excluir',
                            'log_tabela_nome' => 'treinamento_agendado',
                            'log_tabela_coluna_nome' => 'treinamento_agendado_id',
                            'log_tabela_coluna_valor' => "{$id}",
                            'log_usuario_codigo' => "{$_SESSION['usuario_portal_id']}",
                            'log_usuario_login' => "{$_SESSION['usuario_portal_login']}",
                            'log_usuario_nome' => "{$_SESSION['contrato_responsavel_nome']}",
                            'log_endereco_captura' => "{$_SERVER['HTTP_REFERER']}",
                            'log_ip' => "{$_SERVER['SERVER_ADDR']}",
                            'log_data_hora' => date('Y-m-d H:i:s'),
                            'log_codigo_sessao_acesso' => "{$_SERVER['HTTP_COOKIE']}",
                            'log_detalhe' => 'Tentativa de exclusão da agenda via portal do cliente',
                            'log_observacao' => "",
                            'log_colecao_parametros_enviados_via_post' => "",
                            'log_colecao_parametros_enviados_via_get' => ""
                        );
                        try {
                            $ModeloLog = new Application_Model_Log();
                            $ModeloLog->insert($log);
                        } catch (Exception $exlog) {
                            $erro = 1;
                            $mensagem = "Erro ao executar comando no banco de dados. Log: " . $exlog->getMessage();
                        }                    
                        ##############################################################
                    }
                }else{
                    $feedback = array('erro' => 1, 'msg' => 'Não pode excluir o registros já avaliados.<meta http-equiv="refresh" content = "3"/>');
                }
                break;
            case 'agenda':
                $excluiu = Util::excluir($tabela, $id);
                if ($excluiu) {
                    $feedback = array('erro' => 0, 'msg' => 'Agenda excluída com sucesso.');
                    ##############################################################
                    # Registrando no Log
                    ##############################################################
                    $log = array(
                        'log_evento' => 'excluir',
                        'log_tabela_nome' => 'agenda',
                        'log_tabela_coluna_nome' => 'agenda_id',
                        'log_tabela_coluna_valor' => "{$id}",
                        'log_usuario_codigo' => "{$_SESSION['usuario_portal_id']}",
                        'log_usuario_login' => "{$_SESSION['usuario_portal_login']}",
                        'log_usuario_nome' => "{$_SESSION['contrato_responsavel_nome']}",
                        'log_endereco_captura' => "{$_SERVER['HTTP_REFERER']}",
                        'log_ip' => "{$_SERVER['SERVER_ADDR']}",
                        'log_data_hora' => date('Y-m-d H:i:s'),
                        'log_codigo_sessao_acesso' => "{$_SERVER['HTTP_COOKIE']}",
                        'log_detalhe' => 'Tentativa de exclusão da agenda via portal do cliente',
                        'log_observacao' => "",
                        'log_colecao_parametros_enviados_via_post' => "",
                        'log_colecao_parametros_enviados_via_get' => ""
                    );
                    try {
                        $ModeloLog = new Application_Model_Log();
                        $ModeloLog->insert($log);
                    } catch (Exception $exlog) {
                        $erro = 1;
                        $mensagem = "Erro ao executar comando no banco de dados. Log: " . $exlog->getMessage();
                    }                    
                    ##############################################################
                } 
                break;
            default:
                $excluiu = Util::excluir($tabela, $id);
                if ($excluiu) {
                    $feedback = array('erro' => 0, 'msg' => 'Registro excluído com sucesso <meta http-equiv="refresh" content = "2"/>');
                }
        }
        $this->view->feedback = $feedback;
        $this->_helper->layout->disableLayout();
        $this->renderScript('feedback.phtml');
    }

    public function excluirItemPcmsoProdutoPorId()
    {
        //excluirItemPcmsoProdutoPorId
        $itemPcmsoProdutoId = $this->_getParam('item_pcmso_produto_id', 0);
        $excluido = 0;
        $strMensagem = 'O registro nao foi excluido!';
        try {
            $itemProduto = new Application_Model_ItemPcmsoProduto();
            $condicao = array(
                'item_pcmso_produto_id = ?' => $itemPcmsoProdutoId,
            );
            $excluido = $itemProduto->delete($condicao);
            if ($excluido > 0) {
                $strMensagem = "Exame excluido com sucesso";
            }
        } catch (Exception $ex) {
            $strMensagem = $ex->getMessage();
        }
        $resultado = array(
            'quantidadeItemExcluido' => $excluido,
            'strMensagem' => $strMensagem,
        );
        echo Zend_Json::encode($resultado);
        $this->_desabilitarTodoCarregamentoDeVisualizacao();
    }

    public function deletarAction()
    {
        $feedback = array(
            'erro' => 1,
            'msg' => 'Não foi possível excluir registro');

        $excluiu = Util::excluir($this->_getParam('registro'), $this->_getParam('id'));

        //Após ter feito a Exclusão da Tabela Cargo, faz o UPDATE na Tabela Função, setando a funcao_status para 2 (Inativo).
        $id = $this->_getParam('id', 0);
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $sql = "UPDATE funcao SET funcao_status = 2 WHERE funcao_id = ?";
        $prep = $adapter->prepare($sql);
        $prep->execute(array($id));
        //Fim.

        if ($excluiu) {
            $feedback['erro'] = 0;
            $feedback['msg'] = 'Registro excluído com sucesso';
        }
        $this->view->feedback = $feedback;
        $this->_helper->layout->disableLayout();
        $this->renderScript('feedback.phtml');
    }

    public function jsonAction()
    {
        $_servico = $this->_getParam("servico");
        $partes = explode("-", $_servico);
        $servico = "";
        foreach ($partes as $parte) {
            $servico = $servico . ucfirst(strtolower($parte));
        }

        call_user_func(array($this, $servico));
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
    }

    public function htmlAction()
    {
        $_servico = $this->_getParam("servico");
        $partes = explode("-", $_servico);
        $servico = "";
        foreach ($partes as $parte) {
            $servico = $servico . ucfirst(strtolower($parte));
        }

        call_user_func(array($this, $servico));
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
    }

    public function imprimirFormularioUsuarioEspecialista()
    {
        // Resgata dados da pessoa;
        $pessoa = new Application_Model_Pessoa();
        $pessoaId = (int) $this->_getParam('pessoaid', 0);
        $resultadoPessoa = $pessoa->fetchRow("pessoa_id = '{$pessoaId}'");
        if ($resultadoPessoa !== null) {
            $resultadoPessoa = $resultadoPessoa->toArray();
        } else {
            $resultadoPessoa = array();
        }
        $this->view->pessoa = $resultadoPessoa;
        // Resgata as especialidade para montar o modal
        // de inclusão de especialista
        $especialidades = array();
        $e = new Application_Model_Especialidade();
        $resultadoEspecialidade = $e->obterTodos();
        $especialidades = (count($resultadoEspecialidade) > 0) ? $resultadoEspecialidade : array();
        $this->view->especialidades = $especialidades;

        $pessoaEspecialista = new Application_Model_PessoaEspecialidade();
        $filtro = "pessoa_especialidade.fk_pessoa_id = '{$pessoaId}' ";
        $resultadoEspecialista = $pessoaEspecialista->buscaCompletaUsandoClausula($filtro);
        if (count($resultadoEspecialista) > 0) {
            $resultadoEspecialista = $resultadoEspecialista[0];
        } else {
            $p = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('pessoa');
            $pe = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('pessoa_especialidade');
            $e = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('especialidade');
            $resultadoEspecialista = array_merge($p, $pe, $e);
        }
        $this->view->especialista = $resultadoEspecialista;

        $this->renderScript('/ajax/usuario/imprimir-formulario-usuario-especialista.phtml');
    }

    public function salvarUsuarioEspecialista()
    {
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();
            $classe = 'alert alert-success';
            $pessoa_especialidade_id = (int) $this->_getParam('pessoa_especialidade_id', 0);
            unset($post['pessoa_especialidade_id']);
            try {
                $p = new Application_Model_PessoaEspecialidade();
                if ($pessoa_especialidade_id == 0) {
                    $p->insert($post);
                } else {
                    $p->update($post, array('pessoa_especialidade_id = ?' => $pessoa_especialidade_id));
                }
                $resposta = "<strong>Sucesso!</strong> Registro salvo";
            } catch (Exception $e) {
                $classe = 'alert alert-error';
                $resposta = "<strong>Erro</strong> {$e->getMessage()}";
            }
            $html = '<div class="' . $classe . '">
                        <button type="button" class="close" data-dismiss="alert">×</button>
                         ' . $resposta . '
                      </div>';
            echo $html;
        }
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function listarProdutosPelaCategoria()
    {
        $categoriadoproduto_id = (int) $_POST["categoria"];
        $model_produto = new Application_Model_Produto();
        $json = $model_produto->obterTodosNaCategoria($categoriadoproduto_id);
        echo $this->json($json);
    }

    public function listarEmpresaAtivaPeloId()
    {
        $idEmpresa = (int) $this->_getParam('id', 0);
        $clausula = "empresa.empresa_id = {$idEmpresa} AND empresa.empresa_status = 0";
        $json = array();
        try {
            $e = new Application_Model_Empresa();
            $resultado = $e->buscarCompletoUsandoClausula($clausula, "empresa_id", '1');
            $json = (is_array($resultado) && count($resultado) > 0) ? $resultado[0] : array();
        } catch (Exception $e) {

        }
        echo $this->json($json);
    }

    public function obterPessoaPeloCpf()
    {
        $cpf = $this->_getParam("cpf");
        $model_pessoa = new Application_Model_Pessoa();
        $dados = $model_pessoa->obterPeloCpf($cpf);
        echo $dados ? $this->json($dados) : $this->json(Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('pessoa'));
    }

    public function ajaxObterPessoaPeloCpf()
    {
        $cpf = str_replace(array('.', '-', '-', '_', '*', '&'), null, $this->_getParam("cpf"));
        $model_pessoa = new Application_Model_Pessoa();
        $dados = $model_pessoa->ajaxObterPeloCpf($cpf);
        echo $dados ? $this->json($dados) : $this->json(Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('pessoa'));
    }

    public function obterContratosPelaEmpresa()
    {
        $p = $this->_getParam("empresa");
        $model_contrato = new Application_Model_Contrato();
        $dados = $model_contrato->obterPelaEmpresa($p);
        echo $this->json($dados);
    }

    public function obterRiscosPelaClasse()
    {
        $p = $this->_getParam("classe");
        $model_risco = new Application_Model_Risco();
        $dados = $model_risco->obterPelaClasse($p);
        echo $this->json($dados);
    }

    public function obterAlocacaoPeloId()
    {
        $alocacaoId = $this->_getParam('alocacao_id', 0);
        $itemResultadoAlocacao = array();
        $excecao = null;
        $quantidadeItemAlocado = 0;
        try {
            $funcionario = new Application_Model_Funcionario();
            $filtroAlocacao = "alocacao.alocacao_id = {$alocacaoId}";
            $resultadoAlocacao = $funcionario->obterPeloFiltro($filtroAlocacao);
            if (is_array($resultadoAlocacao) && count($resultadoAlocacao) > 0) {
                $itemResultadoAlocacao = $resultadoAlocacao[0];
                $quantidadeItemAlocado = 1;
            }
        } catch (Exception $exc) {
            $excecao = $exc->getMessage();
        }
        $imprimirRetorno = array(
            'alocacao' => $itemResultadoAlocacao,
            'excecao' => $excecao,
            'quantidadeItemAlocado' => $quantidadeItemAlocado,
        );
        echo $this->json($imprimirRetorno);
    }

    /* public function salvarRiscoEmPcmsoObtendoTabela() {
    $pcmso_id = $this->_getParam("pcmso");
    $risco_id = $this->_getParam("risco");
    $exposicao_id = $this->_getParam("exposicao");
    try {
    $dados = array();
    if ($item_pcmso_id == 0) {
    $dados = array(
    "item_pcmso_produtos_manuseados" => $pro_manuseados,
    "fk_cargo_id" => $cargo_id,
    "fk_funcao_id" => $funcao_id,
    "fk_pcmso_id" => $pcmso_id,
    "fk_periodo_id" => $periodo_id
    );
    $item_pcmso_id = $item_pcmso->insert($dados);
    }
    $this->view->dados = $dados;
    $this->_helper->layout->disableLayout();
    $this->renderScript("ajax/pcmso/tabela-riscos.phtml");
    } catch (Zend_Exception $e) {

    }
    } */

    public function salvarExameNoItemDoPcmso()
    {
        $post = $this->getRequest()->getPost();
        if ((int) $post['fk_tipoexame_id'] == 0 || (int) $post['fk_produto_id'] == 0 || (int) $post['fk_periodo_id'] == 0) {
            echo '<div class="alert alert-warning">Selecione um tipo de exame, um exame e um período.</div>';
        } else {
            try {
                $model_item_pcmso_produto = new Application_Model_ItemPcmsoProduto();
                $retorno = $model_item_pcmso_produto->verificaExistencia($post['fk_produto_id'], $post['fk_periodo_id'], $post['fk_tipoexame_id'], $post['fk_item_pcmso_id']);
                if (count($retorno) == 0) {
                    $model_item_pcmso_produto->insert($post);
                    $this->view->dados = array(
                        'examesDoPcmso' => $model_item_pcmso_produto->obterExamesPeloItemDoPcmso($post['fk_item_pcmso_id']),
                    );
                    $this->_helper->layout->disableLayout();
                    $this->renderScript("ajax/pcmso/tabela-exames.phtml");
                } else {
                    echo '<div class="alert alert-warning">Este produto já se encontra nesta alocação.</div>';
                }
            } catch (Zend_Exception $e) {
                echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
            }
        }
    }

    public function salvarRiscoNoItemDoPcmso()
    {
        $post = $this->getRequest()->getPost();
        $post['fk_exposicao_id'] = (int) $post['fk_exposicao_id'] == 0 ? null : (int) $post['fk_exposicao_id'];
        if ((int) $post['fk_risco_id'] == 0) {
            echo '<div class="alert alert-warning">Selecione um risco.</div>';
        } else {
            try {
                $model_item_pcmso_risco = new Application_Model_ItemPcmsoRisco();
                $retorno = $model_item_pcmso_risco->verificaExistencia($post['fk_item_pcmso_id'], $post['fk_risco_id']);
                if (count($retorno) == 0) {
                    try {
                        $model_item_pcmso_risco->insert($post);
                    } catch (Zend_Exception $ee) {
                        echo '<div class="alert alert-danger"><strong>Erro ao inserir:</strong> ' . $ee->getMessage() . '</div>';
                    }
                    $this->view->dados = array(
                        'riscosDoPcmso' => $model_item_pcmso_risco->obterRiscosPeloItemDoPcmso($post['fk_item_pcmso_id']),
                    );
                    $this->_helper->layout->disableLayout();
                    $this->renderScript("ajax/pcmso/tabela-riscos.phtml");
                } else {
                    echo '<div class="alert alert-warning">Este risco já se encontra nesta alocação.</div>';
                }
            } catch (Zend_Exception $e) {
                echo '<div class="alert alert-danger"><strong>Erro desconhecido:</strong> ' . $e->getMessage() . '</div>';
            }
        }
    }

    public function listarContratoPelaEmpresa()
    {
        $contratos = array();
        $id = $this->_getParam('id', 0);
        try {
            $c = new Application_Model_Contrato();
            $filtrar = "contrato.contrato_status = 0 AND empresa.empresa_id = {$id} AND contrato.fk_unidade_id = {$_SESSION['usuario']['unidadeativa']['unidade_id']}";
            $ordenar = "contrato.contrato_numero";
            $contratos = $c->buscaCompletaUsandoClausula($filtrar, $ordenar);
        } catch (Exception $e) {

        }
        echo $this->json($contratos);
    }

    public function listarProdutoPorId()
    {
        $itemProduto = array();
        $id = $this->_getParam('id', 0);
        try {
            $p = new Application_Model_Produto();
            $resultado = $p->fetchRow(array('produto_id = ?' => $id))->toArray();
            $itemProduto = (is_array($resultado) && count($resultado) > 0) ? $resultado : array();
        } catch (Exception $e) {

        }
        echo $this->json($itemProduto);
    }

    public function obterFuncionarioPeloContratoEmpresa()
    {
        $empresaId = $this->_getParam('empresaId');
        $contratoId = $this->_getParam('contratoId');
        $funcionarios = array();
        try {
            $f = new Application_Model_Funcionario();
            $filtro = "funcionario.fk_contrato_id = {$contratoId} AND funcionario.fk_empresa_id = {$empresaId} AND funcionario.funcionario_status = 0";
            $funcionarios = $f->obterPeloFiltro($filtro);
        } catch (Exception $e) {

        }
        echo $this->json($funcionarios);
    }

    public function obterFuncionarioPorIdPessoaEmpresa()
    {
        $pessoaId = $this->_getParam('pessoaId');
        $empresaId = $this->_getParam('empresaId');
        $funcionarios = array();
        try {
            $f = new Application_Model_Funcionario();
            $filtro = "pessoa.pessoa_id = {$pessoaId} AND empresa.empresa_id = {$empresaId}";
            $funcionarios = $f->obterPeloIdFuncionario($filtro);
        } catch (Exception $e) {

        }
        echo $this->json($funcionarios);
    }

    public function ajaxObterEmpresasPorIds()
    {
        $tipoRetorno = trim($this->_getParam('formatoRetorno'));
        $quemChama = trim($this->_getParam('chamadoNaPagina'));
        $listaIds = trim($this->_getParam('ids'));
        $parametroEmpresaPrincipal = $this->getParam('idEmpresaPrincipal');
        $e = new Application_Model_Empresa();
        $resultadoConsulta = array();
        if ($quemChama == 'criarContrato') {
            $apenasEssas = "empresa_id IN($listaIds) AND empresa_status = 0";
            try {
                $resultado = $e->fetchAll($apenasEssas)->toArray();
                if (is_array($resultado) && count($resultado) > 0) {
                    $resultadoConsulta = $resultado;
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        $this->_helper->layout->disableLayout();
        $this->view->resultado = $resultadoConsulta;
        $this->view->empresaPrincipalId = $parametroEmpresaPrincipal;
        $this->renderScript('/ajax/empresa/ajax-obter-empresas-por-ids.phtml');
    }

    public function ajaxObterProdutosPorIds()
    {
        $tipoRetorno = trim($this->_getParam('formatoRetorno'));
        $quemChama = trim($this->_getParam('chamadoNaPagina'));
        $listaIds = $this->_getParam('ids');

        $e = new Application_Model_Contrato();
        $resultadoConsulta = array();
        if ($quemChama == 'criarContrato') {
            try {
                $resultado = $e->obterPeloProduto($listaIds);
                if (is_array($resultado) && count($resultado) > 0) {
                    $resultadoConsulta = $resultado;
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        $this->_helper->layout->disableLayout();
        $this->view->resultado = $resultadoConsulta;
        $this->renderScript('/ajax/produto/ajax-obter-produtos-por-ids.phtml');
    }

    public function ajaxExibirDiasAtendimento()
    {

        $dias_model = new Application_Model_DiasSemAtendimento();
        $dias = $dias_model->verificarDiaAtendimento();

        $this->_helper->layout->disableLayout();
        $this->view->resultado = $resultadoConsulta;
        $this->renderScript('/ajax/agenda/dias-atendimento.phtml');
    }

    public function ajaxRecuperarDadosConsultaPeloId()
    {
        $tipoRetorno = trim($this->_getParam('formatoRetorno'));
        $quemChama = trim($this->_getParam('chamadoNaPagina'));
        $listaIds = $this->_getParam('ids');

        $e = new Application_Model_Contrato();
        $resultadoConsulta = array();
        try {
            $resultado = $e->obterPeloProduto($listaIds);
            if (is_array($resultado) && count($resultado) > 0) {
                $resultadoConsulta = $resultado;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $this->_helper->layout->disableLayout();
        $this->view->resultado = $resultadoConsulta;
        $this->renderScript('/ajax/produto/ajax-obter-produtos-por-ids.phtml');
    }

    public function buscarAgendamentoPorId()
    {
        $agendaId = $this->_getParam('id');

        $p = new Application_Model_ProdutoAgenda();
        $produtosListados = $p->obterProdutosDoCliente($agendaId);
        //Util::dump($produtosListados);

        $f = new Application_Model_Funcionario();
        $funcionario = $f->obterInformacoesParaRecepcao($agendaId);

        $this->view->funcionarios = $funcionario;
        $this->view->produtos = $produtosListados;
        $this->_helper->layout->disableLayout();
        $this->renderScript('/ajax/agendamento/buscar-agendamento-por-id.phtml');
    }

    public function listarDiasDeAtendimento()
    {

        $data = date();

        $p = new Application_Model_ProdutoAgenda();
        $produtosListados = $p->obterProdutosDoCliente($agendaId);
        //Util::dump($produtosListados);

        $f = new Application_Model_Funcionario();
        $funcionario = $f->obterInformacoesParaRecepcao($agendaId);

        $this->view->funcionarios = $funcionario;
        $this->view->produtos = $produtosListados;
        $this->_helper->layout->disableLayout();
        $this->renderScript('/ajax/agendamento/formulario.phtml');
    }

    public function buscarEmpresaPeloCnpj()
    {
        $cnpj = preg_replace('/[^0-9]/', '', $this->_getParam('cnpj'));
        $model = new Application_Model_Empresa();
        $empresa = $model->buscarCompletoUsandoClausula("empresa.empresa_cnpj = '{$cnpj}'");
        echo $this->json($empresa);
    }

    public function obterFuncoesParticionadas()
    {
        $parte = $this->_getParam('parte', 0);
        $quantidade = $this->_getParam('quantidade', 100);
        $model = new Application_Model_Funcao();
        $funcoes = $model->obterParte($parte, $quantidade);
        echo $this->json($funcoes);
    }

    public function obterCargosParticionados()
    {
        $parte = $this->_getParam('parte', 0);
        $quantidade = $this->_getParam('quantidade', 100);
        $model = new Application_Model_Cargo();
        $funcoes = $model->obterParte($parte, $quantidade);
        echo $this->json($funcoes);
    }

    public function obterExamesPeloItemPcmso()
    {
        # Parâmetros da requisição
        $agendaid = (int) $this->_getParam('agenda_id', 0);
        $setorid = (int) $this->_getParam('setor_id', 0);
        $cargoid = (int) $this->_getParam('cargo_id', 0);
        $funcaoid = (int) $this->_getParam('funcao_id', 0);
        $empresaid = (int) $this->_getParam('empresa_id', 0);
        $contratoid = (int) $this->_getParam('contrato_id', 0);
        $tipoexameid = (int) $this->_getParam('tipoexame_id', 0);

        # Obter os produtos do item PCMSO compatível com o filtro de setor, cargo, função e empresa
        $m = new Application_Model_ItemPcmso();
        $r = $m->obterPeloFiltroETipoExame("setor.setor_id = {$setorid}
        AND cargo.cargo_id = {$cargoid}
        AND funcao.funcao_id = {$funcaoid}
        AND empresa.empresa_id = {$empresaid}", $tipoexameid);
        $produtos = $r['produtos'];

        # Obter os produtos da agenda em questão
        $pa = new Application_Model_ProdutoAgenda();
        $filtro = "agenda.agenda_id = {$agendaid} AND agenda.fk_tipoexame_id = {$tipoexameid} AND produto_agenda_status = 0";
        $itensRetornados = $pa->buscaCompletaUsandoClausula($filtro, "produto.produto_nome ASC");
        $itensProdutoAgenda = array();
        foreach ($itensRetornados as $item) {
            $itensProdutoAgenda[$item['produto_id']] = $item;
        }

        # Obter precificação e fornecedores de cada produto
        $tipoExame = new Application_Model_TipoExame();
        $tpExame = $tipoExame->obterTodos();
        $listaPessoaEspecialidade = new Application_Model_Pessoa();
        $pessoasEspecialidades = $listaPessoaEspecialidade->listarMedicoEEspecialidade();
        $produto = new Application_Model_Produto();
        $produtosExames = $produto->listarProdutosDaCategoriaExame();
        $precificacao = new Application_Model_Precificacao();
        $novaLista = array();
        foreach ($produtos as $prod) {
            $itemProduto = $prod;
            $resultadoConsulta = $precificacao->listarProdutoComPrecificaoCompletaPorId($prod['produto_id']);
            $itemProduto['fornecedores'] = (is_array($resultadoConsulta) && count($resultadoConsulta) > 0) ? $resultadoConsulta : array();
            $novaLista[] = $itemProduto;
            $itemProduto = array();
        }
        $produtos = $novaLista;

        //util::dump($produtos);
        # Renderização
        $this->view->produtoExame = $produtos;
        $this->view->itensProdutoAgenda = $itensProdutoAgenda;
        $this->_helper->layout->disableLayout();
        $this->renderScript('ajax/agendamento/exames-do-pcmso.phtml');
    }

    public function salvarValorFaturamentoMinimoCoordenacao()
    {

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            $classe = 'alert alert-success';
            $produto_contratado_id = (int) $this->_getParam('produto_contratado_id', 0);
            unset($post['produto_contratado_id']);

            try {
                $p = new Application_Model_ProdutoContratado();
                if ($produto_contratado_id > 0) {
                    $p->update($post, array('produto_contratado_id = ?' => $produto_contratado_id));
                }
                $resposta = "<strong>Sucesso!</strong> Registro Alterado com Sucesso";
            } catch (Exception $e) {
                $classe = 'alert alert-error';
                $resposta = "<strong>Registro Não foi Alterado, Erro</strong> {$e->getMessage()}";
            }
            $html = '<div class="' . $classe . '">
                        <button type="button" class="close" data-dismiss="alert">×</button>
                         ' . $resposta . '
                      </div>';
            echo $html;
        }
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function adicionarItemNaOs()
    {
        $dados = $this->getRequest()->getPost();

        $produtocontratado = new Application_Model_ProdutoContratado();
        $p = new Application_Model_Produto();
        $os = new Application_Model_Os();
        $consultaProduto = $p->buscarCompletaUsandoClausula("produto_id = {$dados["fk_produto_id"]} ");
        $dados['produto_contratado_faturamento_minimo'] = 0;
        if (is_array($consultaProduto) && count($consultaProduto) > 0) {
            $categoriaId = $consultaProduto[0]['categoriadoproduto_codigo_fixo'];
            if ($categoriaId == '4') {
                require_once '../application/business/CoordenacaoPcmsoBusiness.php';
                $coordenacao = new CoordenacaoPcmsoBusiness();
                $dados['produto_contratado_faturamento_minimo'] = $coordenacao->calcularFaturamentoMinimo($dados['produto_contratado_valor_venda'], $dados['produto_contratado_efetivo']);
            }
        }

        $osObservacao = $dados['os_observacao'];
        unset($dados['os_observacao']);

        $id = $produtocontratado->insert($dados);
        $os->update(array('os_observacao' => $osObservacao), array('os_id = ?' => $dados['fk_os_id']));

        $adapter = Zend_Db_Table::getDefaultAdapter();

        # Consulta tipos de cobrança
        $sql = "SELECT * FROM tipo_cobranca";
        $prepare = $adapter->prepare($sql);
        $prepare->execute();
        $tiposcobranca = $prepare->fetchAll();

        # Consulta formas de pagamento
        $sql = "SELECT * FROM formapagamento";
        $prepare = $adapter->prepare($sql);
        $prepare->execute();
        $formaspagamento = $prepare->fetchAll();

        $this->view->osid = $dados['fk_os_id'];
        $this->view->listaTiposCobranca = $tiposcobranca;
        $this->view->formasPagamento = $formaspagamento;
        $this->view->itens = $produtocontratado->obterProdutosContratadosDaOsSeparadosPorCategoria((int) $dados['fk_os_id']);
        $this->_helper->layout->disableLayout();
        $this->renderScript('ajax/item-os/listar-itens-da-os.phtml');
    }

    public function obterExamesPeloEspecialista()
    {
        $especialistaid = $this->getParam('id', 0);
        if ($especialistaid > 0) {
            $sql = "SELECT produto.produto_id, produto.produto_nome FROM agenda
			JOIN pessoa_especialidade ON pessoa_especialidade.pessoa_especialidade_id = agenda.fk_pessoa_especialidade_id
			JOIN produto_agenda ON produto_agenda.fk_agenda_id = agenda.agenda_id AND produto_agenda.produto_agenda_status = 0
			JOIN produto ON produto.produto_id = produto_agenda.fk_produto_id AND produto.produto_status = 0
			WHERE pessoa_especialidade.pessoa_especialidade_id = ?
			GROUP BY produto.produto_id";
        } else {
            $sql = "SELECT produto.produto_id, produto.produto_nome FROM produto
			JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id
			WHERE produto.produto_status = 0 AND categoriadoproduto.categoriadoproduto_codigo_fixo = 2";
        }
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $prepare = $adapter->prepare($sql);
        $prepare->execute(array($especialistaid));
        $exames = $prepare->fetchAll();
        echo $this->json($exames);
    }

    public function relatorioFatura()
    {
        $emp = $this->_getParam('empresaid', 0);
        $con = $this->_getParam('contratoid', 0);
        $dt1 = Util::dataBD($this->_getParam('dtinicio', 0));
        $dt2 = Util::dataBD($this->_getParam('dtfim', 0));

        # Busca baseada em filtro
        $filtro = "empresa.empresa_id = {$emp} "
            . "AND contrato.contrato_id = {$con} "
            . "AND (agenda.agenda_data_exame BETWEEN '{$dt1}' AND '{$dt2}' "
            . "OR agenda.agenda_data_clinico BETWEEN '{$dt1}' AND '{$dt2}')";
        $fatura = new Application_Model_Fatura();
        $produtos = $fatura->obterProdutosParaFaturar($con, $emp, $dt1, $dt2);
        #$outros = $fatura->obterProdutosParaFaturar($con, $emp, $dt1, $dt2);
        #$exames = $fatura->buscarProdutosParaFaturarPeloFiltro($filtro);
        #$produtos = array();
        #foreach ($exames as $item)
        #    $produtos[] = $item;
        #foreach ($outros as $item)
        #    $produtos[] = $item;

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $empresa = new Application_Model_Empresa();
        $empresaPagante = $empresa->obterEmpresaPaganteDoContrato($con);
        $empresapagante = (int) $empresaPagante[0]['empresa_id'];

        ob_start();
        include '../application/views/scripts/ajax/fatura/faturar-produtos.phtml';
        $html = ob_get_clean();
        echo json_encode(array('html' => $html));
    }

    public function obterVagasPorHorarioNaData()
    {

        $json = file_get_contents('php://input');
        $params = json_decode($json, true);

        try {

            $data = $params['data'];
            $_ = explode('/', $data);
            $ymd = "{$_[2]}-{$_[1]}-{$_[0]}";
            $unidadeID = (int) $params['unidade'];

            $Agenda = new Application_Model_Agenda();
            $HorarioGlobal = new Application_Model_HorarioGlobal();
            $HorarioDiario = new Application_Model_HorarioDiario();

            $horarios = $HorarioGlobal->obterHorariosDaUnidade($unidadeID);
            $horarios = $HorarioDiario->atualizar($horarios, $ymd);

            for ($i = 0; $i < count($horarios); $i++)
            {
                $agendados = $Agenda->contarAgendaDaUnidadePorDataHorario($unidadeID, $ymd, $horarios[$i]['id']);
                $vagas = (int) ($horarios[$i]['vagas'] - $agendados);
                $horarios[$i]['vagas'] = $vagas > 0 ? $vagas : 0;
            }

            $lista = $horarios;
            $this->view->data = $data;
            $this->view->horarios = $lista;
            $this->renderScript('/ajax/agenda/vagas-por-horario.phtml');

        } catch (Exception $ex) { die(Util::alertDanger($ex->getMessage())); }

    }

    public function obterDiasAtendimento()
    {

        $params = $this->getRequest()->getParams();

        try {

            $grid = [];
            $mes = $params['mes'];
            $ano = $params['ano'];
            $unidadeID = (int) $params['unidadeId'];
            $dias = Util::obterUtimoDoMes($mes, $ano);
            $dia = $mes == date('m') ? date('d') : '01';
            $data = new DateTime("{$ano}-{$mes}-{$dia}");
            if ($ano < date('Y')) die(Util::alertWarning("Data retroativa"));
            if ($ano = date('Y') && $mes < date('m')) die(Util::alertWarning("Data retroativa"));

            $Agenda = new Application_Model_Agenda();
            $HorarioGlobal = new Application_Model_HorarioGlobal();
            $HorarioDiario = new Application_Model_HorarioDiario();

            $horarios = $HorarioGlobal->obterHorariosDaUnidade($unidadeID);

            $data->add(new DateInterval('P1D'));
            if ($data->format('N') == 7) $data->add(new DateInterval('P1D'));
            if ($data->format('N') == 6) $data->add(new DateInterval('P2D'));

            for ($i = 0; $i < $dias; $i++)
            {

                if ($i > 0) $data->add(new DateInterval('P1D'));
                $ymd = $data->format('Y-m-d');
                $dmy = $data->format('d/m/Y');
                $dia = $data->format('N');
                if ($dia > 5) continue;
                $dispo = 0;

                foreach ($horarios as $h) {

                    $total = $Agenda->contarAgendaDaUnidadePorDataHorario($unidadeID, $ymd, $h['id']);
                    $vagas = $HorarioDiario->vagasDaUnidadeNaDataNoHorario($unidadeID, $ymd, $h['id']);
                    $dispo += $vagas - $total > 0 ? $vagas - $total : 0;

                }

                $grid[] = ['data' => $dmy, 'vagas' => $dispo];

            }

            $this->view->vagas = $grid;
            $this->renderScript('/ajax/agenda/dias-atendimento.phtml');

        } catch (Exception $ex) { die(Util::alertDanger($ex->getMessage())); }

    }

    public function ajaxObterHistoricoFuncionario()
    {
        $parametros = $this->getRequest()->getParams();
        $contrato = $_SESSION['fk_contrato_id'];
        $empresa = $_SESSION['empresa']['empresa_id'];
        $adapter = Zend_Db_Table::getDefaultAdapter();

        $Funcionario = new Application_Model_Funcionario();
        $funcionario = $Funcionario->obter((int) $parametros['ids']);
        $this->view->funcionario = $funcionario;

        $agenda = new Application_Model_Agenda();
        $historicoFuncionario = $agenda->historicoFuncionario($contrato, $empresa, $funcionario['funcionario_id']);

        $id = $funcionario['fk_pessoa_id'];
        $agendas = array();
        foreach ($historicoFuncionario as $agendado) {
            $sql = "SELECT *
                        FROM fichamedica
                    WHERE fichamedica.fk_agenda_id = '{$agendado['agenda_id']}'
                        AND fichamedica.fichamedica_status = '0'";
            $prep = $adapter->prepare($sql);
            $prep->execute();
            $agendado['resultados'] = $prep->fetchAll();

            $agendas[] = $agendado;
            $id = (int) $agendado["pessoa_id"];
        }

        # TREINAMENTO
        $treinamentoModel = new Application_Model_Treinamento();
        $treinamentos = $treinamentoModel->obterTreinamentosPeloAluno($id);
        $this->view->lista = $treinamentos;

        $this->_helper->layout->disableLayout();
        $this->view->resultado = $agendas;
        $this->renderScript('/ajax/funcionario/historico-funcionario.phtml');
    }

    public function obterUnidades() {

        try {

            $model = new Application_Model_Unidade();
            $unidades = $model->obterTodos();
            echo $this->json($unidades);

        } catch (Exception $ex) {

            echo $this->json(['error' => $ex->getMessage()]);

        }

    }

}
