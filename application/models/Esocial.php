<?php

class Application_Model_Esocial extends Zend_Db_Table {

    public function obterTodosContrato($filtro) {
        /*
        $comando = "SELECT 
                        e.empresa_id,
                        e.empresa_cnpj,
                        e.empresa_razao, 
                        c.contrato_id,
                        c.contrato_numero,
                        etp.esocial_tipoevento_nome,
                        ev.esocial_envio_datahora, 
                        ev.esocial_envio_recibo,
                        ev.esocial_envio_id,
                        p.pessoa_nome
                    FROM esocial_envio ev 
                        JOIN esocial_tipoevento etp ON etp.esocial_tipoevento_id = ev.fk_tipoevento_id
                        LEFT JOIN contratante ct ON ct.fk_empresa_id = ev.fk_empresa_id
                        LEFT JOIN usuario u ON u.usuario_id = ev.fk_usuario_id
                        LEFT JOIN pessoa p ON p.pessoa_id = u.fk_pessoa_id
                        JOIN empresa e ON e.empresa_id = ev.fk_empresa_id
                        JOIN contrato c ON c.contrato_id = ct.fk_contrato_id
                    WHERE ev.esocial_envio_status = 0
                        {$filtro}                                                
                    ORDER BY e.empresa_razao ASC, ev.esocial_envio_datahora DESC";
        */
        $comando = "SELECT 
                    et.esocial_envio_tecnospeed_id,
                    tp.esocial_tipoevento_nome,
                    et.esocial_envio_tecnospeed_datahora,
                    #DATE_FORMAT(et.esocial_envio_tecnospeed_datahora,'%d/%m/%Y') AS 'esocial_envio_tecnospeed_datahora',
                    et.esocial_envio_tecnospeed_disparo_id
                    FROM esocial_envio_tecnospeed et
                    JOIN esocial_tipoevento tp ON tp.esocial_tipoevento_id = et.fk_tipoevento_id 
                        AND tp.esocial_tipoevento_status = 0
                    WHERE et.esocial_envio_tecnospeed_status = 0
                    {$filtro}
                    ORDER BY et.esocial_envio_tecnospeed_datahora DESC
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
