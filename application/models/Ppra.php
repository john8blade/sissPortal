<?php

class Application_Model_Ppra extends Zend_Db_Table {

    public function obterItensPelaEmpresaEContrato($empresa, $contrato) {
        $sql = "SELECT * 
                FROM ppra 
                    JOIN ppra_item ON ppra_item.fk_ppra_id = ppra.ppra_id AND ppra_item.ppra_item_status = 0
                    JOIN ghe ON ghe.ghe_id = ppra_item.fk_ghe_id AND ghe.ghe_status = 0
                    JOIN setor ON setor.setor_id = ppra_item.fk_setor_id AND setor.setor_status = 0
                    JOIN funcao ON funcao.funcao_id = ppra_item.fk_funcao_id AND funcao.funcao_status = 0
                    JOIN cargo ON cargo.cargo_id = ppra_item.fk_cargo_id AND cargo.cargo_status = 0
                WHERE ppra.ppra_status = 0
                    AND ppra.fk_empresa_id = ?
                    AND ppra.fk_contrato_id = ?";
        return $this->getDefaultAdapter()->fetchAll($sql, array($empresa, $contrato));
    }

    public function obterItemDoPpra($id) {
        $sql = "SELECT * 
                FROM ppra_item 
                    JOIN ghe ON ghe.ghe_id = ppra_item.fk_ghe_id AND ghe.ghe_status = 0
                    JOIN setor ON setor.setor_id = ppra_item.fk_setor_id AND setor.setor_status = 0
                    JOIN funcao ON funcao.funcao_id = ppra_item.fk_funcao_id AND funcao.funcao_status = 0
                    JOIN cargo ON cargo.cargo_id = ppra_item.fk_cargo_id AND cargo.cargo_status = 0
                WHERE ppra_item.ppra_item_status = 0
                    AND ppra_item.ppra_item_id = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, array($id));
    }

}
