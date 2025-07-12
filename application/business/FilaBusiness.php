<?php

abstract class FilaBusiness {

    public static $id = null;
    public static $dhCriado = null;
    public static $dhAlterado = null;
    public static $dtAtendimento = null;
    public static $hrAtendimento = null;
    public static $senha = null;
    public static $codPrefixo = null;
    public static $codSufixo = null;
    public static $codControle = null;
    public static $registradoVia = null;
    public static $registradoViaDetalhes = null;
    public static $deptoAtendimento = null;
    public static $unidadeAtendimento = null;
    public static $status = 0;
    public static $intervaloAtendimentoId = null;
    public static $unidadeId = null;
    public static $colecaoItens = array();

    public abstract static function registrar($idReferenciaAgendamento = null, $registradoVia = null, $registradoViaDetalhes = null, $senhaCodPrefixo = null, $senhaCodSufixo = null);

    protected static function _inicializarAtributos() {
        self::$id = (strlen(self::$id) > 0 && (int) self::$id > 0) ? self::$id : null;
        self::$dhAlterado = (strlen(self::$dhAlterado) >= 19 ) ? self::$dhAlterado : null;
    }

    protected static function _eValido() {
        $v = true; //1989-06-06 15:30:31
        if (strlen(trim(self::$dhCriado)) < 19)
            $v = false;
        elseif (strlen(trim(self::$senha)) == 0)
            $v = false;
        elseif (strlen(trim(self::$deptoAtendimento)) == 0)
            $v = false;
        elseif (strlen(trim(self::$dtAtendimento)) < 10)
            $v = false;
        elseif (strlen(trim(self::$unidadeId)) == 0 or ! is_numeric(self::$unidadeId))
            $v = false;
        elseif (strlen(trim(self::$intervaloAtendimentoId)) > 0 && !is_numeric(self::$intervaloAtendimentoId))
            $v = false;
        return $v;
    }
    
    /**
     * Mapeia os atributos do objeto em contexto para array associativo contendo o nome da colunas
     * da tabela fila do banco de dados.
     * @return array 
     */
    protected static function _mapearObjetoFilaParaArrayAssociativo() {
        $c = array(
            'fila_id' => self::$id,
            'fila_dh_criado' => self::$dhCriado,
            'fila_dh_alterado' => self::$dhAlterado,
            'fila_dt_atendimento' => self::$dtAtendimento,
            'fila_hr_atendimento' => self::$hrAtendimento,
            'fila_senha' => self::$senha,
            'fila_cod_prefixo' => self::$codPrefixo,
            'fila_cod_sufixo' => self::$codSufixo,
            'fila_cod_controle' => self::$codControle,
            'fila_registrado_via' => self::$registradoVia,
            'fila_registrado_via_detalhes' => self::$registradoViaDetalhes,
            'fila_depto_atendimento' => self::$deptoAtendimento,
            'fila_unidade_atendimento' => self::$unidadeAtendimento,
            'fila_status' => self::$status,
            'fk_intervalo_atendimento' => self::$intervaloAtendimentoId,
            'fk_unidade_id' => self::$unidadeId
        );
        return $c;
    }

}
