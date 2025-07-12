<?php

class Application_Model_ProdutoContratado extends Zend_Db_Table {

    protected $_name = "produto_contratado";
    protected $_primary = "produto_contratado_id";

    public function buscarCompletoUsandoClausula($clausula = '1 = 1', $ordenarPor = 'produto_contratado.produto_contratado_id', $limite = '0,99999999999', $imprimirComando = false) {
        $comando = "SELECT * 
                                 FROM produto_contratado
                                           JOIN produto ON produto.produto_id = produto_contratado.fk_produto_id
                                           JOIN os ON os.os_id = produto_contratado.fk_os_id
                                           JOIN localentrega ON localentrega.localentrega_id = produto_contratado.fk_localentrega_id
                                           LEFT JOIN precificacao ON precificacao.precificacao_id = produto_contratado.fk_precificacao_id
                                 WHERE  {$clausula}
                                 ORDER BY {$ordenarPor}
                                 LIMIT {$limite} ";
        echo ($imprimirComando) ? $comando : null;
        return $this->getDefaultAdapter()->fetchAll($comando);
    }
    
    public function obterProduto() {
        $comando = "SELECT * FROM produto
                    where produto.fk_categoriadoproduto_id = 2
                    ORDER BY produto.produto_nome ASC";
        
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

}
