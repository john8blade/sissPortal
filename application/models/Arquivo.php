<?php

class Application_Model_Arquivo extends Zend_Db_Table {

    protected $_name = 'arquivo';
    protected $_primary = 'arquivo_id';

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'arquivo.arquivo_id', $limite = '0,99999999999', $imprimirComando = false) {
        $comando = "SELECT *
                             FROM {$this->_name}
                                        JOIN contrato ON contrato.contrato_id = arquivo.fk_contrato_id
                                        JOIN empresa ON empresa.empresa_id = arquivo.fk_empresa_id
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        echo ($imprimirComando) ? $comando : null;
        return $this->getDefaultAdapter()->fetchAll($comando);
    }
}