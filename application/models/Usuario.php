<?php

class Application_Model_Usuario extends Zend_Db_Table
{

    protected $_name = 'usuario';
    protected $_primary = 'usuario_id';

    public function listarInstrutoresDeTreinamento($ordernacao = 'pessoa.pessoa_nome')
    {
        $sql = "SELECT * FROM usuario
                JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                JOIN pessoa_especialidade ON pessoa_especialidade.fk_pessoa_id = pessoa.pessoa_id
                JOIN especialidade ON especialidade.especialidade_id = pessoa_especialidade.fk_especialidade_id
                WHERE especialidade.especialidade_sigla_conselho = 'INST'
                ORDER BY {$ordernacao} ASC";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterPeloFiltro($where = "1 = 1")
    {
        $resultado = array();
        $sql = "SELECT usuario.*, pessoa.* , pe.pessoa_especialidade_id
                    FROM usuario
                              JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                              LEFT JOIN pessoa_especialidade pe ON pe.fk_pessoa_id = usuario.fk_pessoa_id
                    WHERE {$where}
                                AND usuario.usuario_status = 0
                    ORDER BY pessoa.pessoa_nome ASC";
        $itens = $this->getDefaultAdapter()->fetchAll($sql);
        $comando = "SELECT *
                              FROM unidade_usuario uus
                                        JOIN unidade uni ON(uni.`unidade_id` = uus.`fk_unidade_id`)
                              WHERE  uus.`fk_usuario_id` =  ?";
        /* $comando_coordenador = "SELECT *
        FROM rh_coordenador
        JOIN rh_setor ON rh_setor_id = fk_rh_setor_id
        JOIN rh_local ON rh_local_id = fk_rh_local_id
        JOIN unidade ON unidade_id = fk_unidade_id
        WHERE fk_usuario_id = ?"; */
        foreach ($itens as $item) {
            $item["unidade"] = $this->getDefaultAdapter()->fetchAll($comando, array($item['usuario_id']));
            //$item["coordenador"] = $this->getDefaultAdapter()->fetchRow($comando_coordenador, array($item['usuario_id']));
            $resultado[] = $item;
        }
        return $resultado;
    }

    public function obterPeloFiltroCompleto($where = "1 = 1")
    {
        $resultado = array();
        $sql = "SELECT *
                    FROM usuario
                              JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                    WHERE {$where}
                                AND usuario.usuario_status = 0
                    ORDER BY pessoa.pessoa_nome ASC";
        $itens = $this->getDefaultAdapter()->fetchAll($sql);
        $comando = "SELECT *
                              FROM unidade_usuario uus
                                        JOIN unidade uni ON(uni.`unidade_id` = uus.`fk_unidade_id`)
                              WHERE  uus.`fk_usuario_id` =  ?";
        $comando_coordenador = "SELECT *
            FROM rh_coordenador
                JOIN rh_setor ON rh_setor_id = fk_rh_setor_id
                JOIN rh_local ON rh_local_id = fk_rh_local_id
                JOIN unidade ON unidade_id = fk_unidade_id
            WHERE fk_usuario_id = ?";
        foreach ($itens as $item) {
            $item["unidade"] = $this->getDefaultAdapter()->fetchAll($comando, array($item['usuario_id']));
            $item["coordenador"] = $this->getDefaultAdapter()->fetchRow($comando_coordenador, array($item['usuario_id']));
            $resultado[] = $item;
        }
        return $resultado;
    }

    public function obterTelefoneUsuario($usuario_id)
    {
        $comando = "SELECT *
                     FROM usuario u
                        INNER JOIN pessoa p
                          ON p.`pessoa_id` = u.`fk_pessoa_id`
                        INNER JOIN telefone t
                          ON p.`pessoa_id` = t.`fk_pessoa_id`
                    WHERE u.`usuario_id` = {$usuario_id}
                          AND u.usuario_status = 0
                    GROUP BY u.`usuario_id`
                    ORDER BY t.`telefone_id`";

        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function obterTudo()
    {
        $sql = "SELECT * FROM usuario JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id WHERE usuario_status = 0";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obter($id)
    {
        $sql = "SELECT *, DATE_FORMAT(pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento FROM usuario JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id WHERE usuario.usuario_id = ? AND usuario_status = 0";
        $sql_acesso = "SELECT * FROM perfil_servico WHERE fk_usuario_id = ?";
        $tmp_acessos = $this->getDefaultAdapter()->fetchAll($sql_acesso, array($id));
        $menus = array();
        foreach ($tmp_acessos as $item) {
            $menus[] = $item["fk_perfil_id"] . ";" . $item["fk_servico_id"] . ";" . $item["fk_acao_id"];
        }
        $usuario = $this->getDefaultAdapter()->fetchRow($sql, array($id));
        $usuario["acesso"] = $menus;
        return $usuario;
    }

    public function obterPessoaPeloId($id)
    {
        $sql = "SELECT
                    u.`usuario_email`,
                    t.`telefone_ddd`,
                    t.`telefone_numero`,
                    u.`usuario_setor`,
                    t.`telefone_ramal`,
                    p.`pessoa_nome`
                  FROM
                    usuario u
                    JOIN pessoa p
                      ON p.`pessoa_id` = u.`fk_pessoa_id`
                    join `telefone` t
                      on t.`fk_pessoa_id` = p.`pessoa_id`
                  WHERE u.usuario_id = ?";

        $usuario = $this->getDefaultAdapter()->fetchRow($sql, $id);
        return $usuario;
    }

    /* public function selecionarComRegraDeLogin($usuario, $senha) {
    $retorno = array();
    $evtDeletar = Log::EVENTO_DELETE;
    $evtInativar = Log::EVENTO_INATIVAR;
    $comando = "SELECT u.*,
    p.*
    FROM usuario u
    INNER JOIN pessoa p on (p.pessoa_id = u.`fk_pessoa_id`)
    WHERE u.usuario_id NOT IN (
    SELECT log_tabela_id
    FROM `log`
    WHERE log_evento IN('{$evtDeletar}', '{$evtInativar}')
    AND log_tabela = 'usuario'
    AND log_tabela_id IS NOT NULL
    )
    AND u.`usuario_login` = ?
    AND u.`usuario_senha` = ?";
    $executarComando = $this->getDefaultAdapter()->fetchRow($comando, array($usuario, $senha));
    (is_bool($executarComando) && $executarComando == false) ? $executarComando = array() : null;
    //if (count($executarComando) > 0) {
    $retorno['usuario'] = $executarComando;
    $idUsuario = $retorno['usuario']['usuario_id'];
    $comando = "SELECT s.*,
    p.*,
    ps.*,
    GROUP_CONCAT(a.acao_nome) AS acoes
    FROM servico s
    INNER JOIN perfil_servico ps ON (ps.`fk_servico_id` = s.`servico_id`)
    INNER JOIN perfil p ON (p.`perfil_id` = ps.`fk_perfil_id`)
    INNER JOIN acao a ON (a.acao_id = fk_acao_id)
    WHERE ps.`fk_usuario_id` = {$idUsuario}
    GROUP BY s.`fk_modulo_id`, s.`servico_id`";
    $executarComando = $this->getDefaultAdapter()->fetchAll($comando);
    $retorno['servicos'] = (count($executarComando) > 0) ? $executarComando : array();

    $comando = "SELECT *
    FROM unidade u
    INNER JOIN unidade_usuario uu ON (uu.fk_unidade_id = u.unidade_id)
    WHERE uu.fk_usuario_id = {$idUsuario}";
    $executarComando = $this->getDefaultAdapter()->fetchAll($comando);
    $retorno['unidades'] = (is_array($executarComando) && count($executarComando) > 0) ? $executarComando : array();
    }//
    if (count($executarComando) > 0) {
    $retorno['usuario'] = $executarComando;
    $idUsuario = $retorno['usuario']['usuario_id'];
    $comando = "SELECT CONCAT( perfil.perfil_nome,  ';', modulo.modulo_nome,  ';', servico.servico_controller,  ';', acao.acao_nome ) AS item
    FROM perfil_servico
    JOIN servico ON servico.servico_id = perfil_servico.fk_servico_id
    JOIN perfil ON perfil.perfil_id = perfil_servico.fk_perfil_id
    JOIN acao ON acao.acao_id = perfil_servico.fk_acao_id
    JOIN modulo ON modulo.modulo_id = servico.fk_modulo_id
    WHERE perfil_servico.fk_usuario_id = ?";
    $executarComando = $this->getDefaultAdapter()->fetchAll($comando, array($idUsuario));

    $menu = array();
    foreach ($executarComando as $item) {

    #dados
    list($perfil, $modulo, $servico, $acao) = explode(";", $item["item"]);
    $perfil = strtoupper($perfil);
    $modulo = strtoupper($modulo);
    $servico = strtoupper($servico);
    $acao = strtoupper($acao);

    #menu
    if (!isset($menu[$modulo]))
    $menu[$modulo] = array();
    if (!isset($menu[$modulo][$servico]))
    $menu[$modulo][$servico] = array();
    $menu[$modulo][$servico][] = $acao;

    #acesso
    if (!isset($acesso[$servico]))
    $acesso[$servico] = array();
    $acesso[$servico][] = $acao;
    }

    //$retorno['servicos'] = (count($executarComando) > 0) ? $executarComando : array();
    $retorno['servicos'] = $menu;
    $retorno['acesso'] = $acesso;

    $comando = "SELECT *
    FROM unidade u
    INNER JOIN unidade_usuario uu ON (uu.fk_unidade_id = u.unidade_id)
    WHERE uu.fk_usuario_id = {$idUsuario}";
    $executarComando = $this->getDefaultAdapter()->fetchAll($comando);
    $retorno['unidades'] = (is_array($executarComando) && count($executarComando) > 0) ? $executarComando : array();
    }
    return $retorno;
    } */

    public function selecionarComRegraDeLogin($usuario, $senha)
    {
        $sql_usuario = "SELECT * FROM usuario
                            JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                        WHERE usuario_login = ? AND usuario_senha = ? AND usuario_status = 0";

        $fet_usuario = $this->getDefaultAdapter()->fetchRow($sql_usuario, array($usuario, $senha));
        $retorno = [];
        if ($fet_usuario) {
            $sql_unidade = "SELECT * FROM unidade_usuario
            JOIN unidade ON unidade.unidade_id = unidade_usuario.fk_unidade_id AND unidade.unidade_status = 0
            LEFT JOIN endereco ON endereco.endereco_id = unidade.fk_endereco_id
            WHERE unidade_usuario.fk_usuario_id = ?";
            $fet_unidade = $this->getDefaultAdapter()->fetchAll($sql_unidade, array($fet_usuario['usuario_id']));
            $fet_usuario['unidades'] = $fet_unidade;
            $fet_usuario['unidadeativa'] = $fet_unidade[0];
            $retorno = array();

            if (count($fet_usuario) > 0) {
                $retorno['usuario'] = $fet_usuario;
                $sql_acesso = "
                SELECT
                    CONCAT(servico.servico_endereco, '/', LOWER(acao.acao_nome)) AS url
                FROM perfil_servico
                    JOIN servico ON servico.servico_id = perfil_servico.fk_servico_id
                    JOIN acao ON acao.acao_id = perfil_servico.fk_acao_id AND acao.acao_status = 0
                WHERE fk_usuario_id = :usuario_id
                      AND servico.servico_status = 0
                GROUP BY url

                UNION

                SELECT
                    CONCAT(servico.servico_endereco, '/api') AS url
                FROM perfil_servico
                    JOIN servico ON servico.servico_id = perfil_servico.fk_servico_id
                WHERE fk_usuario_id = :usuario_id AND servico.servico_status = 0
                ";
                $fet_acesso = $this->getDefaultAdapter()->fetchAll($sql_acesso, [':usuario_id' => $fet_usuario['usuario_id']]);

                // Armazena dados referente a especialidade
                // do funcionário
                $retorno['usuario']['especialidade'] = [];
                $pe = new Application_Model_PessoaEspecialidade();
                $filtro = "fk_pessoa_id = '{$retorno['usuario']['pessoa_id']}'";
                $resultadoEspecialidade = $pe->buscaCompletaUsandoClausula($filtro);
                if (count($resultadoEspecialidade) > 0) {
                    $resultadoEspecialidade = $resultadoEspecialidade[0];
                }

                $retorno['usuario']['especialidade'] = $resultadoEspecialidade;

                $retorno['acesso'] = [];
                foreach ($fet_acesso as $acesso) {
                    $retorno['acesso'][] = $acesso['url'];
                }

                $sql_menu = "
                SELECT
                    CONCAT(modulo.modulo_nome, ';', servico.servico_apelido, ';', servico.servico_endereco) AS item
                FROM perfil_servico
                JOIN servico ON servico.servico_id = perfil_servico.fk_servico_id
                JOIN modulo ON modulo.modulo_id = servico.fk_modulo_id
                WHERE perfil_servico.fk_usuario_id = ?
                     AND servico.servico_menu = 1
                     AND servico.servico_status = 0
                GROUP BY item
                ORDER BY modulo.modulo_ordem ASC";
                $fet_menu = $this->getDefaultAdapter()->fetchAll($sql_menu, [$fet_usuario['usuario_id']]);
                $retorno['menu'] = [];
                foreach ($fet_menu as $item) {

                    #dados
                    list($modulo, $servico, $url) = explode(';', $item['item']);

                    #menu
                    if (!isset($menu[$modulo])) {
                        $menu[$modulo] = [];
                    }

                    $menu[$modulo][] = ['nome' => $servico, 'url' => $url];

                    $retorno['menu'] = $menu;
                }
                $sql_tels = "SELECT * FROM telefone WHERE fk_pessoa_id = ?";
                $fet_tels = $this->getDefaultAdapter()->fetchAll($sql_tels, [$fet_usuario['fk_pessoa_id']]);
                $retorno['usuario']['telefones'] = $fet_tels;
            }
        }
        return $retorno;
    }

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'usuario.usuario_id', $limit = '0,99999999999')
    {
        $comando = "SELECT *
                              FROM usuario
                              JOIN pessoa ON (pessoa.`pessoa_id` = usuario.`fk_pessoa_id`)
                              LEFT JOIn telefone ON (telefone.`telefone_id` = usuario.`fk_telefone_id`)
                              WHERE {$clausulaComando}
                              ORDER BY $ordenarPor
                              LIMIT {$limit}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function verificaLoginExiste($login)
    {
        $r = $this->getDefaultAdapter()->fetchAll("SELECT usuario_login FROM usuario WHERE usuario.usuario_login = ?", array(strtoupper($login)));
        return count($r) > 0 ? true : false;
    }

    public function verificaCpfExiste($cpf)
    {
        $r = $this->getDefaultAdapter()->fetchAll("SELECT pessoa_cpf FROM pessoa WHERE pessoa.pessoa_cpf = ?", array($cpf));
        return count($r) > 0 ? true : false;
    }

    public function obterPeloCpf($cpf)
    {
        $r = $this->getDefaultAdapter()->fetchRow("SELECT * FROM pessoa JOIN usuario ON usuario.fk_pessoa_id = pessoa.pessoa_id WHERE pessoa.pessoa_cpf = ?", array($cpf));
        return $r;
    }

}