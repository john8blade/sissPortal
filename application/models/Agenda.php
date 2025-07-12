<?php

class Application_Model_Agenda extends Zend_Db_Table
{

    protected $_name = 'agenda';
    protected $_primary = 'agenda_id';

    /**
     * Obtém os produtos dàs agendas faturadas
     * @param int $faturaId Id da fatura
     * @return array Padrão fetch_assoc do PHP
     * @throws Exception
     */
    public static function obterColecaoProdutoDeAgendasFaturadas($faturaId)
    {
        $comando = "SELECT
                            pf.produto_fatura_valor,
                            SUM(pf.produto_fatura_quantidade * pf.produto_fatura_valor_total) AS produto_fatura_valor_total,
                            SUM(pf.produto_fatura_quantidade) AS produto_fatura_quantidade,
                            pf.produto_fatura_quantidade_parcela,
                            pf.produto_fatura_parcela_contexto,
                            pf.produto_fatura_grupo_item_ids,
                            pf.produto_fatura_grupo_faturamento,
                            pf.fk_os_id,
                            produto_nome,
                            produto_id,
                            produto_sigla,
                            produto_codigo_fixo,
                            produto_descricao,
                            contrato_id,
                            contrato_numero,
                            contrato_sufixo_numero,
                            (
                                    SELECT sub_a.agenda_data_exame
                                    FROM agenda AS sub_a, produto_agenda AS sub_pa
                                    WHERE sub_pa.fk_agenda_id = sub_a.agenda_id
                                          AND sub_pa.produto_agenda_id IN(pf.produto_fatura_grupo_item_ids)
                                    LIMIT 1
                            ) AS procedimento_data

                    FROM
                            produto_fatura AS pf
                            JOIN produto AS pr ON pr.produto_id = pf.fk_produto_id
                            JOIN fatura AS f ON f.fatura_id = pf.fk_fatura_id
                            JOIN contrato AS c ON c.contrato_id = f.fk_contrato_id

                    WHERE
                            pf.produto_fatura_status = 0
                            AND pf.fk_fatura_id = ?
                            AND pf.fk_produto_agenda_id IS NOT NULL

                    GROUP BY
                            pf.produto_fatura_valor,
                            pf.produto_fatura_valor_total,
                            pf.produto_fatura_quantidade,
                            pf.produto_fatura_quantidade_parcela,
                            pf.produto_fatura_parcela_contexto,
                            pf.produto_fatura_grupo_item_ids,
                            pf.produto_fatura_grupo_faturamento,
                            pf.fk_os_id,
                            produto_nome,
                            produto_id,
                            produto_sigla,
                            produto_codigo_fixo,
                            produto_descricao,
                            contrato_id,
                            contrato_numero,
                            contrato_sufixo_numero";
        $resultado = array();
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $resultado = $Cnx->fetchAll($comando, array($faturaId));
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

    public function obterPorTipo($mes, $ano)
    {

        $sql = "SELECT
                `tipoexame`.`tipoexame_nome` as MEIO, count(*) as QNT, `tipoexame`.`tipoexame_sigla` AS SGL
              FROM
                agenda
                JOIN empresa
                  ON empresa.`empresa_id` = agenda.`fk_empresa_id`
                JOIN pessoa
                  ON pessoa.`pessoa_id` = agenda.`fk_pessoa_id`
                JOIN `tipoexame`
                  ON `tipoexame`.`tipoexame_id` = `agenda`.`fk_tipoexame_id`
                JOIN contrato
                  ON contrato.`contrato_id` = agenda.`fk_contrato_id`
              WHERE contrato.`contrato_id` = {$_SESSION['empresa']['fk_contrato_id']}
              AND empresa.`empresa_id` = {$_SESSION['empresa']['empresa_id']}
              and month(agenda.`agenda_data_exame`) = {$mes}
              and year(agenda.`agenda_data_exame`) = {$ano}
              GROUP BY `tipoexame`.`tipoexame_nome`";

        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterGraficoPeriodo($ano, $tipoExame)
    {

        $sql = "SELECT
                `tipoexame`.`tipoexame_nome` as MEIO, count(*) as QNT, `tipoexame`.`tipoexame_sigla` AS SGL
              FROM
                agenda
                JOIN empresa
                  ON empresa.`empresa_id` = agenda.`fk_empresa_id`
                JOIN pessoa
                  ON pessoa.`pessoa_id` = agenda.`fk_pessoa_id`
                JOIN `tipoexame`
                  ON `tipoexame`.`tipoexame_id` = `agenda`.`fk_tipoexame_id`
                JOIN contrato
                  ON contrato.`contrato_id` = agenda.`fk_contrato_id`
              WHERE contrato.`contrato_id` = {$_SESSION['empresa']['fk_contrato_id']}
              AND empresa.`empresa_id` = {$_SESSION['empresa']['empresa_id']}
              and year(agenda.`agenda_data_exame`) = {$ano}
                  AND `tipoexame`.`tipoexame_sigla` = '{$tipoExame}'
              GROUP BY `tipoexame`.`tipoexame_nome`";

        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterGraficoProgramadoRealizado($mes, $ano, $tipoExame, $result)
    {

        if ($result == 1) {
            $sql = "SELECT COUNT(*) as QNTP FROM `agenda`
                WHERE agenda.`fk_contrato_id` = {$_SESSION['empresa']['fk_contrato_id']}
                AND agenda.`fk_empresa_id` = {$_SESSION['empresa']['empresa_id']}
                AND MONTH(agenda.`agenda_data_exame`) = {$mes}
                AND YEAR(agenda.`agenda_data_exame`) = {$ano}
                AND `agenda`.`fk_tipoexame_id` = {$tipoExame}
                GROUP BY agenda.`fk_tipoexame_id`;";
        } else {
            $sql = "SELECT COUNT(*) AS QNTR FROM `agenda`
                    WHERE agenda.`fk_contrato_id` = {$_SESSION['empresa']['fk_contrato_id']}
                    AND agenda.`fk_empresa_id` = {$_SESSION['empresa']['empresa_id']}
                    AND MONTH(agenda.`agenda_data_exame`) = {$mes}
                    AND YEAR(agenda.`agenda_data_exame`) = {$ano}
                    AND `agenda`.`fk_tipoexame_id` = {$tipoExame}
                    AND agenda.`agenda_presente_exame` = 1
                    GROUP BY agenda.`fk_tipoexame_id`;";
        }
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterGraficoExamePeriodo($ano, $tipoExame, $mes = 1, $tipo = 1)
    {

        if ($tipo == 1) {
            $sql = "SELECT COUNT(*) AS QNT FROM `agenda`, `produto_agenda`
                        WHERE `agenda`.`agenda_id` = `produto_agenda`.`fk_agenda_id`
                        AND YEAR(`agenda`.`agenda_data_exame`) = {$ano}
                        AND `agenda`.`agenda_presente_exame` = 1
                        AND `agenda`.`fk_empresa_id` = {$_SESSION['empresa']['empresa_id']}
                        AND `agenda`.`fk_contrato_id`= {$_SESSION['empresa']['fk_contrato_id']}
                        AND `produto_agenda`.`fk_produto_id` = {$tipoExame}
                        GROUP BY `produto_agenda`.`fk_produto_id`";
            return $this->getDefaultAdapter()->fetchAll($sql);
        } else {
            $sql = "SELECT COUNT(*) AS QNT FROM `agenda`, `produto_agenda`
                WHERE `agenda`.`agenda_id` = `produto_agenda`.`fk_agenda_id`
                AND YEAR(`agenda`.`agenda_data_exame`) = {$ano}
                AND MONTH(`agenda`.`agenda_data_exame`) = {$mes}
                AND `agenda`.`agenda_presente_exame` = 1
                AND `agenda`.`fk_empresa_id` = {$_SESSION['empresa']['empresa_id']}
                AND `agenda`.`fk_contrato_id`= {$_SESSION['empresa']['fk_contrato_id']}
                AND `produto_agenda`.`fk_produto_id` = {$tipoExame}
                GROUP BY `produto_agenda`.`fk_produto_id`";
            return $this->getDefaultAdapter()->fetchAll($sql);
        }
    }

    public function obterGraficoFaltas($mes, $ano, $fase)
    {

        if ($fase == 1) {
            $sql = "SELECT
                    agenda.`agenda_presente_exame` AS MEIO,
                    COUNT(*) AS QNT
                  FROM
                    agenda
                  WHERE `agenda`.`fk_contrato_id` = {$_SESSION['empresa']['fk_contrato_id']}
                    AND `agenda`.`fk_empresa_id` = {$_SESSION['empresa']['empresa_id']}
                    AND month(agenda.`agenda_data_exame`) = {$mes}
                    AND year(agenda.`agenda_data_exame`) = {$ano}
              GROUP BY agenda.`agenda_presente_exame`";
            return $this->getDefaultAdapter()->fetchAll($sql);
        }
        if ($fase == 2) {
            $sql = "SELECT
                   agenda.`agenda_presente_clinico` AS MEIO,
                    COUNT(*) AS QNT
                  FROM
                    agenda
                  WHERE `agenda`.`fk_contrato_id` = {$_SESSION['empresa']['fk_contrato_id']}
                    AND `agenda`.`fk_empresa_id` = {$_SESSION['empresa']['empresa_id']}
                    AND month(agenda.`agenda_data_clinico`) = {$mes}
                    AND year(agenda.`agenda_data_clinico`) = {$ano}
              GROUP BY agenda.`agenda_presente_clinico`";
            return $this->getDefaultAdapter()->fetchAll($sql);
        }
    }

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'agenda.agenda_id', $limite = '0,99999999999', $imprimirComando = false)
    {
        $comando = "SELECT *,
                                          agenda.fk_empresa_id
                             FROM agenda
                                       JOIN empresa ON empresa.`empresa_id` = agenda.`fk_empresa_id`
                                       JOIN pessoa ON pessoa.`pessoa_id` = agenda.`fk_pessoa_id`
                                       JOIN `tipoexame` ON `tipoexame`.`tipoexame_id` = `agenda`.`fk_tipoexame_id`
                                       JOIN contrato ON contrato.`contrato_id` = agenda.`fk_contrato_id`
                                       JOIN alocacao ON alocacao.alocacao_id = agenda.fk_alocacao_id
                                       #JOIN unidade ON unidade.unidade_id = agenda.fk_unidade_id
                                       LEFT JOIN pessoa_especialidade ON pessoa_especialidade.pessoa_especialidade_id = agenda.fk_pessoa_especialidade_id
                                       LEFT JOIN horario_global ON horario_global.horario_global_id = agenda.fk_horario_global_id
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        echo ($imprimirComando) ? $comando : null;
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function produtoID($id)
    {
        $comando = "SELECT * from produto
                    WHERE produto.produto_id = {$id}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function buscarPessoaAgendamento($idEmpresa, $idPessoa, $dataExame, $tipoExame)
    {

        $comando = "SELECT *,
                        DATE_FORMAT(a.`agenda_data_exame`, '%d/%m/%Y') AS data_exame
                        FROM agenda a
                            INNER JOIN empresa e
                                ON (e.`empresa_id` = a.`fk_empresa_id`)
                            INNER JOIN funcionario f
                        	ON (f.`fk_empresa_id`= e.`empresa_id`)
                            INNER JOIN pessoa p
                                ON (p.`pessoa_id` = f.`fk_pessoa_id`)
                            INNER JOIN tipoexame t
				ON (t.`tipoexame_id` = a.`fk_tipoexame_id`)
                            WHERE e.`empresa_id` = {$idEmpresa}
                                AND p.`pessoa_id` = {$idPessoa}
                                AND a.`agenda_data_exame` = '{$dataExame}'
                                AND a.`fk_tipoexame_id` = {$tipoExame}
                                AND a.`agenda_status` = 0";

        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function recuperarAgendaComOsExamesProgramadosPelosIdsExames($parametroStringIdsExames, $filtroAgenda = '1 = 1', $ordenarPor = 'agenda.agenda_id', $limite = '0,99999999999')
    {
        $retorno = array();
        $comando = "SELECT *,
                                          agenda.fk_empresa_id
                             FROM agenda
                                       JOIN empresa ON empresa.empresa_id = agenda.fk_empresa_id
                                       JOIN pessoa ON pessoa.pessoa_id = agenda.fk_pessoa_id
                                       JOIN tipoexame ON tipoexame.tipoexame_id = agenda.fk_tipoexame_id
                                       JOIN contrato ON contrato.contrato_id = agenda.fk_contrato_id
                                       JOIN alocacao ON alocacao.alocacao_id = agenda.fk_alocacao_id
                                       JOIN funcao ON funcao.funcao_id = alocacao.fk_funcao_id
                                       JOIN cargo ON cargo.cargo_id = alocacao.fk_cargo_id
                             WHERE {$filtroAgenda}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        $resultadoConsulta = $this->getDefaultAdapter()->fetchAll($comando);
        foreach ($resultadoConsulta as $agenda) {
            $item = $agenda;
            $comando = "SELECT *
                                 FROM produto_agenda,
                                           produto
                                 WHERE produto.produto_id =  produto_agenda.fk_produto_id
                                             AND produto_agenda.produto_agenda_status = 0
                                             AND produto.produto_id IN ({$parametroStringIdsExames})
                                             AND produto_agenda.fk_agenda_id = '{$agenda['agenda_id']}'
                                 ORDER BY produto_nome ASC";
            $item['produto_agenda'] = $this->getDefaultAdapter()->fetchAll($comando);
            $retorno[] = $item;
        }
        return $retorno;
    }

    public function historicoFuncionario($contrato, $empresa, $funcionario)
    {
        $sql = "SELECT
                    *
                FROM
                    agenda
                    JOIN empresa ON empresa.`empresa_id` = agenda.`fk_empresa_id`
                    JOIN pessoa ON pessoa.`pessoa_id` = agenda.`fk_pessoa_id`
                    JOIN `tipoexame` ON `tipoexame`.`tipoexame_id` = `agenda`.`fk_tipoexame_id`
                    JOIN contrato ON contrato.`contrato_id` = agenda.`fk_contrato_id`
                    JOIN alocacao ON alocacao.alocacao_id = agenda.fk_alocacao_id
                    LEFT JOIN pessoa_especialidade ON pessoa_especialidade.pessoa_especialidade_id = agenda.fk_pessoa_especialidade_id
                WHERE
                    agenda.agenda_status = '0'
                    AND contrato.`contrato_id` = {$contrato}
                    AND empresa.`empresa_id` = {$empresa}
                    AND `alocacao`.`fk_funcionario_id` = {$funcionario}";
        //echo $sql;
        return $resultadoConsulta = $this->getDefaultAdapter()->fetchAll($sql);
    }

    /**
     * Conta a quantidade de agendamento.
     *
     * @param  string $data data do agendamento (agenda_data_exame). Data deve ser formato americano, exemplo 1989-06-06
     * @param  string $turno turno, valores aceitos são: M,V.
     *
     * @return int numero de agendamentos
     *
     * @throws Exception
     * @author Silas Stoffel <contato@tagtec.com.br>
     */
    public function contarAgendaPorDataTurno($data, $turno, $unidade=NULL)
    {
        $total = 0;
        try {
            $resultado = $this->fetchAll([
                'agenda_status = ?' => 0,
                'turno = ?' => $turno,
                'agenda_data_exame = ?' => $data,
                'fk_unidade_id = ?' => $unidade
            ]);
            $total = $resultado->count();
        } catch (Exception $exc) {
            throw $exc;
        }
        return $total;
    }

    public function contarAgendaDaUnidadePorDataHorario($unidadeID, $data, $horarioID)
    {
        $total = 0;
        try {
            $resultado = $this->fetchAll([
                'agenda_status = ?' => 0,
                'agenda_data_exame = ?' => $data,
                'fk_unidade_id = ?' => $unidadeID,
                'fk_horario_global_id = ?' => $horarioID,
            ]);
            $total = $resultado->count();
        } catch (Exception $exc) {
            throw $exc;
        }
        return $total;
    }

    public function contarAgendaDaUnidadePorData($unidadeID, $data)
    {
        $total = 0;
        try {
            $resultado = $this->fetchAll([
                'agenda_status = ?' => 0,
                'agenda_data_exame = ?' => $data,
                'fk_unidade_id = ?' => $unidadeID,
            ]);
            $total = $resultado->count();
        } catch (Exception $exc) {
            throw $exc;
        }
        return $total;
    }

    public function relacaodebiometria($filtro = "1") {

      $limit = "LIMIT 99999";

      $where = "AND a.agenda_status = 0";

      $join = "JOIN biometria b ON b.fk_agenda_id = a.agenda_id AND b.biometria_status = 0
              JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id AND p.pessoa_status = 0
              JOIN empresa e ON e.empresa_id = a.fk_empresa_id AND e.empresa_status = 0
              JOIN tipoexame t ON t.tipoexame_id = a.fk_tipoexame_id AND t.tipoexame_status = 0";

      $sqlDados = "SELECT COUNT(*) AS TOTAL FROM agenda a $join WHERE $filtro $where $limit";
      $dados = $this->getDefaultAdapter()->fetchRow($sqlDados);

      $sqlLista = "SELECT
                        e.empresa_cnpj AS cnpj, e.empresa_fantasia AS empresa,
                        p.pessoa_cpf AS cpf, p.pessoa_nome AS funcionario,
                        t.tipoexame_nome AS tipo_exame,
                        DATE_FORMAT(b.biometria_data_hora_criacao, '%d/%m/%Y') AS data_biometria,
                        b.biometria_pressao_maxima AS pressao_maxima,
                        b.biometria_pressao_minima AS pressao_minima,
                        b.biometria_peso AS peso,
                        b.biometria_altura AS altura,
                        ROUND((b.biometria_peso / (b.biometria_altura * b.biometria_altura)),2) AS IMC
                      FROM agenda a $join
                      WHERE $filtro $where
                      ORDER BY e.empresa_fantasia, p.pessoa_nome ASC $limit";
      //echo '<pre>',$sqlLista ,'</pre>'; exit;
      $lista = $this->getDefaultAdapter()->fetchAll($sqlLista);

      return array('dados' => $dados, 'lista' => $lista);
    }

    public function agendaDuplicidadeSequente($alocacao_id, $data_exame, $tipoexame_id, $horario_id)
    {
        $dataHoje = date('Y-m-d');
        $comando = "SELECT
                        a.agenda_id, p.pessoa_nome,
                        tp.tipoexame_nome,
                        tp.tipoexame_id, a.turno,
                        a.agenda_data_exame
                    FROM agenda a
                        JOIN tipoexame tp ON tp.tipoexame_id = a.fk_tipoexame_id
                        JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                    WHERE a.fk_alocacao_id = ?
                    AND a.agenda_data_exame BETWEEN ? AND ?
                    AND tp.tipoexame_id = ?
                    AND a.fk_horario_global_id = ?
                    AND a.fk_contrato_id = ?
                    AND a.agenda_status = 0
                    ORDER BY a.agenda_id DESC LIMIT 1;";
        //echo '<pre>',$comando ,'</pre>'; exit;
        $binds = [$alocacao_id, $dataHoje, $data_exame, $tipoexame_id, $horario_id, $_SESSION['contrato_id']];
        $resultado = $this->getDefaultAdapter()->fetchRow($comando, $binds);
        //util::dump($resultado);
        return $resultado;
    }

    public function vagasDisponiveis($unidadeID, $data, $horarioID)
    {
        // obtem vagas totais para a data no horário
        $res = Util::consultaDireta("SELECT SUM(horario_diario_quantidade) AS total FROM horario_diario WHERE fk_horario_global_id = ? AND horario_diario_data = ?", [$horarioID, $data]);
        if (is_null($res['total'])) $res = Util::consultaDireta("SELECT SUM(horario_global_vagas) AS total FROM horario_global WHERE horario_global_id = ?", [$horarioID]);
        $vagas = is_null($res['total']) ? 0 : $res['total'];

        // obtem total de agendados na unidade na data e horario
        $sql = "SELECT COUNT(*) AS total FROM agenda WHERE agenda_status = 0 AND fk_unidade_id = ? AND agenda_data = ? AND fk_horario_global_id = ?";
        $res = Util::consultaDireta($sql, [$unidadeID, $data, $horarioID]);
        $agendados = $res['total'];

        // disp = total - agendados
        $disp = $vagas - $agendados;

        // normaliza num negativos
        return $disp > 0 ? $disp : 0;

    }

    public function verificarAgendamentoPosterior($pessoa_id)
    {
        $sql = "SELECT 
                  agenda.agenda_id, agenda.agenda_data_exame,
                  agenda.fk_contrato_id, contrato.contrato_numero,
                  agenda.fk_empresa_id, agenda.fk_pessoa_id,
                  pessoa.pessoa_nome, pessoa.pessoa_cpf,
                  agenda.fk_tipoexame_id, tipoexame.tipoexame_nome,
                  unidade.unidade_sigla
                FROM agenda
                  JOIN empresa ON empresa.empresa_id = agenda.fk_empresa_id
                  JOIN pessoa ON pessoa.pessoa_id = agenda.fk_pessoa_id
                  JOIN tipoexame ON tipoexame.tipoexame_id = agenda.fk_tipoexame_id
                  JOIN contrato ON contrato.contrato_id = agenda.fk_contrato_id
                  JOIN alocacao ON alocacao.alocacao_id = agenda.fk_alocacao_id
                  JOIN unidade ON unidade.unidade_id = agenda.fk_unidade_id
                  LEFT JOIN pessoa_especialidade ON pessoa_especialidade.pessoa_especialidade_id = agenda.fk_pessoa_especialidade_id
                  LEFT JOIN horario_global ON horario_global.horario_global_id = agenda.fk_horario_global_id
                WHERE agenda.agenda_status = 0 
                  AND agenda.fk_pessoa_id = {$pessoa_id}
                  AND agenda.fk_empresa_id = {$_SESSION['empresa']['empresa_id']}
                  AND agenda.fk_contrato_id = {$_SESSION['contrato_id']}
                  AND agenda.agenda_data_exame >= CURDATE()
                ORDER BY agenda.agenda_data_exame DESC
                LIMIT 1";
        //echo $sql;
        return $resultadoConsulta = $this->getDefaultAdapter()->fetchRow($sql);
    }

    public function buscaGuiaAtendimento($id)
    {

        $comando =  "SELECT 
                        a.agenda_id, a.agenda_data_exame, a.agenda_observacao, 
                        a.turno, p.*, al.*, cg.cargo_nome, e.empresa_razao, 
                        e.empresa_fantasia, c.contrato_numero, c.contrato_sufixo_numero, u.unidade_id,
                        u.unidade_sigla, u.unidade_descricao, te.tipoexame_nome, fl.fila_senha, 
                        fl.fila_cod_prefixo, fl.fila_cod_sufixo, fl.fila_id, 
                        ia.intervalo_atendimento_nome, hg.horario_global_de, hg.horario_global_ate,
                        (SELECT pp.ppra_item_funcao FROM item_pcmso i JOIN ppra_item pp ON pp.ppra_item_id = i.fk_ppra_item_id WHERE i.item_pcmso_id = al.fk_item_pcmso_id LIMIT 1) AS ppra_item_funcao
                    FROM agenda AS a
                        INNER JOIN pessoa AS p ON p.pessoa_id = a.fk_pessoa_id
                        INNER JOIN alocacao AS al ON al.alocacao_id = a.fk_alocacao_id
                        INNER JOIN cargo AS cg ON cg.cargo_id = al.fk_cargo_id
                        INNER JOIN empresa AS e ON e.empresa_id = a.fk_empresa_id
                        INNER JOIN contrato AS c ON c.contrato_id = a.fk_contrato_id
                        INNER JOIN unidade AS u ON u.unidade_id = a.fk_unidade_id
                        INNER JOIN tipoexame AS te ON te.tipoexame_id = a.fk_tipoexame_id
                        LEFT JOIN fila AS fl ON fl.fila_id = a.fk_fila_id
                        LEFT JOIN intervalo_atendimento AS ia ON ia.intervalo_atendimento_id = fl.fk_intervalo_atendimento
                        LEFT JOIN horario_global AS hg ON hg.horario_global_id = a.fk_horario_global_id
                    WHERE a.agenda_status = 0 
                        AND a.agenda_id = {$id}";

        return $this->getDefaultAdapter()->fetchRow($comando);
    }

}