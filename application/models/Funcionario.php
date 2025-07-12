<?php

class Application_Model_Funcionario extends Zend_Db_Table {

    protected $_name = "funcionario";
    protected $_primary = "funcionario_id";

    public function obterTodos() {
        $sql = "SELECT *,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            (SELECT pp.ppra_item_funcao FROM item_pcmso i JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id WHERE i.item_pcmso_id = alocacao.fk_item_pcmso_id LIMIT 1) AS ppra_item_funcao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            LEFT JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN ghe ON ghe.ghe_id = alocacao.fk_ghe_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
        WHERE funcionario_status = 0";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

// LEFT JOIN ghe ON ghe.ghe_id = alocacao.fk_ghe_id
    public function obterPeloFiltro($where = '') {
        $sql = "SELECT *,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            DATE_FORMAT(funcionario.funcionario_data_admissao, '%d/%m/%Y') AS funcionario_data_admissao,
            (SELECT DATE_FORMAT(agenda.agenda_data_clinico, '%d/%m/%Y') FROM agenda JOIN fichamedica ON fichamedica.fk_agenda_id = agenda.agenda_id AND fichamedica.fichamedica_resultado_aptidao = '1' WHERE agenda.fk_pessoa_id = pessoa.pessoa_id ORDER BY agenda.agenda_id DESC LIMIT 1) AS data_do_apto,
            (SELECT UPPER(tipoexame.tipoexame_nome) FROM agenda JOIN fichamedica ON fichamedica.fk_agenda_id = agenda.agenda_id AND fichamedica.fichamedica_resultado_aptidao = '1' JOIN tipoexame ON tipoexame.tipoexame_id = agenda.fk_tipoexame_id WHERE agenda.fk_pessoa_id = pessoa.pessoa_id ORDER BY agenda.agenda_id DESC LIMIT 1) AS tipo_do_apto,
            (SELECT pp.ppra_item_funcao FROM item_pcmso i JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id WHERE i.item_pcmso_id = alocacao.fk_item_pcmso_id LIMIT 1) AS ppra_item_funcao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            LEFT JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
        WHERE {$where} AND funcionario_status = 0
		ORDER BY pessoa.pessoa_nome ASC";
        //die("<pre>$sql</pre>");
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterPeloFiltroPortal($where = '') {
        $sql = "SELECT *,
            alocacao.fk_empresa_id as empresaAlocada,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            DATE_FORMAT(funcionario.funcionario_data_admissao, '%d/%m/%Y') AS funcionario_data_admissao,
            (SELECT DATE_FORMAT(agenda.agenda_data_clinico, '%d/%m/%Y') FROM agenda JOIN fichamedica ON fichamedica.fk_agenda_id = agenda.agenda_id AND fichamedica.fichamedica_resultado_aptidao = '1' WHERE agenda.fk_pessoa_id = pessoa.pessoa_id ORDER BY agenda.agenda_id DESC LIMIT 1) AS data_do_apto,
            (SELECT UPPER(tipoexame.tipoexame_nome) FROM agenda JOIN fichamedica ON fichamedica.fk_agenda_id = agenda.agenda_id AND fichamedica.fichamedica_resultado_aptidao = '1' JOIN tipoexame ON tipoexame.tipoexame_id = agenda.fk_tipoexame_id WHERE agenda.fk_pessoa_id = pessoa.pessoa_id ORDER BY agenda.agenda_id DESC LIMIT 1) AS tipo_do_apto,
            funcionario.fk_empresa_id,
            (SELECT pp.ppra_item_funcao FROM item_pcmso i JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id WHERE i.item_pcmso_id = alocacao.fk_item_pcmso_id LIMIT 1) AS ppra_item_funcao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            LEFT JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN empresa ON empresa.empresa_id = alocacao.fk_empresa_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
        WHERE {$where} AND funcionario_status = 0
		ORDER BY pessoa.pessoa_nome ASC";
        //die("<pre>$sql</pre>");
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterListaComAsRegrasDoSiss($filtros = array()) {

        $filtro = array(
            'cpf' => isset($filtros['cpf']) ? $filtros['cpf'] : "",
            'nome' => isset($filtros['nome']) ? $filtros['nome'] : "",
            'cargo' => isset($filtros['cargo']) ? $filtros['cargo'] : "",
            'status' => isset($filtros['status']) ? $filtros['status'] : "",
            'unidade' => isset($filtros['unidade']) ? $filtros['unidade'] : "",
            'empresa' => isset($filtros['empresa']) ? $filtros['empresa'] : "",
            'dataAdm' => isset($filtros['dataAdm']) ? $filtros['dataAdm'] : "");

        $Cnx = Zend_Db_Table::getDefaultAdapter();
        $ComandoCustomizado = $Cnx->select();
        $ComandoCustomizado->from(array('f' => 'funcionario'), array('funcionario_data_admissao', 'funcionario_id', 'funcionario_status', 'funcionario_motivo_inativacao', 'fk_empresa_id', 'fk_contrato_id'))
                ->join(array('p' => 'pessoa'), 'p.pessoa_id = f.fk_pessoa_id', array('pessoa_cpf', 'pessoa_nome'))
                ->join(array('a' => 'alocacao'), 'a.fk_funcionario_id = f.funcionario_id', array())
                ->join(array('c' => 'cargo'), 'c.cargo_id = a.fk_cargo_id', array('cargo_nome'))
                ->join(array('e' => 'empresa'), 'e.empresa_id = f.fk_empresa_id', array('empresa_fantasia'))
                ->where('p.pessoa_nome LIKE ?', "%{$filtro['nome']}%")
                ->where('c.cargo_nome LIKE ?', "%{$filtro['cargo']}%")
                ->where('e.empresa_fantasia LIKE ?', "%{$filtro['empresa']}%")
                ->order(array('p.pessoa_nome ASC', 'c.cargo_nome ASC', 'e.empresa_fantasia ASC'));

        if (strlen($filtro['cpf']) >= 11) {
            $c = str_replace(array('.', '-', '_', '/'), '', $filtro['cpf']);
            $ComandoCustomizado->where('p.pessoa_cpf = ?', $c);
        }

        if (strlen($filtro['unidade']) > 0) {
            $ComandoCustomizado->where('e.fk_unidade_id = ?', $filtro['unidade']);
        }

        if (strlen($filtro['dataAdm']) >= 10) {
            $d = Util::dataBD($filtro['dataAdm']);
            $ComandoCustomizado->where('f.funcionario_data_admissao = ?', $d);
        }

        if (strlen($filtro['status']) > 0) {
            if (strtoupper($filtro['status']) == 'INATIVO') {
                $x = "(f.funcionario_motivo_inativacao <> '' OR f.funcionario_status = 1)";
                $ComandoCustomizado->where($x);
            }
            if (strtoupper($filtro['status']) == 'ATIVO') {
                $x = "(f.funcionario_motivo_inativacao = '' OR f.funcionario_motivo_inativacao IS NULL)";
                $ComandoCustomizado->where($x);
                $x = "f.funcionario_status = 0 ";
                $ComandoCustomizado->where($x);
            }
        }
        return $ComandoCustomizado->query()->fetchAll();
    }

    //     LEFT JOIN ghe ON ghe.ghe_id = alocacao.fk_ghe_id
    public function obterPeloIdFuncionario($where = '') {
        $sql = "SELECT *,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            DATE_FORMAT(funcionario.funcionario_data_admissao, '%d/%m/%Y') AS funcionario_data_admissao,
            (SELECT pp.ppra_item_funcao FROM item_pcmso i JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id WHERE i.item_pcmso_id = alocacao.fk_item_pcmso_id LIMIT 1) AS ppra_item_funcao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            LEFT JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
            left join empresa on empresa.`empresa_id` = funcionario.`fk_empresa_id`
        WHERE {$where} AND funcionario_status = 0";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    //     LEFT JOIN ghe ON ghe.ghe_id = alocacao.fk_ghe_id
    public function verificarAlocacaoPeloCpf($cpf, $empresa_id) {
        $cpf = preg_replace('/\D/', '', $cpf);
        $sql = "SELECT *,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            DATE_FORMAT(funcionario.funcionario_data_admissao, '%d/%m/%Y') AS funcionario_data_admissao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            JOIN empresa ON empresa.empresa_id = funcionario.fk_empresa_id
            JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
        WHERE pessoa.pessoa_cpf = ? AND empresa.empresa_id = ? AND funcionario_status = 0";
        return $this->getDefaultAdapter()->fetchAll($sql, array($cpf, $empresa_id));
    }

    public function verificarAlocacaoPeloSetor($setor) {
        $sql = "SELECT *,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            DATE_FORMAT(funcionario.funcionario_data_admissao, '%d/%m/%Y') AS funcionario_data_admissao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            JOIN empresa ON empresa.empresa_id = funcionario.fk_empresa_id
            JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
        WHERE (setor.setor_nome = ?) AND funcionario_status = 0";
        return $this->getDefaultAdapter()->fetchAll($sql, array($setor));
    }

    //   LEFT JOIN ghe ON ghe.ghe_id = alocacao.fk_ghe_id
    public function obter($id) {
        $sql = "SELECT *,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            DATE_FORMAT(funcionario.funcionario_data_admissao, '%d/%m/%Y') AS funcionario_data_admissao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            JOIN empresa ON empresa.empresa_id = funcionario.fk_empresa_id
            LEFT JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
        WHERE funcionario_status IN(0,1) AND funcionario.funcionario_id = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, array($id));
    }

    public function obterPortal($id) {
        $sql = "SELECT *,
            alocacao.`fk_empresa_id` AS empresaAlocadas,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            DATE_FORMAT(funcionario.funcionario_data_admissao, '%d/%m/%Y') AS funcionario_data_admissao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            JOIN empresa ON empresa.empresa_id = funcionario.fk_empresa_id
            LEFT JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
        WHERE funcionario_status IN(0,1) AND funcionario.funcionario_id = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, array($id));
    }

    public function obterFuncionario($id) {
        $sql = "SELECT *
        FROM funcionario
          where funcionario_id = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, array($id));
    }

    public function obterComoObjeto($id) {
        $sql = "SELECT *,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            DATE_FORMAT(funcionario.funcionario_data_admissao, '%d/%m/%Y') AS funcionario_data_admissao,
            (SELECT pp.ppra_item_funcao FROM item_pcmso i JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id WHERE i.item_pcmso_id = alocacao.fk_item_pcmso_id LIMIT 1) AS ppra_item_funcao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            JOIN empresa ON empresa.empresa_id = funcionario.fk_empresa_id
            LEFT JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
        WHERE funcionario_status = 0 AND funcionario.funcionario_id = ?";
        return (object) $this->getDefaultAdapter()->fetchRow($sql, array($id));
    }

    public function obterInformacoesParaRecpcao() {
        $sql = "SELECT *
                FROM agenda
                JOIN empresa ON empresa.`empresa_id` = agenda.`fk_empresa_id`
                INNER JOIN pessoa ON pessoa.`pessoa_id` = agenda.`fk_pessoa_id`
                INNER JOIN `tipoexame` ON `tipoexame`.`tipoexame_id` = `agenda`.`fk_tipoexame_id`";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterInformacoesParaRecepcao($id) {
        $sql = "SELECT *
                FROM agenda
                JOIN empresa ON empresa.`empresa_id` = agenda.`fk_empresa_id`
                INNER JOIN pessoa ON pessoa.`pessoa_id` = agenda.`fk_pessoa_id`
                INNER JOIN `tipoexame` ON `tipoexame`.`tipoexame_id` = `agenda`.`fk_tipoexame_id`
                WHERE agenda.`agenda_id` = {$id}";
        return $this->getDefaultAdapter()->fetchRow($sql);
    }

    public function listarFuncionarioPeloContrato($contrato, $empresa) {
        $sql = "SELECT * FROM funcionario f, `contrato` c, `empresa` e, `pessoa` p
                WHERE f.`fk_contrato_id` = c.`contrato_id`
                AND f.`fk_pessoa_id` = p.`pessoa_id`
                AND f.`fk_empresa_id` = e.`empresa_id`
                AND c.`contrato_id` = {$contrato}
                AND e.`empresa_id` = {$empresa}";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function listarFuncionarioRelatorio($onde) {
        $sql = "SELECT *,(SELECT pp.ppra_item_funcao FROM item_pcmso i JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id WHERE i.item_pcmso_id = a.fk_item_pcmso_id LIMIT 1) AS ppra_item_funcao FROM funcionario f, `pessoa` p, `cargo` c, `funcao` fc,
                `setor` s, `alocacao` a, `contrato` co, `empresa` e
                WHERE f.`fk_pessoa_id` = p.`pessoa_id`
                AND f.`funcionario_id` = a.`fk_funcionario_id`
                AND f.`fk_empresa_id` = e.`empresa_id`
                AND f.`fk_contrato_id` = co.`contrato_id`
                AND a.`fk_cargo_id` = c.`cargo_id`
                AND a.`fk_funcao_id` = fc.`funcao_id`
                AND a.`fk_setor_id` = s.`setor_id`
                {$onde}";

        return $this->getDefaultAdapter()->fetchAll($sql);
    }

     public function obterDadosPelaAlocacao($id) {
        $sql = "SELECT *,
            DATE_FORMAT(pessoa.pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento,
            DATE_FORMAT(funcionario.funcionario_data_admissao, '%d/%m/%Y') AS funcionario_data_admissao,
            (SELECT pp.ppra_item_funcao FROM item_pcmso i JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id WHERE i.item_pcmso_id = alocacao.fk_item_pcmso_id LIMIT 1) AS ppra_item_funcao
        FROM funcionario
            JOIN pessoa ON pessoa.pessoa_id = funcionario.fk_pessoa_id
            JOIN empresa ON empresa.empresa_id = funcionario.fk_empresa_id
            LEFT JOIN alocacao ON alocacao.fk_funcionario_id = funcionario.funcionario_id
            LEFT JOIN setor ON setor.setor_id = alocacao.fk_setor_id
            LEFT JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
            LEFT JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
        WHERE funcionario_status = 0 AND funcionario.funcionario_id = ?";
        return (object) $this->getDefaultAdapter()->fetchRow($sql, array($id));
    }

    public function RelacaoFuncionarios($where) {
        $sql = "SELECT 
                listafuncionarios.*
                FROM (SELECT 
                    DATE_FORMAT(f.funcionario_data_admissao,'%d/%m/%Y') AS funcionario_data_admissao, f.funcionario_id,  
                    f.funcionario_status, f.funcionario_motivo_inativacao, 
                    f.fk_empresa_id, f.fk_contrato_id, p.pessoa_cpf, 
                    p.pessoa_nome, a.alocacao_id, c.cargo_nome, e.empresa_fantasia,
                    (SELECT pp.ppra_item_funcao FROM item_pcmso i JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id WHERE i.item_pcmso_id = a.fk_item_pcmso_id LIMIT 1) AS ppra_item_funcao,
                    a.fk_ppra_item_id,
                    a.fk_item_pcmso_id
                FROM funcionario AS f
                    INNER JOIN pessoa AS p ON p.pessoa_id = f.fk_pessoa_id
                    INNER JOIN alocacao AS a ON a.fk_funcionario_id = f.funcionario_id
                    INNER JOIN cargo AS c ON c.cargo_id = a.fk_cargo_id
                    INNER JOIN empresa AS e ON e.empresa_id = f.fk_empresa_id
                WHERE f.funcionario_status = 0                   
                ) AS listafuncionarios
                {$where}
                ORDER BY listafuncionarios.pessoa_nome ASC, listafuncionarios.ppra_item_funcao ASC, listafuncionarios.empresa_fantasia ASC";
                //util::dump($sql);
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function RelacFunc($where) {
        
        $sql = "SELECT * FROM (
                SELECT 
                    DATE_FORMAT(f.funcionario_data_admissao,'%d/%m/%Y') AS 'funcionario_data_admissao', f.funcionario_id, 
                    f.funcionario_status, f.fk_empresa_id, f.fk_contrato_id, p.pessoa_cpf, p.pessoa_nome, 
                    e.empresa_fantasia, ct.contrato_numero, u.unidade_sigla, c.cargo_nome,
                    a.fk_item_pcmso_id, a.fk_ppra_item_id,
                    CASE  
                        WHEN (f.funcionario_motivo_inativacao != '' OR f.funcionario_status = 1) THEN 'INATIVO'
                        ELSE 'ATIVO' 
                    END AS func_status,
                    (SELECT 
                    DATE_FORMAT(pa.produto_agenda_data_programada, '%d/%m/%Y') AS dt_programada
                        FROM produto_agenda pa
                    JOIN agenda a 
                        ON a.agenda_id = pa.fk_agenda_id
                        AND a.agenda_status = 0
                    WHERE pa.produto_agenda_status = 0
                        AND pa.fk_produto_id = 163
                        AND a.fk_tipoexame_id != 3
                        AND a.agenda_presente_clinico = 1
                        #AND pa.produto_agenda_executado = 1
                        AND a.fk_pessoa_id = p.pessoa_id
                        AND a.fk_contrato_id = {$_SESSION['contrato_id']}
                        AND pa.produto_agenda_data_programada >= '2002'
                    ORDER BY pa.produto_agenda_data_programada DESC 
                    LIMIT 1
                    ) dt_programada,                    
                    (SELECT 
                    DATE_FORMAT(DATE_ADD(ADDDATE(pa.produto_agenda_data_programada, INTERVAL 1 YEAR), INTERVAL -30 DAY), '%d/%m/%Y') dt_prazo
                        FROM produto_agenda pa
                    JOIN agenda a 
                        ON a.agenda_id = pa.fk_agenda_id
                        AND a.agenda_status = 0
                    WHERE pa.produto_agenda_status = 0
                        AND pa.fk_produto_id = 163
                        AND a.fk_tipoexame_id != 3
                        AND a.agenda_presente_clinico = 1
                        #AND pa.produto_agenda_executado = 1
                        AND a.fk_pessoa_id = p.pessoa_id
                        AND a.fk_contrato_id = {$_SESSION['contrato_id']}
                        AND pa.produto_agenda_data_programada >= '2002'
                    ORDER BY pa.produto_agenda_data_programada DESC 
                    LIMIT 1
                    ) dt_prazo,
                    (SELECT 
                    DATE_FORMAT(ADDDATE(pa.produto_agenda_data_programada, INTERVAL 1 YEAR), '%d/%m/%Y') AS dt_proximo_exame
                        FROM produto_agenda pa
                    JOIN agenda a 
                        ON a.agenda_id = pa.fk_agenda_id
                        AND a.agenda_status = 0
                    WHERE pa.produto_agenda_status = 0
                        AND pa.fk_produto_id = 163
                        AND a.fk_tipoexame_id != 3
                        AND a.agenda_presente_clinico = 1
                        #AND pa.produto_agenda_executado = 1
                        AND a.fk_pessoa_id = p.pessoa_id
                        AND a.fk_contrato_id = {$_SESSION['contrato_id']}
                        AND pa.produto_agenda_data_programada >= '2002'
                    ORDER BY pa.produto_agenda_data_programada DESC 
                    LIMIT 1
                    ) dt_proximo_exame,
                    (SELECT 
                    CASE  
                        WHEN ADDDATE(pa.produto_agenda_data_programada, INTERVAL 1 YEAR) < DATE(NOW()) THEN 'Vencido'
                        WHEN DATE_ADD(ADDDATE(pa.produto_agenda_data_programada, INTERVAL 1 YEAR), INTERVAL -30 DAY) < DATE(NOW()) THEN 'A vencer'
                        ELSE 'A realizar' END AS Status
                        FROM produto_agenda pa
                    JOIN agenda a 
                        ON a.agenda_id = pa.fk_agenda_id
                        AND a.agenda_status = 0
                    WHERE pa.produto_agenda_status = 0
                        AND pa.fk_produto_id = 163
                        AND a.fk_tipoexame_id != 3
                        AND a.agenda_presente_clinico = 1
                        #AND pa.produto_agenda_executado = 1
                        AND a.fk_pessoa_id = p.pessoa_id
                        AND a.fk_contrato_id = {$_SESSION['contrato_id']}
                        AND pa.produto_agenda_data_programada >= '2002'
                    ORDER BY pa.produto_agenda_data_programada DESC 
                    LIMIT 1
                    ) periodico_status
                            
                FROM funcionario f
                    INNER JOIN pessoa p ON p.pessoa_id = f.fk_pessoa_id
                    INNER JOIN empresa e ON e.empresa_id = f.fk_empresa_id
                    INNER JOIN contrato ct ON ct.contrato_id = f.fk_contrato_id
                    INNER JOIN unidade u ON u.unidade_id = e.fk_unidade_id
                    INNER JOIN alocacao a ON a.fk_funcionario_id = f.funcionario_id
                    INNER JOIN cargo c ON c.cargo_id = a.fk_cargo_id
                WHERE f.fk_contrato_id = {$_SESSION['contrato_id']}
                AND f.funcionario_status IN(0,1)
                ) as list
                {$where}
                ORDER BY list.pessoa_nome ASC, list.cargo_nome ASC, list.empresa_fantasia ASC";
            #util::dump($sql);
        $dados = $this->getDefaultAdapter()->fetchAll($sql);
        return $dados;
    }

    public function xobter($cols = [], $join = [], $cond = ['funcionario_status = 0'], $order) {
        $parsed = $cols;

        if (count($cols) > 0 && @$cols[0] != '*') {
            $parsed = join(',', array_map(function($val) {
                return "$val";
            }, $cols));
        } else {
            $parsed = '*';
        }

        $cond = join('', array_map(function($val) {
            return " AND {$val} ";
        }, $cond));

        $finalJoin = '';

        foreach ($join as $key => $cval)  {
            $finalJoin .= " LEFT JOIN {$key} ON {$cval} ";
        }

        $sql = "SELECT {$parsed} FROM {$this->_name} {$finalJoin} WHERE 1 = 1 {$cond} {$order}";

        // Util::dump($sql);

        $resultado = $this->getAdapter()->fetchAll($sql);

        return $resultado;
    }
}
