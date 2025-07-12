<?php

class Application_Model_Treinamento extends Zend_Db_Table {

    protected $_name = 'treinamento_agenda';
    protected $_primary = 'treinamento_agenda_id';

    public function obterTreinamentosPeloAluno($id) {
        $sql = "SELECT
                    *
                FROM
                    treinamento_agendado ta
                    JOIN pessoa p
                        ON p.pessoa_id = ta.fk_pessoa_id
                        AND p.pessoa_status = 0
                    JOIN treinamento_agenda tag
                        ON tag.treinamento_agenda_id = ta.fk_treinamento_agenda_id
                        AND tag.treinamento_agenda_status = 0
                    JOIN produto pr
                        ON pr.produto_id = tag.fk_produto_id
                        AND pr.produto_status = 0
                WHERE ta.treinamento_agendado_status = 0 AND p.pessoa_id = ?";

        //die($sql . ' ' . $id);

        return $this->getDefaultaDapter()->fetchAll($sql, array((int) $id));
    }

    public function obterPesquisaDaAgenda($agenda = 0) {
        $sql = "SELECT * FROM treinamento_pesquisa WHERE fk_treinamento_agenda_id = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, array($agenda));
    }

    public function obterTrainamentoAgendadoId($treinamento_agendado_id = 0) {
        $sql = "SELECT * FROM treinamento_agendado WHERE treinamento_agendado_id = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, array($treinamento_agendado_id));
    }

    public function verificarAlunoNaAgenda($pessoa, $agenda) {
        $sql = "SELECT *,
                IF(treinamento_agendado.treinamento_agendado_presente = 1, 'PRESENTE', 'AUSENTE') AS presenca
                FROM treinamento_agendado
                JOIN treinamento_agenda ON treinamento_agenda.treinamento_agenda_id = treinamento_agendado.fk_treinamento_agenda_id
                JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                JOIN pessoa ON pessoa.pessoa_id = treinamento_agendado.fk_pessoa_id
                WHERE treinamento_agendado.treinamento_agendado_status = 0
                AND pessoa.pessoa_id = ?
                AND treinamento_agenda.treinamento_agenda_id = ?";
        return count($this->getDefaultAdapter()->fetchAll($sql, array((int) $pessoa, (int) $agenda))) > 0 ? true : false;
    }

    public function obterTreinamentos() {
        $sql = "SELECT * FROM produto
                JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id
                WHERE categoriadoproduto.categoriadoproduto_codigo_fixo = '0003'
                AND categoriadoproduto.categoriadoproduto_status = 0
                AND produto.produto_status = 0";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterAlunosDaAgenda($agenda = 0, $separarPorEmpresa = false) {
        $agenda = (int) $agenda;
        if ($separarPorEmpresa) {
            $sql = "
                SELECT
                    treinamento_agendado.*,
                    contrato.*,
                    treinamento_agenda.*,
                    pessoa.*,
                    empresa.*,
                    IF(treinamento_agendado.treinamento_agendado_presente = 1, 'PRESENTE', 'AUSENTE') AS presenca,
                    treinamento_agendado.fk_contrato_id,
                    fm.fichamedica_liberado_espaco_confinado,
                    fm.fichamedica_liberado_trabalho_altura
                FROM treinamento_agendado
                    JOIN contrato ON contrato.contrato_id = treinamento_agendado.fk_contrato_id
                    JOIN treinamento_agenda ON treinamento_agenda.treinamento_agenda_id = treinamento_agendado.fk_treinamento_agenda_id
                    JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                    LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                    JOIN pessoa ON pessoa.pessoa_id = treinamento_agendado.fk_pessoa_id
                    JOIN empresa ON empresa.empresa_id = treinamento_agendado.fk_empresa_id
                    LEFT JOIN agenda a ON a.agenda_id = treinamento_agendado.fk_treinamento_agenda_id
                    LEFT JOIN fichamedica fm ON (fm.fk_agenda_id = a.agenda_id AND fm.fichamedica_status = 0)
                WHERE treinamento_agendado.treinamento_agendado_status = 0
                    AND treinamento_agenda.treinamento_agenda_id = {$agenda}
                    AND treinamento_agendado.fk_empresa_id = {$_SESSION['empresa']['empresa_id']}
                ORDER BY pessoa.pessoa_nome ASC";

            $saida = $this->getDefaultAdapter()->fetchAll($sql);
        } else {

            $sql = "
                SELECT
                    treinamento_agendado.*,
                    contrato.*,
                    treinamento_agenda.*,
                    pessoa.*,
                    empresa.*,
                    IF(treinamento_agendado.treinamento_agendado_presente = 1, 'PRESENTE', 'AUSENTE') AS presenca,
                    treinamento_agendado.fk_contrato_id,
                    fm.fichamedica_liberado_espaco_confinado,
                    fm.fichamedica_liberado_trabalho_altura
                FROM treinamento_agendado
                    JOIN contrato ON contrato.contrato_id = treinamento_agendado.fk_contrato_id
                    JOIN treinamento_agenda ON treinamento_agenda.treinamento_agenda_id = treinamento_agendado.fk_treinamento_agenda_id
                    JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                    LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                    JOIN pessoa ON pessoa.pessoa_id = treinamento_agendado.fk_pessoa_id
                    JOIN empresa ON empresa.empresa_id = treinamento_agendado.fk_empresa_id
                    LEFT JOIN agenda a ON a.agenda_id = treinamento_agendado.fk_treinamento_agenda_id
                    LEFT JOIN fichamedica fm ON (fm.fk_agenda_id = a.agenda_id AND fm.fichamedica_status = 0)
                WHERE treinamento_agendado.treinamento_agendado_status = 0
                    AND treinamento_agenda.treinamento_agenda_id = {$agenda}
                ORDER BY pessoa.pessoa_nome ASC";

            $saida = $this->getDefaultAdapter()->fetchAll($sql);

            
        }

        return $saida;
    }

