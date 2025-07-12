<?php

class CoordenacaoPcmsoBusiness {

    public static $faturamentoMinimoCategoria = 95.00;
    public static $quantidadeEfetivoFaturamentoMinimo = 14;

    const PORCENTAGEM_MONTANTE = 70;

    public function __construct($faturamentoMinimoCategoria = 95.00, $quantidadeEfetivoFaturamentoMinimo = 14) {
        self::$faturamentoMinimoCategoria = $faturamentoMinimoCategoria;
        self::$quantidadeEfetivoFaturamentoMinimo = $quantidadeEfetivoFaturamentoMinimo;
    }

    public function calcularFaturamentoMinimo($valorItem, $efetivo = 1, $faturamentoMinimoCategoria = null) {
        $retornoFaturamento = self::$faturamentoMinimoCategoria;
        $faturamentoMinimoCategoria = ($faturamentoMinimoCategoria == null) ? self::$faturamentoMinimoCategoria : $faturamentoMinimoCategoria;
        $montante = $valorItem * $efetivo;
        /*
         * REGRAS:
         * 1 - EFETIVO MENOR QUE 14 FATURAMENTO MÍNIMO É 95 ( usar valor em: public static $faturamentoMinimoCategoria = 95.00;).
         * 2 - SE 70% DO MONTANTE FOR MENOR QUE 95 O FATURAMENTO MÍNIMO É R$ 95 ( usar valor em: public static $faturamentoMinimoCategoria = 95.00;)
         * 3 - SE 70% DO MONTANTE FOR MAIOR QUE 95R$ ( usar valor em: public static $faturamentoMinimoCategoria = 95.00;) O VALOR FATURAMENTO MINIMO SERÁ 70% DO MONTANTE
         */
        if ((int) $efetivo > self::$quantidadeEfetivoFaturamentoMinimo) {
            $setentaPorCentoMontante = $montante * (self::PORCENTAGEM_MONTANTE / 100);
            if ($setentaPorCentoMontante > $faturamentoMinimoCategoria) {
                $retornoFaturamento = $setentaPorCentoMontante;
            }
        }
        return $retornoFaturamento;
    }

}
