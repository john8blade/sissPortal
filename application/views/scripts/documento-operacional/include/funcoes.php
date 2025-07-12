<?php

/**
 * Script para montar/criar o HTML utilizado na geração
 * de Rodapé das páginas em  formato pdf
 */
function obterHtmlDoRodapeEmPdf() {
    /*
     * Ativa rastreio de problemas.
     * Recomendamos ficar como false no
     * Ambiente de produção
     */
    $monitorarScript = false;
    $htmlRodape = null;
    $comando = "SELECT * FROM componentehtml WHERE componentehtml_status = 0 AND componentehtml_nome = 'Rodape Documentos PDFs'";
    try {
        $conexao = Zend_Db_Table::getDefaultAdapter();
        $resultado = $conexao->fetchRow($comando);
        if (is_array($resultado) && count($resultado) > 0) {
            $htmlRodape = $resultado['componentehtml_conteudo'];
        }
    } catch (Exception $e) {
        if ($monitorarScript) {
            echo $e->getMessage();
        }
    }
    return $htmlRodape;
}