    public function obterAprovados($agendamento = 0, $agenda = 0) {

        if ($agenda > 0) {

            $sql = "SELECT *,
                IF(treinamento_agendado.treinamento_agendado_presente = 1, 'PRESENTE', 'AUSENTE') AS presenca,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = usuario.fk_pessoa_id LIMIT 1) AS instrutor,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = a.fk_pessoa_id LIMIT 1) AS instrutor2,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = b.fk_pessoa_id LIMIT 1) AS resptecnico,
                (SELECT pe.pessoa_especialidade_assinatura FROM pessoa_especialidade pe WHERE pe.fk_pessoa_id = usuario.fk_pessoa_id LIMIT 1) AS assinatura,
                (SELECT pe.pessoa_especialidade_assinatura FROM pessoa_especialidade pe WHERE pe.fk_pessoa_id = a.fk_pessoa_id LIMIT 1) AS assinatura_instrutor2,
                (SELECT pe.pessoa_especialidade_assinatura FROM pessoa_especialidade pe WHERE pe.fk_pessoa_id = b.fk_pessoa_id LIMIT 1) AS assinatura_resptecnico
                FROM treinamento_agendado
                JOIN treinamento_agenda ON treinamento_agenda.treinamento_agenda_id = treinamento_agendado.fk_treinamento_agenda_id
                JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                JOIN pessoa ON pessoa.pessoa_id = treinamento_agendado.fk_pessoa_id
                JOIN produto ON produto.produto_id = treinamento_agenda.fk_produto_id
                JOIN usuario ON usuario.usuario_id = treinamento_agenda.fk_usuario_id_instrutor
                LEFT JOIN usuario a ON a.usuario_id = treinamento_agenda.fk_usuario_id_instrutor_2
                LEFT JOIN usuario b ON b.usuario_id = treinamento_agenda.fk_usuario_id_instrutor_responsavel_tecnico
                LEFT JOIN unidade ON unidade.unidade_id = treinamento_agenda.fk_unidade_id
                WHERE treinamento_agendado.treinamento_agendado_status = 0
                AND treinamento_agendado.treinamento_agendado_aprovado = 1
                AND treinamento_agenda.treinamento_agenda_id = {$agenda}
                AND treinamento_agendado.fk_empresa_id = {$_SESSION['empresa']['empresa_id']};";
             
            $saida = $this->getDefaultAdapter()->fetchAll($sql);

        } else{

            $sql = "SELECT *,
                IF(treinamento_agendado.treinamento_agendado_presente = 1, 'PRESENTE', 'AUSENTE') AS presenca,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = usuario.fk_pessoa_id LIMIT 1) AS instrutor,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = a.fk_pessoa_id LIMIT 1) AS instrutor2,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = b.fk_pessoa_id LIMIT 1) AS resptecnico,
                (SELECT pe.pessoa_especialidade_assinatura FROM pessoa_especialidade pe WHERE pe.fk_pessoa_id = usuario.fk_pessoa_id LIMIT 1) AS assinatura,
                (SELECT pe.pessoa_especialidade_assinatura FROM pessoa_especialidade pe WHERE pe.fk_pessoa_id = a.fk_pessoa_id LIMIT 1) AS assinatura_instrutor2,
                (SELECT pe.pessoa_especialidade_assinatura FROM pessoa_especialidade pe WHERE pe.fk_pessoa_id = b.fk_pessoa_id LIMIT 1) AS assinatura_resptecnico
                FROM treinamento_agendado
                JOIN treinamento_agenda ON treinamento_agenda.treinamento_agenda_id = treinamento_agendado.fk_treinamento_agenda_id
                JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                JOIN pessoa ON pessoa.pessoa_id = treinamento_agendado.fk_pessoa_id
                JOIN produto ON produto.produto_id = treinamento_agenda.fk_produto_id
                JOIN usuario ON usuario.usuario_id = treinamento_agenda.fk_usuario_id_instrutor
                LEFT JOIN usuario a ON a.usuario_id = treinamento_agenda.fk_usuario_id_instrutor_2
                LEFT JOIN usuario b ON b.usuario_id = treinamento_agenda.fk_usuario_id_instrutor_responsavel_tecnico
                LEFT JOIN unidade ON unidade.unidade_id = treinamento_agenda.fk_unidade_id
                WHERE treinamento_agendado.treinamento_agendado_status = 0
                AND treinamento_agendado.treinamento_agendado_aprovado = 1
                AND treinamento_agendado.treinamento_agendado_id = {$agendamento}";
            
            $saida = $this->getDefaultAdapter()->fetchAll($sql);

        } 
              

        return $saida;
    }

    public function obterAgendas() {
        $sql = "SELECT *,
                (SELECT COUNT(*) FROM treinamento_agendado WHERE treinamento_agendado.treinamento_agendado_status = 0 AND treinamento_agendado.fk_treinamento_agenda_id = treinamento_agenda.treinamento_agenda_id) AS alunos,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = usuario.fk_pessoa_id) AS instrutor
                FROM treinamento_agenda
                JOIN produto ON produto.produto_id = treinamento_agenda.fk_produto_id
                JOIN usuario ON usuario.usuario_id = treinamento_agenda.fk_usuario_id_instrutor
                JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                WHERE treinamento_agenda.treinamento_agenda_status = 0
                AND treinamento_agenda.treinamento_agenda_data_inicio <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND treinamento_agenda.treinamento_agenda_data_inicio >= CURDATE()
                ORDER BY treinamento_agenda.treinamento_agenda_data_inicio DESC";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterAgendasAntigas() {
        $sql = "SELECT *,
                (SELECT COUNT(*) FROM treinamento_agendado WHERE treinamento_agendado.treinamento_agendado_status = 0 AND treinamento_agendado.fk_treinamento_agenda_id = treinamento_agenda.treinamento_agenda_id) AS alunos,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = usuario.fk_pessoa_id) AS instrutor
                FROM treinamento_agenda
                JOIN produto ON produto.produto_id = treinamento_agenda.fk_produto_id
                JOIN usuario ON usuario.usuario_id = treinamento_agenda.fk_usuario_id_instrutor
                JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                WHERE treinamento_agenda.treinamento_agenda_status = 0
                AND treinamento_agenda.treinamento_agenda_data_inicio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND treinamento_agenda.treinamento_agenda_data_inicio <= CURDATE()
                ORDER BY treinamento_agenda.treinamento_agenda_data_inicio ASC";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterAgenda($agenda = 0) {
        $sql = "SELECT *,
                (SELECT COUNT(*) FROM treinamento_agendado WHERE treinamento_agendado.fk_treinamento_agenda_id = treinamento_agenda.treinamento_agenda_id) AS alunos,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = usuario.fk_pessoa_id) AS instrutor
                FROM treinamento_agenda
                JOIN produto ON produto.produto_id = treinamento_agenda.fk_produto_id
                JOIN usuario ON usuario.usuario_id = treinamento_agenda.fk_usuario_id_instrutor
                JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                WHERE treinamento_agenda.treinamento_agenda_id = {$agenda}"; 
        return $this->getDefaultAdapter()->fetchRow($sql);
    }

    public function obterLocais() {
        $sql = "SELECT * FROM treinamento_local";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterSalasPeloLocal($local = 0) {
        $sql = "SELECT * FROM treinamento_local_sala
                JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_local_sala.fk_treinamento_sala_id
                WHERE treinamento_local_sala.fk_treinamento_local_id = ?";
        return $this->getDefaultAdapter()->fetchAll($sql, array((int) $local));
    }

    public function salvarAvaliacao($agendamento = 0, $nota = null, $indice = null, $presente = null, $aprovado = null) {
        $sql = "UPDATE treinamento_agendado SET
                treinamento_agendado.treinamento_agendado_presente = ?,
                treinamento_agendado.treinamento_agendado_aprovado = ?,
                treinamento_agendado.treinamento_agendado_data_aprovacao = NOW(),
                treinamento_agendado.treinamento_agendado_nota = ?,
                treinamento_agendado.treinamento_agendado_indice_aproveitamento = ?
                WHERE treinamento_agendado.treinamento_agendado_status = 0
                AND treinamento_agendado.treinamento_agendado_id = ?";
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $prepare = $adapter->prepare($sql);
        $prepare->execute(array($presente, $aprovado, $nota, $indice, $agendamento));
        return (int) $prepare->rowCount();
    }

    public function salvarPesquisa($post, $id = 0) {
        $id = (int) $id;
        $adapter = Zend_Db_Table::getDefaultAdapter();
        return $id > 0 ? $adapter->update('treinamento_pesquisa', $post, array('treinamento_pesquisa_id = ?' => $id)) : $adapter->insert('treinamento_pesquisa', $post);
    }

    public function salvarAgendamento($dados = array()) {
        $sql = "INSERT INTO treinamento_agendado (fk_treinamento_agenda_id, fk_pessoa_id, fk_empresa_id, fk_contrato_id, fk_fichamedica_id, liberacao_trabalho_altura, liberacao_espaco_confinado) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $prepare = $adapter->prepare($sql);
        $prepare->execute(array($dados['fk_treinamento_agenda_id'], $dados['fk_pessoa_id'], $dados['fk_empresa_id'], $dados['fk_contrato_id'], $dados['fk_fichamedica_id'], $dados['liberacao_trabalho_altura'], $dados['liberacao_espaco_confinado'] ));
        return $adapter->lastInsertId();
    }

    public function obterAgendasIndex($dt, $treinamento, $instrutor, $hinicio, $htermino, $cargah, $modal) {
        $unidade = $_SESSION['fk_unidade_id'];
        /*
        $clausulaComando = '1 = 1';
        if ($clausula != null) {
            $clausulaComando = $clausula;
        }
        */
        $id_modal = null;
        if ($modal == 'A DISTÂNCIA') {
           $id_modal = 0;
        }elseif ($modal == 'PRESENCIAL') {
           $id_modal = 1;
        }elseif ($modal == 'SEMIPRESENCIAL') {
           $id_modal = 2;
        }

        if ($id_modal != null || $id_modal == 0) {
            $modalidade = " AND treinamento_modalidade LIKE '%{$id_modal}%'";
        } 

        $data = date('Y-m-d');
        ///$data = DateTime::createFromFormat('Y-m-d', $data);
        //$data->add(new DateInterval('P1D')); // 1 dias     
        //$data = $data->format('Y-m-d');

        $sql1 = "SELECT
                    a.*
                FROM 
                    (SELECT *, 
                        (SELECT COUNT(*) FROM treinamento_agendado WHERE treinamento_agendado.treinamento_agendado_status = 0 
                            AND treinamento_agendado.fk_treinamento_agenda_id = treinamento_agenda.treinamento_agenda_id) AS alunos,
                        (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = usuario.fk_pessoa_id) AS instrutor,
                        (SELECT COUNT(*) FROM treinamento_agendado WHERE treinamento_agendado.treinamento_agendado_status = 0 
                            AND treinamento_agendado.fk_treinamento_agenda_id = treinamento_agenda.treinamento_agenda_id 
                            AND treinamento_agendado.fk_empresa_id = {$_SESSION['empresa']['empresa_id']} ) AS alunos_empresa
                        FROM treinamento_agenda
                            JOIN produto ON produto.produto_id = treinamento_agenda.fk_produto_id
                            JOIN usuario ON usuario.usuario_id = treinamento_agenda.fk_usuario_id_instrutor
                            JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                            JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                            LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                            JOIN unidade ON unidade.unidade_id = treinamento_agenda.fk_unidade_id
                        WHERE treinamento_agenda.treinamento_agenda_status = 0
                            AND ( 
                             treinamento_agenda_data_inicio LIKE '%{$dt}%' 
                             AND produto_nome LIKE '%{$treinamento}%'
                             AND pessoa_nome LIKE '%{$instrutor}%'
                             {$modalidade} 
                             AND treinamento_agenda_hora_inicio LIKE '%{$hinicio}%'
                             AND treinamento_agenda_hora_fim LIKE '%{$htermino}%'
                             AND treinamento_agenda_carga_horaria LIKE '%{$cargah}%'
                             )
                            AND unidade_id = '{$unidade}' AND treinamento_agenda_status = 0
                        ORDER BY treinamento_agenda.treinamento_agenda_data_inicio DESC) AS a
                WHERE a.alunos_empresa > 0"; 
                //echo '<per>'.$sql1.'</per>';exit(0); 
        $treinamentosantigos = $this->getDefaultAdapter()->fetchAll($sql1);

        $sql2 = "SELECT *, 
                    (SELECT COUNT(*) FROM treinamento_agendado WHERE treinamento_agendado.treinamento_agendado_status = 0 
                        AND treinamento_agendado.fk_treinamento_agenda_id = treinamento_agenda.treinamento_agenda_id) AS alunos,
                    (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = usuario.fk_pessoa_id) AS instrutor,
                    '' AS alunos_empresa
                FROM treinamento_agenda
                    JOIN produto ON produto.produto_id = treinamento_agenda.fk_produto_id
                    JOIN usuario ON usuario.usuario_id = treinamento_agenda.fk_usuario_id_instrutor
                    JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                    JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                    LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                    JOIN unidade ON unidade.unidade_id = treinamento_agenda.fk_unidade_id
                WHERE treinamento_agenda_data_inicio > '{$data}' 
                    AND 
                    ( treinamento_agenda_data_inicio LIKE '%{$dt}%'  
                    AND produto_nome LIKE '%{$treinamento}%' AND pessoa_nome LIKE '%{$instrutor}%'
                    AND treinamento_agenda_hora_inicio LIKE '%{$hinicio}%'
                    {$modalidade}
                    AND treinamento_agenda_hora_fim LIKE '%{$htermino}%'
                    AND treinamento_agenda_carga_horaria LIKE '%{$cargah}%')
                    AND unidade_id = '4' 
                    AND treinamento_agenda_status = 0
                ORDER BY treinamento_agenda.treinamento_agenda_data_inicio DESC"; 
                //echo '<per>'.$sql2.'</per>';exit(0); 
        $treinamentosnovos = $this->getDefaultAdapter()->fetchAll($sql2);
        foreach ($treinamentosantigos as $a => $antigo) {
            foreach ($treinamentosnovos as $n => $novo) {
                if ($antigo['treinamento_agenda_id'] == $novo['treinamento_agenda_id']) {
                    unset($treinamentosnovos[$n]);
                }
            }            
        }

        $lista = array_merge($treinamentosnovos,$treinamentosantigos);

        return $lista;
    }

    public function obterAgendadoAprovados($agendamento = 0) {
        $sql = "SELECT *,
                IF(treinamento_agendado.treinamento_agendado_presente = 1, 'PRESENTE', 'AUSENTE') AS presenca,
                (SELECT p.pessoa_nome FROM pessoa p WHERE p.pessoa_id = usuario.fk_pessoa_id LIMIT 1) AS instrutor,
                (SELECT pe.pessoa_especialidade_assinatura FROM pessoa_especialidade pe WHERE pe.fk_pessoa_id = usuario.fk_pessoa_id LIMIT 1) AS assinatura
                FROM treinamento_agendado
                JOIN treinamento_agenda ON treinamento_agenda.treinamento_agenda_id = treinamento_agendado.fk_treinamento_agenda_id
                JOIN treinamento_local ON treinamento_local.treinamento_local_id = treinamento_agenda.fk_treinamento_local_id
                LEFT JOIN treinamento_sala ON treinamento_sala.treinamento_sala_id = treinamento_agenda.fk_treinamento_sala_id
                JOIN pessoa ON pessoa.pessoa_id = treinamento_agendado.fk_pessoa_id
                JOIN produto ON produto.produto_id = treinamento_agenda.fk_produto_id
                JOIN usuario ON usuario.usuario_id = treinamento_agenda.fk_usuario_id_instrutor
                LEFT JOIN unidade ON unidade.unidade_id = treinamento_agenda.fk_unidade_id
                WHERE treinamento_agendado.treinamento_agendado_status = 0
                AND treinamento_agendado.treinamento_agendado_data_aprovacao IS NOT null
                AND treinamento_agendado.treinamento_agendado_id = {$agendamento}" ;
        return $this->getDefaultAdapter()->fetchRow($sql);
    }

}
