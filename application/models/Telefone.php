<?php

class Application_Model_Telefone extends Zend_Db_Table {

    protected $_name = "telefone";
    protected $_primary = "telefone_id";

    public function listarTelefones($usuario) {

        $sql = "SELECT * FROM `telefone` t, `usuario` u, `pessoa` p 
				WHERE p.`pessoa_id` = u.fk_pessoa_id 
				AND p.`pessoa_id` = t.`fk_pessoa_id`
				AND u.`usuario_id` = {$usuario}";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }
    
    public function listarTelefonesEmpresa($empresa) {

        $sql = "SELECT * FROM `telefone` t, `usuario` u, `pessoa` p 
				WHERE u.`usuario_id` = {$usuario}";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

}
