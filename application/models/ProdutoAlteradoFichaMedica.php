<?php

class Application_Model_ProdutoAlteradoFichaMedica extends Zend_Db_Table {

    protected $_name = 'produto_alterado_fichamedica';
    protected $_primary = 'produto_alterado_fichamedica_id';

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'produto_alterado_fichamedica.fk_fichamedica_id ', $limite = '0,99999999999') {
        $comando = "SELECT *
                             FROM produto_alterado_fichamedica
                                       JOIN fichamedica ON fichamedica.fichamedica_id = produto_alterado_fichamedica.fk_fichamedica_id 
                                       JOIN produto ON produto.produto_id =  produto_alterado_fichamedica.fk_produto_id
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

}
