<?php

class Log {

    /**
     * ID do log
     * @var string
     * @access public
     */
    public static $id;

    /**
     * // 'INSERT', 'UPDATE', 'DELETE', 'SELECT', 'LOGIN', 'LOGOUT', 'ATIVAR', 'INATIVAR', 'OUTRO'
     * Evento do log.
     * Valores aceitos:
     * <ul>
     * <li>INSERT</li>
     * <li>UPDATE</li>
     * <li>DELETE</li>
     * <li>LOGIN</li>
     * <li>SELECT</li>
     * <li>LOGIN</li>
     * <li>LOGOUT</li>
     * <li>ATIVAR</li>
     * <li>INATIVAR</li>
     * <li>OUTRO</li>
     * </ul>
     * @var string
     * @access public 
     */
    public static $evento;

    /**
     * Data e hora do registro do log.
     * <b>Formato: ANO-MES-DIA HORA MINUTOS SEGUNDOS. 1989-06-06 03:06:07</b>
     * @var datetime
     * @access public
     */
    public static $dataHora;

    /**
     * Nome data tabela
     * @var string
     * @access public
     */
    public static $tabela;
    
    /**
     * Valor do ID de uma determinada tabela
     * @var int 
     */
    public static $tabelaId;

//    /**
//     * Nome data coluna
//     * @var string
//     * @access public
//     */
//    public static $coluna;
//
//    /**
//     * Valor para Coluna
//     * @var string
//     * @access public
//     */
//    public static $valorColuna;

    /**
     * Comando executado
     * @var string
     * @access public
     */
    public static $comandoExecutado;

    /**
     * Detalhes do comando
     * @var string
     * @access public
     */
    public static $detalhes;

    /**
     * Id do usuário.
     * @var int 
     * @access public
     */
    public static $idUsuario;

    /**
     * Url que o log registrará.
     * @var int
     * @access public
     */
    public static $url;

    /**
     * Instancia do adaptador de acesso a banco de dados
     * @var mixed
     * @access public
     */
    public static $adaptadorAcessoDados;

    /**
     * Define se log será registrado na tabela do banco de dados
     * @var bool
     * @access public
     */
    public static $registrarLog = true;

    /**
     * Obtem informações do cabeçalho contido na variável
     * super global do PHP, $_SERVER
     * @var array
     * @access private
     */private static $_informacaoDoCabecalho;

    const SALVAR_LOG_NA_TABELA = 'log';
    const EVENTO_INATIVAR = 'INATIVAR';
    const EVENTO_ATIVAR = 'ATIVAR';
    const EVENTO_INSERIR = 'INSERT';
    const EVENTO_ALTERAR = 'UPDATE';
    const EVENTO_SELECIONAR = 'SELECT';
    const EVENTO_DELETE = 'DELETE';
    const EVENTO_LOGAR = 'LOGIN';
    const EVENTO_DESLOGAR = 'LOGOUT';
    const EVENTO_OUTRO = 'OUTRO';

    public static function limparAtributos() {
        self::$dataHora = self::$tabela = self::$coluna = self::$valorColuna = null;
        self::$comandoExecutado = self::$detalhes = self::$idUsuario = self::$url = null;
        self::$registrarLog = self::$_informacaoDoCabecalho = null;
    }

