<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initSessao() {
        session_start();
    }

    protected function _initDefines() {
        define('CNPJ', '21969023000189');
        define('TMP', $_SERVER['DOCUMENT_ROOT'] . '/tmp/'); 
    }

    /**
     * Redireciona os usuario não autenticados para
     * página de login
     * 
     */
    protected function _initRedirecionarUsuarioNaoAutenticado() {
        $url = $_SERVER["REQUEST_URI"];
        $enderecos = array(
            '/auth/login/',
            '/auth/login',
            'auth/login',
            'auth/login/',
            'auth/logout/',
            '/auth/logout',
            'auth/logout'
        );
        $enderecoReservado = in_array(strtolower($url), $enderecos);
        if ((!isset($_SESSION['usuario_portal_id']) or count($_SESSION) == 0) && $enderecoReservado == false) {
            header('location:/auth/login/');
        }
    }

}
