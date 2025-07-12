<?php

/**
 * @author  Silas Stoffel <silas.ugv@hiest.com.br>
 * @category paginador
 * @access public
 * @package view
 *
 * Este arquivo é utilizado NESTE projeto para agilizar o processo de paginação
 * padrão do projeto.
 * Para o funcionamento correte requer:
 * - Que o atributo "$this->itensPaginados" seja uma instancia do objeto Zend_Paginator.
 */
if (isset($this->itensPaginados) && $this->itensPaginados instanceof Zend_Paginator) {
    $this->itensPaginados->setItemCountPerPage(20);
    $this->itensPaginados->setPageRange(20);
}