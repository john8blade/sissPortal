<?php

class Application_Model_Endereco extends Zend_Db_Table {

    protected $_name = "endereco";
    protected $_primary = "endereco_id";
    public $atributos = array(
        'endereco_id' => null,
        'endereco_logradouro' => null,
        'endereco_cep' => null,
        'endereco_bairro' => null,
        'endereco_cidade' => null,
        'endereco_uf' => null,
        'endereco_pais' => null,
    );

}
