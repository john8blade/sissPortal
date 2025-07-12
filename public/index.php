<?php

// (8) não para, não para, não para não! (8)
ini_set('memory_limit', -1);
ini_set('max_execution_time', -1);
ini_set('session.gc_maxlifetime', 2678400); // a sessão expira em 1 mês :)

// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

if (!file_exists(APPLICATION_PATH . '/configs/application.ini')) {
	die("Olá programador! Você não configurou os dados de conexão.");
}

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
        APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini'
);

include('Util.php');
//include('Seguranca.php');
include('Controller.php');
//require_once 'Log.php';
require('eSocialActions.php');

$application->bootstrap()
        ->run();



/**
 * @access public
 * ATENÇÃO:
 * Tivemos que fazer uma adaptação para gravar
 * os logs de maneira automatizada. Como não conseguimos
 */
//$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
//$options = $bootstrap->getOptions();
//$config = $options['resources']['db']['params'];
//$novaConexao = Zend_Db::factory('Pdo_Mysql', array(
//            'host' => $config['host'],
//            'username' => $config['username'],
//            'password' => $config['password'],
//            'dbname' => $config['dbname']
//        ));
//Log::$adaptadorAcessoDados = $novaConexao;
//Log::$idUsuario = isset($_SESSION["usuario"]) ? $_SESSION["usuario"]["usuario_id"] : null;
//Log::gravar();
//$novaConexao = null;
//unset($bootstrap);
//unset($options);