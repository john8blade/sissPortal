<?php

class Util {

    public static function getParam($name, $default = null) {
        return isset($_POST[$name]) ? $_POST[$name] : $default;
    }

    public static function DateAddInterval($data, $interval = '6 MONTH') {
        if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $data))
            $data = implode('-', array_reverse(explode('/', $data)));
        $fetch = Zend_Db_Table::getDefaultAdapter()->fetchRow("SELECT DATE_ADD('{$data}', INTERVAL {$interval}) AS data");
        return $fetch['data'];
    }

    public static function DateBetweenInterval($data, $data1, $data2) {
        if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $data))
            $data = implode('-', array_reverse(explode('/', $data)));
        if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $data1))
            $data1 = implode('-', array_reverse(explode('/', $data1)));
        if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $data2))
            $data2 = implode('-', array_reverse(explode('/', $data2)));
        $fetch = Zend_Db_Table::getDefaultAdapter()->fetchRow("SELECT IF('{$data}' BETWEEN '{$data1}' AND '{$data2}', 1, 0) AS resultado");
        return $fetch['resultado'] == '1' ? true : false;
    }

    public static function eCnpjValido($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) <> 14)
            return false;
        $soma = 0;
        $soma += ($cnpj[0] * 5);
        $soma += ($cnpj[1] * 4);
        $soma += ($cnpj[2] * 3);
        $soma += ($cnpj[3] * 2);
        $soma += ($cnpj[4] * 9);
        $soma += ($cnpj[5] * 8);
        $soma += ($cnpj[6] * 7);
        $soma += ($cnpj[7] * 6);
        $soma += ($cnpj[8] * 5);
        $soma += ($cnpj[9] * 4);
        $soma += ($cnpj[10] * 3);
        $soma += ($cnpj[11] * 2);

        $d1 = $soma % 11;
        $d1 = $d1 < 2 ? 0 : 11 - $d1;

        $soma = 0;
        $soma += ($cnpj[0] * 6);
        $soma += ($cnpj[1] * 5);
        $soma += ($cnpj[2] * 4);
        $soma += ($cnpj[3] * 3);
        $soma += ($cnpj[4] * 2);
        $soma += ($cnpj[5] * 9);
        $soma += ($cnpj[6] * 8);
        $soma += ($cnpj[7] * 7);
        $soma += ($cnpj[8] * 6);
        $soma += ($cnpj[9] * 5);
        $soma += ($cnpj[10] * 4);
        $soma += ($cnpj[11] * 3);
        $soma += ($cnpj[12] * 2);

        $d2 = $soma % 11;
        $d2 = $d2 < 2 ? 0 : 11 - $d2;
        if ($cnpj[12] == $d1 && $cnpj[13] == $d2 && $cnpj != '00000000000000') {
            return true;
        } else {
            return false;
        }
    }

    public static function telefone($tel) {
        $numeroComDdd = str_replace(array('(', ')', ' ', '-'), '', $tel);
        $ddd = substr($numeroComDdd, 0, 2);
        $num = substr($numeroComDdd, 2);
        return '(' . $ddd . ') ' . (strlen($num) > 8 ? substr($num, 0, 5) : substr($num, 0, 4)) . '-' . (strlen($num) > 8 ? substr($num, 5) : substr($num, 4));
    }

    public static function timeDiffByUnix($unix1 = null, $unix2 = null) {
        if ($unix1 && $unix2) {
            $time1 = new DateTime();
            $time2 = new DateTime();
            $time1->setTimestamp($unix1 / 1000);
            $time2->setTimestamp($unix2 / 1000);
            $time3 = $time1->diff($time2);
            return array('time1' => $time1->format('H:i:s'), 'time2' => $time2->format('H:i:s'), 'time3' => $time3->format('%H:%I:%S'));
        } else {
            return array('time1' => null, 'time2' => null, 'time3' => null);
        }
    }

    public static function eregDataBR($dataBR) {
        return preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $dataBR);
    }

    public static function eregDataBD($dataBD) {
        return preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/', $dataBD);
    }

    public static function eregFimDeSemana($data) {
        return (date('N', strtotime(Util::dataBD($data))) > 5);
    }

    public static function obterUtimoDoMes($mes = 'm', $ano = 'Y') {
        $parametroMes = ($mes == 'm') ? date('m') : $mes;
        $parametroAno = ($ano == 'Y') ? date('m') : $ano;
        $ultimo_dia = date("t", mktime(0, 0, 0, $parametroMes, '01', $parametroAno));
        return $ultimo_dia;
    }

    public static function obterVetorAssociativoDosMesesDoAno() {
        $meses = array(
            '01' => "Janeiro",
            '02' => "Fevereiro",
            '03' => "Março",
            '04' => "Abril",
            '05' => "Maio",
            '06' => "Junho",
            '07' => "Julho",
            '08' => "Agosto",
            '09' => "Setembro",
            '10' => "Outubro",
            '11' => "Novembro",
            '12' => "Dezembro"
        );
        return $meses;
    }

    public static function preencherComZero($valor, $colunas) {
        $string = $valor;
        while (strlen($string) < $colunas) {
            $string = '0' . $string;
        }
        return $string;
    }

    public static function calcularPorcentagem($valor, $porcentagem) {
        return (float) $valor * $porcentagem / 100;
    }

    public static function cpf($cpf)
    {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }


    public static function nomeDoMes($mes) {
        switch ((int) $mes) {
            case 1: return 'Janeiro';
            case 2: return 'Fevereiro';
            case 3: return 'Março';
            case 4: return 'Abril';
            case 5: return 'Maio';
            case 6: return 'Junho';
            case 7: return 'Julho';
            case 8: return 'Agosto';
            case 9: return 'Setembro';
            case 10: return 'Outubro';
            case 11: return 'Novembro';
            case 12: return 'Dezembro';
        }
        return 'desconhecido';
    }

    public static function moeda($valor) {
        return number_format($valor, 2, ',', '.');
    }

    public static function geraTimestamp($data) {
        $partes = explode('/', $data);
        return mktime(0, 0, 0, $partes[1], $partes[0], $partes[2]);
    }

    public static function consultaDireta($sql, $bind = array()) {
        return Zend_Db_Table::getDefaultAdapter()->fetchRow($sql, $bind);
    }

    public static function consultaDiretaAll($sql, $bind = array()) {
        return Zend_Db_Table::getDefaultAdapter()->fetchAll($sql, $bind);
    }

    public static function calculaDiasEntre($data_inicial, $data_final) {
        $time_inicial = Util::geraTimestamp($data_inicial);
        $time_final = Util::geraTimestamp($data_final);
        $diferenca = $time_final - $time_inicial;
        $dias = (int) floor($diferenca / (60 * 60 * 24));
        return $dias;
    }

    public static function dump($variavel, $simplificado = true, $morrer = true) {
        echo '<pre>';
        if ($simplificado)
            var_export($variavel);
        else
            var_dump($variavel);
        echo '</pre>';
        if ($morrer)
            exit;
    }

    public static function cnpj($str) {
        $novo = '';
        $novo .= substr($str, 0, 2);
        $novo .= '.' . substr($str, 2, 3);
        $novo .= '.' . substr($str, 5, 3);
        $novo .= '/' . substr($str, 8, 4);
        $novo .= '-' . substr($str, 12, 2);
        return $novo;
    }

    public static function cep($str) {
        $novo = '';
        $novo .= substr($str, 0, 2);
        $novo .= '.' . substr($str, 2, 3);
        $novo .= '-' . substr($str, 5, 3);
        return $novo;
    }

    public static function tel($str) {
        $novo = '';
        $novo .= '(' . substr($str, 0, 2);
        $novo .= ') ' . substr($str, 2, 4);
        $novo .= '-' . substr($str, 6, 4);
        return $novo;
    }

    public static function tel1($str) {
        $novo = '';
        $novo .= '(' . substr($str, 0, 2);
        $novo .= ') ' . substr($str, 2, 5);
        $novo .= '-' . substr($str, 7, 4);
        return $novo;
    }

    public static function excluir($tabela, $id) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $af = $db->update($tabela, array($tabela . "_status" => 2), $tabela . "_id = " . $id);
        return $af > 0 ? true : false;
    }

    public static function dataBD($dataBR) {
        return implode('-', array_reverse(explode('/', $dataBR, 3)));
    }

    public static function dataBR($dataBD) {
        return implode('/', array_reverse(explode('-', $dataBD, 3)));
    }

    public static function validaCampos($dados, $post) {
        $corrigir = array();
        $erros = array();
        foreach ($dados as $campo => $info) {
            if (!isset($post[$campo])) {
                $corrigir[] = $campo;
                $erros[] = 'O campo <b>' . $info['nome'] . '</b> deve ter ao menos uma opção selecionada.';
            } else {
                $valor = $post[$campo];
                if ((!isset($info['vazio']) || !$info['vazio']) && $valor === '') {
                    $corrigir[] = $campo;
                    $erros[] = 'O campo <b>' . $info['nome'] . '</b> não pode ser vazio.';
                } else {
                    switch ($info['tipo']) {
                        case 'data':
                        if (!preg_match('/^([1-9]|0[1-9]|[1,2][0-9]|3[0,1])\/([1-9]|1[0,1,2])\/\d{4}$/', $valor)) {
                            $corrigir[] = $campo;
                            $erros[] = 'O campo <b>' . $info['nome'] . '</b> não é uma data válida.';
                        }
                        break;
                        case 'email':
                        if (!preg_match('/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+$/', $valor)) {
                            $corrigir[] = $campo;
                            $erros[] = 'O campo <b>' . $info['nome'] . '</b> não é um e-mail válido.';
                        }
                        break;
                        case 'numero':
                        if (!is_numeric($valor)) {
                            $corrigir[] = $campo;
                            $erros[] = 'O campo <b>' . $info['nome'] . '</b> não é um número.';
                        }
                        break;
                        case 'texto':
                        default:
                        break;
                    }
                }
            }
        }
        return array('erros' => $erros, 'corrigir' => $corrigir);
    }

    public static function geraSaidaDeValidacao($erros) {
        $retorno = '';
        foreach ($erros as $msg) {
            $retorno .= '&times; ' . $msg . '<br/>';
        }
        return $retorno;
    }

    public static function obterClassificacaoBiometriaIMC($resultado) {
        $retorno = "";
        if ($resultado < 18.4) {
            $retorno = "Baixo Peso";
        } else if ($resultado >= 18.5 && $resultado <= 24.9) {
            $retorno = "Peso Normal";
        } else if ($resultado >= 25 && $resultado <= 29.9) {
            $retorno = "Excesso";
        } else if ($resultado >= 30 && $resultado <= 34.9) {
            $retorno = "Obesidade Grau I";
        } else if ($resultado >= 35 & $resultado <= 39.9) {
            $retorno = "Obesidade Grau II";
        } else if ($resultado >= 40) {
            $retorno = "Obesidade Grau III";
        }
        return $retorno;
    }

    public static function formatarNumeroContrato($numero, $zeroEsquerda = 5) {
        $numeroFormatado = null;
        $quantosDigitos = strlen($numero);
        $adicionarQuantosZeros = $zeroEsquerda - $quantosDigitos;
        if ($quantosDigitos < 5) {
            $numeroFormatado = str_repeat('0', $adicionarQuantosZeros) . $numero;
        }
        return $numeroFormatado;
    }

    public static function mensagemInformativaDeAmbiente() {
        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $app = $config->getOption('resources');
        $host = $app['db']['params']['host'];
        $develop = ($host == 'mysql-develop.mysql.database.azure.com');
        return $develop ? "<div class='row-fluid'><div class='span12 alert alert-danger'><i class='fa fa-warning'></i> <strong>ATENÇÃO!</strong> Você está conectado à base de dados de <b>DESENVOLVIMENTO</b></div></div>" : "";
    }

    public static function alertWarning($texto = "")
    {
        return '<div class="alert alert-warning" style="margin-bottom:0;padding:6px;"><i class="fa fa-warning"></i> ' . $texto . '</div>';
    }

    public static function alertDanger($texto = "")
    {
        return '<div class="alert alert-danger" style="margin-bottom:0;padding:6px;"><i class="fa fa-times"></i> ' . $texto . '</div>';
    }

    public static function horarioGlobal($horario)
    {
        if (!isset($horario['horario1']) || is_null($horario['horario2'])) {
            return "";
        } else {
            $h1 = explode(':', $horario['horario1']);
            array_pop($h1);
            $h1 = implode(':', $h1);
            $h2 = explode(':', $horario['horario2']);
            array_pop($h2);
            $h2 = implode(':', $h2);
            return "{$h1} ~ {$h2}";
        }
    }

    public static function horarioGlobalCompleto($horario)
    {
        if (!isset($horario['horario_global_de']) || is_null($horario['horario_global_ate'])) {
            return "";
        } else {
            $h1 = explode(':', $horario['horario_global_de']);
            array_pop($h1);
            $h1 = implode(':', $h1);
            $h2 = explode(':', $horario['horario_global_ate']);
            array_pop($h2);
            $h2 = implode(':', $h2);
            return "{$h1} ~ {$h2}";
        }
    }

    public static function zeroFill($num, $len = 2)
    {
        return str_pad($num, $len, '0', STR_PAD_LEFT);
    }

    public static function zerarNegativo($num)
    {
        return $num > 0 ? $num : 0;
    }

    public static function usuarioAceitouTermos()
    {
        
        if ($_SESSION['usuario_portal_id']) {
            $aceitou = Zend_Db_Table::getDefaultAdapter()->fetchOne("SELECT usuario_portal_aceitou_termos FROM usuario_portal WHERE usuario_portal_id = ?", $_SESSION['usuario_portal_id']);
            if ($aceitou == '0000-00-00 00:00:00' OR $aceitou == NULL) {                
                return false;
            }else{
                return $aceitou;
            }            
        }
        return false;
    }

    public static function template($arquivo, $substituicoes = [], $nl2br = true)
    {
        $template = file_get_contents($arquivo);
        if ($nl2br) {
            $template = nl2br($template);
        }
        foreach ($substituicoes as $de => $para) {
            $template = str_replace($de, $para, $template);
        }

        return $template;
    }

    public static function formatXmlString($xml) {
        $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
        $token      = strtok($xml, "\n");
        $result     = '';
        $pad        = 0; 
        $matches    = array();
        while ($token !== false) : 
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) : 
              $indent=0;
            elseif (preg_match('/^<\/\w/', $token, $matches)) :
              $pad--;
              $indent = 0;
            elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
              $indent=1;
            else :
              $indent = 0; 
            endif;
            $line    = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
            $result .= $line . "\n";
            $token   = strtok("\n");
            $pad    += $indent;
        endwhile; 
        return $result;
    }

    /**
     * modifica o xml passado como parametro para que contenha nodes referentes as chaves do array passao
     * @link https://stackoverflow.com/questions/1397036/how-to-convert-array-to-simplexml 
    **/
    public static function array_to_xml( $data, &$xml_data, $elementName = 'item') {
        foreach( $data as $key => $value ) {
            if( is_array($value) ) {
                if( is_numeric($key) ){
                    $key = $elementName; //dealing with <0/>..<n/> issues
                }
                $subnode = $xml_data->addChild($key);
                self::array_to_xml($value, $subnode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    public static function gerarDatetime() {
        return date('Y-m-d H:i:s');
    }
}