    /**
     * Este método tem a função registrar o log no banco de dados.
     * @param array $listaAtributosAssociativos [opcional] - array associativo com as colunas a serem inseridas. Utilizando o padrão (HASH TABLE): nome da coluna indexada do valor, sendo <b>$item['nomeColuna'] = 'Valor da coluna'</b>;
     * <ul>
     *  <li>$itens = array ();</li>
     *  <li>$itens['log_evento'] = 'INSERT'; Eventos válidos: 'INSERT', 'UPDATE', 'DELETE', 'SELECT', 'LOGIN', 'LOGOUT', 'ATIVAR', 'INATIVAR', 'OUTRO'</li>
     *  <li>$itens['log_data_hora'] = '1989-06-06 03:35:00'; Padrão americano</li>
     *  <li>$itens['log_tabela'] = 'produto'; [Opcional] Nome da tabela utilizada</li>
     *  <li>$itens['log_coluna'] = 'produto_id'; [Opcional] Nome da coluna utilizada</li>
     *  <li>$itens['log_valor_coluna'] = '18'; [Opcional] valor a ser atribuido na coluna</li>
     *  <li>$itens['log_comando_executado'] = 'INSERT INTO tabela VALUES(x,y,z,q,w)'; [Opcional] Comando utilizado</li>
     *  <li>$itens['log_comando_executado'] = 'INSERT INTO tabela VALUES(x,y,z,q,w)'; [Opcional] Comando utilizado</li>
     *  <li>$itens['log_detalhes'] = 'Area para detalhes'; [Opcional] Campo excluiso e aberto para detalhes do log</li>
     *  <li>$itens['log_url'] = 'http://www.hiest.com.br/'; [Opcional] Url em que os dados do log estam sendo processado</li>
     *  <li>$itens['fk_usuario_id'] = '24'; ID do usuário no sistema</li>
     * </ul>
     * @param mixed $adaptadorAcessoDados [opcional] - Instancia da conexão de acesso a dados, caso não seja informado o método tenta utilizar o a instância atribuida no atributo $adaptadorAcessoDados.
     * @return boolean  - TRUE se sucesso, FALSE se erro.
     */
    public static function gravar(array $listaAtributosAssociativos = array(), $adaptadorAcessoDados = null) {
        $inserirColunas = array();
        $salvou = false;
        if (count($listaAtributosAssociativos) == 0) {
            $inserirColunas = self::_preparListaAtributosParaInsercaoComZend();
        } else {
            $inserirColunas = $listaAtributosAssociativos;
        }

        /*
         * - Silas Stoffel
         * - 27/08/2013
         * Até o momento está classe está escrita para trabalhar
         * apenas com adaptador "Zend_Db_Adapter_Pdo_Mysql", mas
         * está preparada para ser escrita com outros adaptadores de
         * acesso a banco de dados
         */
        if ($adaptadorAcessoDados == null) {
            $adaptadorAcessoDados = self::$adaptadorAcessoDados;
        }

        $possuiAtributosObrigatorio = (isset($inserirColunas['log_data_hora']) == false or $inserirColunas['log_data_hora'] == '') ? false : true;
        $possuiAtributosObrigatorio = (isset($inserirColunas['log_evento']) == false or $inserirColunas['log_evento'] == '') ? false : true;

        $inserirColunas['log_data_hora'] = (trim($inserirColunas['log_data_hora']) === '') ? date('Y-m-d H:i:s') : $inserirColunas['log_data_hora'];
        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $inserirColunas['log_url'] = (trim($inserirColunas['log_url']) === '') ? $url : $inserirColunas['log_url'];
        $inserirColunas['log_informacoes_cabecalho'] = json_encode($_SERVER);

        if (self::$registrarLog && $possuiAtributosObrigatorio) {

            if ($adaptadorAcessoDados instanceof Zend_Db_Adapter_Pdo_Mysql) {
                try {
                    $inserir = $adaptadorAcessoDados->insert(self::SALVAR_LOG_NA_TABELA, $inserirColunas);
                    if ($inserir > 0) {
                        $salvou = true;
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }

        return $salvou;
    }

    private static function _preparListaAtributosParaInsercaoComZend() {
        $atributos = array(
            'log_evento' => 'evento',
            'log_data_hora' => 'dataHora',
            'log_tabela' => 'tabela',
            'log_tabela_id' => 'tabelaId',
            // 'log_coluna' => 'coluna',
            // 'log_valor_coluna' => 'valorColuna',
            'log_comando_executado' => 'comandoExecutado',
            'log_detalhes' => 'detalhes',
            'log_url' => 'url',
            'fk_usuario_id' => 'idUsuario',
        );
        $novaLista = array();
        $atrib_dinamico = '';
        foreach ($atributos as $coluna => $atrib) {
            eval('$atrib_dinamico = self::$' . $atrib . ';');
            $novaLista[$coluna] = $atrib_dinamico;
        }
        return $novaLista;
    }

}