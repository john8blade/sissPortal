<?php

class AuthController extends Zend_Controller_Action {

    private static $_atributosContrato = array();
    private static $_mensagens = array();

    public function loginAction() {

        $entradas = array();
        $entradas['login'] = '';
        $entradas['senha'] = '';
        $mensagens = array();
        if ($this->getRequest()->isPost() === true) {
            $parametros = $this->getRequest()->getPost();
            $entradas = $parametros;
            if (!isset($entradas['login']) or ! isset($entradas['senha']) or empty($entradas['login']) or empty($entradas['senha'])) {
                $mensagens[] = 'Login e senha são obrigatórios';
            } else {
                $logar = self::_logar($entradas['login'], $entradas['senha']);
                if ($logar == false) {
                    $mensagens[] = self::$_mensagens[0];
                } else {
                    self::_salvarAtributosEmSessao();
                    $this->redirect('/');
                }
            }
        }
        $this->view->entradas = $entradas;
        $this->view->mensagens = $mensagens;
        $this->_helper->layout->disableLayout();
    }

    public function logoutAction() {
        $_SESSION = array();
        session_destroy();
        $host = $_SERVER['HTTP_HOST'];
        $this->redirect('http://'+$host);
        
    }

    private static function _logar($login, $senha) {
        $logar = false;
        $usuarioPortal = new Application_Model_UsuarioPortal();
        try {
            $resultadoLogar = $usuarioPortal->selecionarComRegraLogin(trim($login), trim(sha1($senha)), trim($senha));
            if (!empty($resultadoLogar)) {
                self::$_atributosContrato = $resultadoLogar;
                $logar = true;
            } else {
                $logar = false;
                self::$_mensagens[0] = 'Login ou senha inválidos!';
            }
        } catch (Exception $e) {
            self::$_mensagens[0] = 'Login ou senha inválidos!';
        }
        if (is_array($resultadoLogar) && count($resultadoLogar) > 0) {
            $statusContrato = (int) $resultadoLogar['usuario_portal_status'];
            $statusFaturaContrato = $resultadoLogar['situacaofatura'];
            // 0:ativo; 1:Inativo; 2:Excluido; 3:Bloqueado
            $codigoStatusPendente = array(1, 3);
            #$descricaoStatusPendente = array('Sua conta está inativa!', 'Acesso bloqueado. Gentileza entrar em contato com o depto financeiro da Hiest através do número 3064-7359');
            $descricaoStatusPendente = 'Sua conta está inativa! <br /> Acesso bloqueado. Gentileza entrar em contato com o depto financeiro da Hiest através do número 3064-7359';
            #$descricaoStatusPendente = wordwrap($descricaoStatusPendente, 23, "<br />\n");
            $codigoPendencia = array_search($statusContrato, $codigoStatusPendente);

            if (is_numeric($codigoPendencia) OR $statusFaturaContrato == true) {
                #self::$_mensagens[0] = $descricaoStatusPendente[$codigoPendencia];
                self::$_mensagens[0] = $descricaoStatusPendente;
                $logar = false;
            } else {
                // Consulta local para verificar se a empresa está inadimplente
                // Substituindo chamada de API externa por verificação direta no banco
                $contratoId = $resultadoLogar['fk_contrato_id'];
                $unidadeId = $resultadoLogar['fk_unidade_id'];
                try {
                    $ModeloContratante = new Application_Model_Contratante();
                    $params = array(
                        'fk_contrato_id = ?' => $contratoId,
                        'contratante_empresa_principal = ?' => 1
                    );
                    $Rst = $ModeloContratante->fetchRow($params);
                    if ($Rst) {
                        $empresaId = $Rst->fk_empresa_id;
                        $ModeloEmpresa = new Application_Model_Empresa();
                        $RstEmpresa = $ModeloEmpresa->fetchRow(array('empresa_id = ?' => $empresaId));
                        
                        // Verifica inadimplência usando o método atualizado que considera a flag de permissão
                        $inadimplentes = $ModeloEmpresa->obterEmpresaInadimplente(array($unidadeId), array($RstEmpresa->empresa_cnpj));
                        
                        if (count($inadimplentes) > 0) {
                            // Está inadimplente, mas permitimos o login com restrições
                            // Definimos uma flag na sessão para controlar o acesso restrito
                            self::$_atributosContrato['acesso_restrito_financeiro'] = true;
                            $logar = true;
                        } else {
                            $logar = true;
                            self::$_atributosContrato['acesso_restrito_financeiro'] = false;
                        }
                        self::$_atributosContrato = array_merge(self::$_atributosContrato, isset(self::$_atributosContrato) ? self::$_atributosContrato : array());
                    } else {
                        // Se não achou contratante, permite logar
                        $logar = true;
                        self::$_atributosContrato['acesso_restrito_financeiro'] = false;
                    }
                } catch (Exception $ex) {
                    // Em caso de erro, permite o login normal para não travar o usuário
                    // self::$_mensagens[0] = 'Erro ao executar comando de identificação do contrato';
                    $logar = true;
                }
            }
        }
        return $logar;
    }

    private static function _salvarAtributosEmSessao() {
        $_SESSION = self::$_atributosContrato;
    }

}
