<?php

class Application_Model_AssinaturaDigital extends Zend_Db_Table {

    protected $_name = 'assinatura_digital';
    protected $_primary = 'assinatura_digital_id';

    public static function obterTagImg($size = 100) {
        $AssinaturaDigital = new Application_Model_AssinaturaDigital();
        $assinatura = $AssinaturaDigital->fetchRow(array('assinatura_digital_ativa = ?' => 1, 'assinatura_digital_status = ?' => 0));
        if ($assinatura) {
            $tag = '<img src="data:{type};base64,{hash}" alt="{name}" height="{size}" style="margin: -10px; padding: 0;"/>';
            $tag = str_replace('{size}', $size, $tag);
            $tag = str_replace('{type}', $assinatura['assinatura_digital_tipo'], $tag);
            $tag = str_replace('{hash}', base64_encode($assinatura['assinatura_digital_imagem']), $tag);
            $tag = str_replace('{name}', $assinatura['assinatura_digital_identificador'], $tag);
            return $tag;
        } else {
            return null;
        }
    }

}
