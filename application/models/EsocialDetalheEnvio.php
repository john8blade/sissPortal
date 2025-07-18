<?php

class Application_Model_EsocialDetalheEnvio extends Zend_Db_Table {
    protected $_name = 'esocial_detalhe_envio';
    protected $_primary = 'esocial_detalhe_envio_id';

    public function dadosEventosEnviado($id, $tipo) {

        $contrato_id = $_SESSION['contrato_id'];

        $sql = "SELECT 
                    ed.esocial_detalhe_envio_id, al.alocacao_id,
                    ed.fk_agenda_id, e.empresa_id, c.contrato_id, c.contrato_numero,
                    et.esocial_tipoevento_nome, e.empresa_razao, 
                    en.esocial_envio_tecnospeed_disparo_id,
                    en.esocial_envio_tecnospeed_datahora,
                    e.empresa_fantasia,
                    CASE 
                        WHEN ag.agenda_id > 0 THEN p.pessoa_nome
                        ELSE ps.pessoa_nome
                    END AS 'pessoa_nome',
                    CASE 
                        WHEN ag.agenda_id > 0 THEN p.pessoa_cpf
                        ELSE ps.pessoa_cpf
                    END AS 'pessoa_cpf'
                FROM esocial_detalhe_envio ed
                JOIN esocial_envio_tecnospeed en ON en.esocial_envio_tecnospeed_id = ed.fk_esocial_envio_tecnospeed_id
                LEFT JOIN empresa e ON e.empresa_id = ed.fk_empresa_id
                LEFT JOIN contrato c ON c.contrato_id = ed.fk_contrato_id
                LEFT JOIN agenda ag ON ag.agenda_id = ed.fk_agenda_id
                LEFT JOIN pessoa p ON p.pessoa_id = ag.fk_pessoa_id
                LEFT JOIN alocacao al ON al.alocacao_id = ed.fk_alocacao_id
                LEFT JOIN funcionario f ON f.funcionario_id = al.fk_funcionario_id
                LEFT JOIN pessoa ps ON ps.pessoa_id = f.fk_pessoa_id
                JOIN esocial_tipoevento et ON et.esocial_tipoevento_id = ed.fk_esocial_tipoevento_id_id
                WHERE ed.esocial_detalhe_envio_status = 0
                    AND en.esocial_envio_tecnospeed_id = {$id}
                    AND en.fk_contrato_id = {$contrato_id}";

        $dados = $this->getDefaultAdapter()->fetchAll($sql);
       
        return $dados;
    }
}