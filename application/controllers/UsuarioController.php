<?php

class UsuarioController extends Controller {

    public function init() {
        parent::init();
        /*
          $this->view->menu_lateral_itens = array(
          '/usuario' => '<i class="icon-th-large"></i> Usuário',
          '/usuario/adicionar' => '<i class="icon-plus"></i> Adicionar',
          );
         * 
         */
        $this->modulo = 'administracao';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
        /*
         * "idsServicoesBloqueadosParaSalvar", é uma lista com os ids contidos
         * na tabela de serviços. O objetivo desta variável é armazenar uma lista dos ids
         * bloqueados para inserção
         */
        $this->view->idsServicoesBloqueadosParaSalvar = array(
            54, 55
        );
    }

    public function indexAction() {
        $buscar = $this->_getParam('like');
        $pagina = $this->_getParam('page', 1);

        $paramNome = trim($this->_getParam('paramNome'));
        $paramSetor = trim($this->_getParam('paramSetor'));
        $paramEspecialidade = trim($this->_getParam('paramEspecialidade'));

        $form = array(
            'paramNome' => $paramNome,
            'paramSetor' => $paramSetor,
            'paramEspecialidade' => $paramEspecialidade
        );

        if (strlen($paramEspecialidade) != 0) {
            if (strtoupper($paramEspecialidade) == 'SIM') {
                $paramEspecialidade = "AND pe.pessoa_especialidade_id IS NOT NULL";
            }else{
                $paramEspecialidade = "AND pe.pessoa_especialidade_id IS NULL";
            }
        }else{
            $paramEspecialidade = "";
        }


        #Util::dump($paramEspecialidade);

        $where = "pessoa.pessoa_nome LIKE '%{$paramNome}%' AND usuario.usuario_setor LIKE '%{$paramSetor}%' {$paramEspecialidade}";
        //$where = "pessoa.pessoa_nome LIKE '%{$buscar}%'";
        $model_usuario = new Application_Model_Usuario();
        $consulta = $model_usuario->obterPeloFiltro($where);

        $resultadoPaginado = Zend_Paginator::factory($consulta);
        $resultadoPaginado->setCurrentPageNumber($pagina);
        $this->view->itensPaginados = $resultadoPaginado;
        $this->view->like = '';
        $this->view->form = $form;

        $this->_log->logDetalhe = 'Listagem de todos os usuario';
        $this->_log->logTabelaNome = 'usuario';
        $this->_log->logTabelaColunaValor = '*';
        $this->_log->logTabelaColunaNome = '*';
    }

