<?php

class Endereco {

    public $id;
    public $logradouro;
    public $cep;
    public $numero;
    public $complemento;
    public $cidade;
    public $bairro;
    public $uf;
    public $pais;

    public function __construct($id = null, $logradouro = null, $complemento = null, $numero = null, $bairro = null, $cidade = null, $uf = null, $pais = null) {
        $this->id = $id;
        $this->logradouro = $logradouro;
        $this->complemento = $complemento;
        $this->numero = $numero;
        $this->bairro = $bairro;
        $this->cidade = $cidade;
        $this->uf = $uf;
        $this->pais = $pais;
    }

}
