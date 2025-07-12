<?php

class FaturamentoImposto {

    public $irrf;
    public $pis;
    public $confins;
    public $csll;
    public $iss;
    public $inss;

    public function __construct($irrf = 0, $pis = 0, $confins = 0, $csll = 0, $iss = 0, $inss = 0) {
        $this->confins = $confins;
        $this->csll = $csll;
        $this->inss = $inss;
        $this->irrf = $irrf;
        $this->iss = $iss;
        $this->pis = $pis;
    }

}