    public function alterarAction() {

        $usuario_id = $this->_getParam('id', 0);

        $this->_log->logDetalhe = 'Formulário de inclusão e/ou alteracao de usuario';
        $this->_log->logTabelaNome = 'usuario';
        $this->_log->logTabelaColunaValor = $usuario_id;
        $this->_log->logTabelaColunaNome = 'usuario_id';


        $model_usuario = new Application_Model_Usuario();
        $model_unidade = new Application_Model_Unidade();
        $model_perfil = new Application_Model_Perfil();
        $model_telefone = new Application_Model_Telefone();
        $model_servico = new Application_Model_Servico();
        $model_acao = new Application_Model_Acao();
        $model_rhsetor = new Application_Model_RhSetor();
        $model_rhcoordenador = new Application_Model_RhCoordenador();

        $unidade = explode(',', $model_unidade->unidadeUsuario($usuario_id));
        $unidades = $model_unidade->obterTodos();

        $perfis = $model_perfil->fetchAll()->toArray();
        #$servicos = $model_servico->fetchAll(array('servico_status = ?' => 0))->toArray();
        $servicos = $model_servico->obterListaPermissoes();
        $acoes = $model_acao->fetchAll(array('acao_status = ?' => 0))->toArray();
        $usuario = $model_usuario->obter($usuario_id);
        $_telefones = $model_telefone->fetchAll(array("fk_pessoa_id = ?" => $usuario["fk_pessoa_id"]), "telefone_id DESC")->toArray();

        $usuario["telefones"] = array();

        foreach ($_telefones as $fone)
            $usuario["telefones"][] = $fone;

        $colecaoSetores = array();
        $lotacao = array();
        try {
            $colecaoSetores = $this->_obterListaSetoresComJuncaoDeDependencias();
            $lotacao = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('lotacao_usuario_setor');
            $usuario['lotacao'] = $lotacao;

            if ($usuario_id > 0) {
                $ModeloLotacaoUsuarioSetor = new Application_Model_LotacaoUsuarioSetor();
                $Resultado = $ModeloLotacaoUsuarioSetor->fetchRow(array('lotacao_usuario_setor_status = ?' => 0, 'fk_usuario_id = ?' => $usuario_id));
                if ($Resultado) {
                    $usuario['lotacao'] = $Resultado->toArray();
                }
            }

            $ModeloTelefone = new Application_Model_Telefone();
            if ((int) $usuario['fk_telefone_id_celular_pessoal'] > 0) {
                $ResultadoComando = $ModeloTelefone->fetchRow(array('telefone_id = ?' => $usuario['fk_telefone_id_celular_pessoal']));
                $usuario['telefone_movel_pessoal'] = ($ResultadoComando) ? '(' . $ResultadoComando->telefone_ddd . ') ' . $ResultadoComando->telefone_numero : null;
            }
            if ((int) $usuario['fk_telefone_id_celular_corporativo'] > 0) {
                $ResultadoComando = $ModeloTelefone->fetchRow(array('telefone_id = ?' => $usuario['fk_telefone_id_celular_corporativo']));
                $usuario['telefone_movel_coporativo'] = ($ResultadoComando) ? '(' . $ResultadoComando->telefone_ddd . ') ' . $ResultadoComando->telefone_numero : null;
            }
        } catch (Exception $exc) {
            $this->_enviarMensagemDeExcecaoParaView($exc);
        }



        $this->view->colecaoSetores = $colecaoSetores;
        $this->view->unidade = $unidade;
        $this->view->unidades = $unidades;
        $this->view->perfis = $perfis;
        $this->view->servicos = $servicos;
        $this->view->acoes = $acoes;
        $this->view->atributos = $usuario;
        $this->view->setoresSemCoordenador = $model_rhsetor->obterPeloFiltro("rh_coordenador_id IS NULL OR usuario_id = '{$usuario_id}'");
        $this->view->setoresCoordenados = $model_rhcoordenador->obterIdsDeSetoresCoordenadosDoUsuario($usuario_id);

        $this->renderScript('usuario/formulario.phtml');
    }

    public function adicionarAction() {
        $unidades = $perfis = $servicos = $acoes = $colecaoSetores = array();
        try {
            $model_unidade = new Application_Model_Unidade();
            $model_perfil = new Application_Model_Perfil();
            $model_servico = new Application_Model_Servico();
            $model_acao = new Application_Model_Acao();
            $model_rhsetor = new Application_Model_RhSetor();

            $unidades = $model_unidade->obterTodos();
            $perfis = $model_perfil->fetchAll()->toArray();
            $servicos = $model_servico->fetchAll(array('servico_status = ?' => 0))->toArray();
            $acoes = $model_acao->fetchAll(array('acao_status = ?' => 0))->toArray();
            $colecaoSetores = $this->_obterListaSetoresComJuncaoDeDependencias();
            $this->view->colecaoSetores = $colecaoSetores;
        } catch (Exception $exc) {
            $this->_enviarMensagemDeExcecaoParaView($exc);
        }
        $this->view->colecaoSetores = $colecaoSetores;
        $this->view->unidade = array();
        $this->view->unidades = $unidades;
        $this->view->perfis = $perfis;
        $this->view->servicos = $servicos;
        $this->view->acoes = $acoes;
        $this->view->setoresSemCoordenador = $model_rhsetor->obterPeloFiltro("rh_coordenador_id IS NULL");
        $this->view->setoresCoordenados = array();

        $this->renderScript('usuario/formulario.phtml');
    }

