<?php

class Application_Model_Esocial extends Zend_Db_Table {

    public function obterTodosContrato($contrato_id, $filtro = '') {
        
        $comando = "
            SELECT
                p.pessoa_nome,
                p.pessoa_cpf,
                f.funcionario_motivo_inativacao,
                GROUP_CONCAT(DISTINCT CASE WHEN ev.esocial_tipoevento_nome = 'S2210' THEN CONCAT(ev.evento_id, '|', ev.data_hora) END ORDER BY ev.data_hora SEPARATOR ';') AS eventos_s2210,
                GROUP_CONCAT(DISTINCT CASE WHEN ev.esocial_tipoevento_nome = 'S2220' THEN CONCAT(ev.evento_id, '|', ev.data_hora) END ORDER BY ev.data_hora SEPARATOR ';') AS eventos_s2220,
                GROUP_CONCAT(DISTINCT CASE WHEN ev.esocial_tipoevento_nome = 'S2240' THEN CONCAT(ev.evento_id, '|', ev.data_hora) END ORDER BY ev.data_hora SEPARATOR ';') AS eventos_s2240
            FROM contrato c
            JOIN funcionario f ON f.fk_contrato_id = c.contrato_id AND f.funcionario_status = 0
            JOIN pessoa p ON p.pessoa_id = f.fk_pessoa_id AND p.pessoa_status = 0
            LEFT JOIN (
                -- Eventos da ALOCAÇÃO (S2210, S2240) - SEM ALTERAÇÃO AQUI
                SELECT
                    p.pessoa_id,
                    et.esocial_tipoevento_nome,
                    en.esocial_envio_tecnospeed_datahora AS data_hora,
                    en.esocial_envio_tecnospeed_id AS evento_id
                FROM contrato c
                JOIN funcionario f ON f.fk_contrato_id = c.contrato_id AND f.funcionario_status = 0
                JOIN pessoa p ON p.pessoa_id = f.fk_pessoa_id AND p.pessoa_status = 0
                JOIN alocacao al ON al.fk_funcionario_id = f.funcionario_id
                JOIN esocial_detalhe_envio ed ON ed.fk_alocacao_id = al.alocacao_id AND ed.esocial_detalhe_envio_status = 0
                JOIN esocial_envio_tecnospeed en ON en.esocial_envio_tecnospeed_id = ed.fk_esocial_envio_tecnospeed_id AND en.esocial_envio_tecnospeed_status = 0
                JOIN esocial_tipoevento et ON et.esocial_tipoevento_id = ed.fk_esocial_tipoevento_id_id AND et.esocial_tipoevento_status = 0
                WHERE en.fk_contrato_id = {$contrato_id}
                AND c.contrato_status = 0
                AND et.esocial_tipoevento_nome IN ('S2210', 'S2240')

                UNION ALL

                -- Eventos da AGENDA (S2220)
                SELECT
                    p.pessoa_id,
                    'S2220' AS esocial_tipoevento_nome,
                    a.agenda_data_clinico AS data_hora,
                    COALESCE(CAST(en.esocial_envio_tecnospeed_id AS CHAR), 'Sem esocial_envio_tecnospeed_id') AS evento_id
                FROM contrato c
                JOIN funcionario f ON f.fk_contrato_id = c.contrato_id AND f.funcionario_status = 0
                JOIN pessoa p ON p.pessoa_id = f.fk_pessoa_id AND p.pessoa_status = 0
                JOIN agenda a ON a.fk_pessoa_id = p.pessoa_id AND a.agenda_status = 0 AND a.agenda_presente_clinico = 1
                LEFT JOIN esocial_detalhe_envio ed ON ed.fk_agenda_id = a.agenda_id AND ed.esocial_detalhe_envio_status = 0
                LEFT JOIN esocial_envio_tecnospeed en ON en.esocial_envio_tecnospeed_id = ed.fk_esocial_envio_tecnospeed_id
                                                    AND en.esocial_envio_tecnospeed_status = 0
                                                    AND en.fk_contrato_id = {$contrato_id}
                LEFT JOIN esocial_tipoevento et ON et.esocial_tipoevento_id = ed.fk_esocial_tipoevento_id_id
                WHERE c.contrato_id = {$contrato_id} -- Filtro principal pelo contrato do funcionário
                AND c.contrato_status = 0

            ) AS ev ON ev.pessoa_id = p.pessoa_id
            WHERE c.contrato_id = {$contrato_id}
            AND c.contrato_status = 0
            {$filtro}
            GROUP BY
                p.pessoa_nome,
                p.pessoa_cpf
            ORDER BY
                p.pessoa_nome ASC;
        ";
        $dados = $this->getDefaultAdapter()->fetchAll($comando);
        return $dados;
    }

    public function obterTodosEventos() {
        $comando = "SELECT *
                    FROM esocial_tipoevento etp 
                    WHERE etp.esocial_tipoevento_status = 0
                    ORDER BY etp.esocial_tipoevento_nome ASC";
        $dados = $this->getDefaultAdapter()->fetchAll($comando);
        return $dados;
    }

}
