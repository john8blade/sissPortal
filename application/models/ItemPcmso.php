<?php

class Application_Model_ItemPcmso extends Zend_Db_Table {

    protected $_name = "item_pcmso";
    protected $_primary = "item_pcmso_id";

    public function buscarColecaoItensDoPcmsoVigente($contratoId, $empresaId) {
        $comando = "SELECT *
                    FROM {$this->_name} ip
                         JOIN (
                                SELECT *
                                FROM pcmso p
                                WHERE p.fk_empresa_id = ?
                                      AND p.fk_contrato_id = ?
                                      AND p.pcmso_data_validade > DATE(NOW())
                                      AND p.pcmso_status = 0
                                ORDER BY pcmso_data_validade DESC
                                LIMIT 1
                         ) JuncaoVirtual ON (JuncaoVirtual.pcmso_id = ip.fk_pcmso_id)
                         JOIN cargo c ON(c.cargo_id = ip.fk_cargo_id AND c.cargo_status = 0)
                         JOIN funcao f ON(f.funcao_id = ip.fk_funcao_id AND f.funcao_status = 0)
                         JOIN setor s ON( ip.fk_setor_id = s.setor_id AND s.setor_status = 0)
                         LEFT JOIN ghe g ON(ip.fk_ghe_id = g.ghe_id AND g.ghe_status = 0)
                         JOIN ppra_item ppi ON(ppi.ppra_item_id = ip.fk_ppra_item_id AND ppi.ppra_item_status = 0)
                         JOIN ppra pr ON(pr.ppra_id = ppi.fk_ppra_id AND pr.ppra_status = 0)
                    WHERE ip.item_pcmso_status = 0
                          AND JuncaoVirtual.fk_contrato_id = ?
                          AND JuncaoVirtual.fk_empresa_id = ?
                          AND JuncaoVirtual.pcmso_data_validade > DATE(NOW())
                          AND JuncaoVirtual.pcmso_status = 0
                    ORDER BY s.setor_nome, c.cargo_nome, f.funcao_nome";

        $parametros = array($empresaId, $contratoId, $contratoId, $empresaId);
        $resultado = array();
        try {
            $resultado = $this->getDefaultAdapter()->fetchAll($comando, $parametros);
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultado;
    }
    
    public function buscarColecaoItensDoPcmsoMaisAtual($contratoId, $empresaId) {
        $comando = "SELECT *
                    FROM {$this->_name} ip
                         JOIN (
                                SELECT *
                                FROM pcmso p
                                WHERE p.fk_empresa_id = ?
                                      AND p.fk_contrato_id = ?
                                        AND p.pcmso_status = 0
                                ORDER BY pcmso_data_validade DESC
                                LIMIT 1
                         ) JuncaoVirtual ON (JuncaoVirtual.pcmso_id = ip.fk_pcmso_id)
                         JOIN cargo c ON(c.cargo_id = ip.fk_cargo_id AND c.cargo_status = 0)
                         JOIN funcao f ON(f.funcao_id = ip.fk_funcao_id AND f.funcao_status = 0)
                         JOIN setor s ON( ip.fk_setor_id = s.setor_id AND s.setor_status = 0)
                         LEFT JOIN ghe g ON(ip.fk_ghe_id = g.ghe_id AND g.ghe_status = 0)
                         JOIN ppra_item ppi ON(ppi.ppra_item_id = ip.fk_ppra_item_id AND ppi.ppra_item_status = 0)
                         JOIN ppra pr ON(pr.ppra_id = ppi.fk_ppra_id AND pr.ppra_status = 0)
                    WHERE ip.item_pcmso_status = 0
                          AND JuncaoVirtual.fk_contrato_id = ?
                          AND JuncaoVirtual.fk_empresa_id = ?
                          AND JuncaoVirtual.pcmso_status = 0
                    ORDER BY s.setor_nome, c.cargo_nome, f.funcao_nome";

        $parametros = array($empresaId, $contratoId, $contratoId, $empresaId);
        $resultado = array();
        
        try {
            $resultado = $this->getDefaultAdapter()->fetchAll($comando, $parametros);
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultado;
    }

}