    public function alterarSenhaAction() {
        self::$_habilitarRegistrarLog = false;
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();
            $erro = 1;
            $mensagens = array();
            try {
                ((!isset($post['senha0']) || empty($post['senha0'])) ? ($mensagens[] = 'Informe a senha atual.') : null);
                ((!isset($post['senha1']) || empty($post['senha1'])) ? ($mensagens[] = 'Informe a nova senha.') : null);
                ((!isset($post['senha2']) || empty($post['senha2'])) ? ($mensagens[] = 'Repita a nova senha.') : null);
                if (count($mensagens) == 0) {
                    $modelUsuario = new Application_Model_Usuario();
                    $usuario_id = (int) $_SESSION['usuario']['usuario_id'];
                    $usuario = $modelUsuario->fetchRow(array('usuario_id = ?' => $usuario_id, 'usuario_senha = ?' => sha1($post['senha0'])));
                    if (is_object($usuario)) {
                        if ($post['senha1'] == $post['senha2']) {
                            $numLinhasAfetadas = $modelUsuario->update(array('usuario_senha' => sha1($post['senha1'])), array('usuario_id = ?' => $usuario_id));
                            if ($numLinhasAfetadas > 0) {
                                $erro = 0;
                                $mensagens[] = 'Sua senha foi atualizada.';
                                $this->_log->logDetalhe = 'Alteracao de senha pelo proprio usuario';
                                $this->_log->logTabelaNome = 'usuario';
                                $this->_log->logTabelaColunaValor = $usuario_id;
                                $this->_log->logTabelaColunaNome = 'usuario_id';
                                self::$_habilitarRegistrarLog = true;
                            } else {
                                $erro = 2;
                                $mensagens[] = 'Parece que nada foi alterado.';
                            }
                        } else {
                            $erro = 2;
                            $mensagens[] = 'As senhas não conferem.';
                        }
                    } else {
                        $erro = 2;
                        $mensagens[] = 'A senha atual informada não corresponde.';
                    }
                } else {
                    $erro = 2;
                }
            } catch (Zend_Exception $e) {
                $mensagens[] = $e->getMessage();
            }
            $this->view->feedback = array('erro' => $erro, 'msg' => implode('<br/>', $mensagens));
            $this->_helper->layout->disableLayout();
            $this->renderScript('feedback.phtml');
        } else {
            $this->view->menu_lateral_itens = null;
        }
    }

    public function salvarAction() {
        self::$_habilitarRegistrarLog = false;
        if ($this->getRequest()->isPost()) {

            $feedback = array('erro' => 1, 'msg' => "");
            $post = $this->getRequest()->getPost();
            $usuario_id = (int) $post["usuario_id"];
            $fk_pessoa_id = (int) $post["fk_pessoa_id"];
            $atualizar_senha = ($usuario_id == 0 || ($usuario_id > 0 && strlen($post["senha1"]) > 0)) ? true : false;

//            $model_unidade = new Application_Model_Unidade();
//            $unidades = $model_unidade->obterUnidadeUsuario($usuario_id);
//            $this->view->unidades = $unidades;

            $validar = array(
                "usuario_setor" => array("tipo" => "texto", "nome" => "Setor"),
                "usuario_login" => array("tipo" => "texto", "nome" => "Login"),
                "pessoa_nome" => array("tipo" => "texto", "nome" => "Nome"),
                "pessoa_cpf" => array("tipo" => "texto", "nome" => "CPF"),
                    //"pessoa_data_nascimento" => array("tipo" => "texto", "nome" => "Data de Nasc."),
            );
            if ($atualizar_senha) {
                $validar["senha1"] = array("tipo" => "texto", "nome" => "Senha");
            }


            $validacao = Util::validaCampos($validar, $post);

            if ($atualizar_senha) {
                if ($post["senha1"] !== $post["senha2"]) {
                    $validacao["erros"][] = "As senhas não conferem";
                    $validacao["corrigir"][] = "senha1";
                    $validacao["corrigir"][] = "senha2";
                }
            }

            if (isset($post['fk_rh_setor_id']) && (int) $post['fk_rh_setor_id'] > 0 && strlen($post['lotacao_usuario_setor_nome_resumido']) <= 1) {
                $validacao["erros"][] = "Preencha o campo sigla para apresentação nos documentos com pelo menos 2 caractéres";
            }

            if (!isset($post["perfil-servico-acao"])) {
                $validacao["erros"][] = "Selecione algum acesso para este usuário.";
            }

            if (!isset($post['unidade'])) {
                $validacao["erros"][] = "Selecione ao menos, uma unidade.";
            }

            $telefone = str_replace(array('(', ')', '-'), '', $post['telefone_fixo']);

            if (!preg_match('/^[0-9]{2}\s[0-9]{8,}/', $telefone)) {
                $validacao["erros"][] = "O telefone parece não ser válido.";
            }

            $modelUsuario = new Application_Model_Usuario();
            $existeLogin = $modelUsuario->verificaLoginExiste($post["usuario_login"]);

            if ($existeLogin && $usuario_id == 0) {
                $validacao["erros"][] = "O login escolhido não é válido ou já está em uso.";
            }

            $cpf = str_replace(array('.', '-'), '', $post['pessoa_cpf']);
            $existeCpf = $modelUsuario->verificaCpfExiste($cpf);

            if ($existeCpf && $usuario_id == 0 && $fk_pessoa_id == 0) {
                $quem = $modelUsuario->obterPeloCpf($cpf);
                if (is_array($quem) && count($quem) > 0) {
                    $validacao["erros"][] = "O CPF informado já está cadastrado para <strong>{$quem['pessoa_nome']}</strong>.";
                }
            }

            if (count($validacao["erros"]) === 0) {

                $usuario = array(
                    "usuario_setor" => $post["usuario_setor"],
                    "usuario_supervisor" => $post["usuario_supervisor"],
                    "usuario_supervisor_email" => $post["usuario_supervisor_email"],
                    "usuario_email" => $post["usuario_email"],
                    "usuario_data_hora_criacao" => date("Y-m-d"),
                    "usuario_login" => $post["usuario_login"]
                );

                if ($atualizar_senha) {
                    $usuario["usuario_senha"] = sha1($post["senha1"]);
                }

                unset($post["senha1"]);
                unset($post["senha2"]);

                $pessoa = array(
                    "pessoa_nome" => $post["pessoa_nome"],
                    "pessoa_cpf" => str_replace(array(".", "-"), "", $post["pessoa_cpf"]),
                        //"pessoa_identidade" => $post["pessoa_identidade"],
                        //"pessoa_data_nascimento" => Util::dataBD($post["pessoa_data_nascimento"])
                );

                $strCelularPessoal = str_replace(array('(', ')', '-'), array('', ''), trim($post['telefone_movel_pessoal']));
                $strCelularCoporativo = str_replace(array('(', ')', '-'), array('', ''), trim($post['telefone_movel_coporativo']));
                $celularPessoalDDD = $celularCorporativoDDD = null;
                $celularPessoalNumero = $celularCorporativoNumero = null;
                if (strlen($strCelularCoporativo) >= 10) {
                    list($ddd, $numero) = explode(' ', $strCelularCoporativo);
                    $celularCorporativoDDD = $ddd;
                    $celularCorporativoNumero = $numero;
                    $ddd = $numero = null;
                }

                if (strlen($strCelularPessoal) >= 10) {
                    list($ddd, $numero) = explode(' ', $strCelularPessoal);
                    $celularPessoalDDD = $ddd;
                    $celularPessoalNumero = $numero;
                    $ddd = $numero = null;
                }


                $model_pessoa = new Application_Model_Pessoa();
                $model_usuario = new Application_Model_Usuario();
                $model_unidade = new Application_Model_Unidade();
                $model_telefone = new Application_Model_Telefone();
                $model_acesso = new Application_Model_Perfilservico();
                $model_rhcoordenador = new Application_Model_RhCoordenador();

                try {

                    $db = Zend_Db_Table::getDefaultAdapter();
                    $db->beginTransaction();

                    try {
                        $acesso = $post["perfil-servico-acao"];
                        unset($post["perfil-servico-acao"]);

                        $ModeloLotacaoUsuarioSetor = new Application_Model_LotacaoUsuarioSetor();

                        #PESSOA E USUARIO
                        if ($usuario_id > 0) {
                            $pessoa_id = $fk_pessoa_id;
                            $model_pessoa->update($pessoa, array("pessoa_id =?" => $pessoa_id));
                            $model_usuario->update($usuario, array("usuario_id =?" => $usuario_id));
                            $model_telefone->delete(array("fk_pessoa_id =?" => $pessoa_id));
                            // Inativa ou exclui a lotação atual
                            $ModeloLotacaoUsuarioSetor->update(array('lotacao_usuario_setor_status' => 2), array('fk_usuario_id = ?' => $usuario_id));
                            $this->_log->logDetalhe = 'Tentativa de alterar usuario';
                            $this->_log->logTabelaNome = 'usuario';
                            $this->_log->logTabelaColunaValor = $usuario_id;
                            $this->_log->logTabelaColunaNome = 'usuario_id';
                            self::$_habilitarRegistrarLog = true;
                        } else {
                            $pessoa_id = $fk_pessoa_id;
                            if ($fk_pessoa_id == 0) {
                                $pessoa_id = $model_pessoa->insert($pessoa);
                            }
                            $usuario["fk_pessoa_id"] = $pessoa_id;
                            $usuario_id = $model_usuario->insert($usuario);

                            $this->_log->logDetalhe = 'Tentativa de incluir usuario';
                            $this->_log->logTabelaNome = 'usuario';
                            $this->_log->logTabelaColunaValor = $usuario_id;
                            $this->_log->logTabelaColunaNome = 'usuario_id';
                            self::$_habilitarRegistrarLog = true;
                        }

                        if (isset($post['fk_rh_setor_id']) && (int) $post['fk_rh_setor_id'] > 0) {
                            $parms = array(
                                'lotacao_usuario_setor_nome_resumido' => $post['lotacao_usuario_setor_nome_resumido'],
                                'lotacao_usuario_setor_status' => 0,
                                'fk_usuario_id' => $usuario_id,
                                'fk_rh_setor_id' => (int) $post['fk_rh_setor_id']
                            );
                            $ModeloLotacaoUsuarioSetor->insert($parms);
                        }

                        #UNIDADES
                        $model_unidade->excluirTodosDoUsuario($usuario_id);
                        $parametroUnidade = $this->getParam('unidade');

                        if (is_array($post['unidade'])) {
                            foreach ($post['unidade'] as $value) {
                                $model_unidade->inserirLigacao(array('fk_unidade_id' => $value, 'fk_usuario_id' => $usuario_id));
                            }
                        } else {
                            $model_unidade->inserirLigacao(array('fk_unidade_id' => $parametroUnidade, 'fk_usuario_id' => $usuario_id));
                        }

                        #COORDENAÇÃO
                        $model_rhcoordenador->update(array('rh_coordenador_status' => 2), array('fk_usuario_id = ?' => $usuario_id));
                        //$model_rhcoordenador->delete(array('fk_usuario_id = ?' => $usuario_id));
                        if (isset($post['rh_setor'])) {
                            foreach ($post['rh_setor'] as $rh_setor_id) {
                                $model_rhcoordenador->insert(array('fk_usuario_id' => $usuario_id, 'fk_rh_setor_id' => $rh_setor_id));
                            }
                        }

                        #ACESSO
                        $model_acesso->delete(array("fk_usuario_id = ?" => $usuario_id));
                        foreach ($acesso as $p_s_a) {
                            list($perfil_id, $servico_id, $acao_id) = explode(";", $p_s_a);
                            $model_acesso->insert(array(
                                "fk_usuario_id" => $usuario_id,
                                "fk_perfil_id" => $perfil_id,
                                "fk_servico_id" => $servico_id,
                                "fk_acao_id" => $acao_id
                            ));
                        }

                        #TELEFONES
                        list($ddd, $numero) = explode(' ', $telefone);
                        $model_telefone->insert(array(
                            "telefone_ddi" => 55,
                            "telefone_ddd" => $ddd,
                            "telefone_numero" => $numero,
                            "telefone_ramal" => null,
                            "fk_pessoa_id" => $pessoa_id,
                        ));

                        if (strlen($celularCorporativoDDD) > 0 && strlen($celularCorporativoNumero) > 0) {
                            $params = array(
                                'telefone_ddd' => $celularCorporativoDDD,
                                'telefone_numero' => $celularCorporativoNumero,
                                'telefone_ddi' => 55,
                            );
                            if (isset($post['fk_telefone_id_celular_corporativo']) && (int) (int) $post['fk_telefone_id_celular_corporativo'] > 0) {
                                $model_telefone->update($params, array('telefone_id = ?' => (int) $post['fk_telefone_id_celular_corporativo']));
                            } else {
                                $telefone_id = $model_telefone->insert($params);
                                $model_usuario->update(array('fk_telefone_id_celular_corporativo' => $telefone_id), array('usuario_id = ?' => $usuario_id));
                            }
                        }

                        if (strlen($celularPessoalDDD) > 0 && strlen($celularPessoalNumero) > 0) {
                            $params = array(
                                'telefone_ddd' => $celularPessoalDDD,
                                'telefone_numero' => $celularPessoalNumero,
                                'telefone_ddi' => 55,
                            );
                            if (isset($post['fk_telefone_id_celular_pessoal']) && (int) (int) $post['fk_telefone_id_celular_pessoal'] > 0) {
                                $model_telefone->update($params, array('telefone_id = ?' => (int) $post['fk_telefone_id_celular_pessoal']));
                            } else {
                                $telefone_id = $model_telefone->insert($params);
                                $model_usuario->update(array('fk_telefone_id_celular_pessoal' => $telefone_id), array('usuario_id = ?' => $usuario_id));
                            }
                        }

                        $db->commit();

                        $feedback["erro"] = 0;
                        $feedback["msg"] = "Usuário <b>{$post["pessoa_nome"]}</b> salvo com sucesso.";
                    } catch (Zend_Exception $e) {
                        $db->rollBack();
                        $feedback["erro"] = 1;
                        $feedback["msg"] = $e->getMessage();
                    }
                } catch (Zend_Exception $eBeginTrans) {
                    $feedback["erro"] = 1;
                    $feedback["msg"] = $e->getMessage();
                }
            } else {

                $feedback["erro"] = 2;
                $feedback["msg"] = implode("<br/>", $validacao["erros"]);
                $feedback["corrigir"] = $validacao["corrigir"];
            }

            $this->view->feedback = ($feedback);
            $this->_helper->layout->disableLayout();
            $this->renderScript('feedback.phtml');
        }
    }

    public function excluirAction() {
        self::$_habilitarRegistrarLog = false;
        $id = (int) $this->_getParam('id', 0);
        $feedback = array('erro' => 1, 'msg' => array());
        $feedback['msg'][0] = 'Registro não foi inativado';
        if ((int) $id > 0) {
            $feedback['msg'][0] = 'Registro inativado com sucesso!';
            $feedback['erro'] = 0;
            $this->_log->logDetalhe = 'Tentativa de excluir usuario';
            $this->_log->logTabelaNome = 'usuario';
            $this->_log->logTabelaColunaValor = $id;
            $this->_log->logTabelaColunaNome = 'usuario_id';
            self::$_habilitarRegistrarLog = true;
        }
        $this->view->feedback = ($feedback);
        $this->_helper->layout->disableLayout();
        $this->renderScript('feedback.phtml');
    }

    private function _obterListaSetoresComJuncaoDeDependencias($paramSetorId = 0) {
        $resultado = array();
        $Comando = null;
        $param = (int) $paramSetorId;
        $operador = ($param > 0) ? ' = ?' : ' > ?';
        try {
            $Comando = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
            $Comando->from(array('rs' => 'rh_setor'))
                    ->join(array('rl' => 'rh_local'), 'rl.rh_local_id = rs.fk_rh_local_id')
                    ->join(array('un' => 'unidade'), 'rl.fk_unidade_id = un.unidade_id')
                    ->where("rs.rh_setor_id {$operador}", $param)
                    ->where('rs.rh_setor_status = ?', 0)
                    ->order('rs.rh_setor_nome ASC');
            $resultado = $Comando->query()->fetchAll();
        } catch (Exception $exc) {
            throw $exc;
        }
        //var_dump($resultado);
        return $resultado;
    }

}
