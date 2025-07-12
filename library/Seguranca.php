<?php

class Seguranca {

    public static function verificaCredenciais($cont, $acao) {
        /*
         * - Alterado por: Silas Stoffel
         * - Alterado em: 12-11-2013
         * - Qual era o problema: Anteriormente no sistema havia um problema
         * na separação URLs publicas ("para usuários autenticados") e privadas, com isso o sistema reportava
         * mensagens de técnicas desagradáveis.
         * - O que foi feito: Ainda continua a existir as URLs públicas ("para usuários autenticados"). 
         * Mesmo que sejam URLs publicas requer que o usuário esteja autenticado, por isso foi necessário
         * fazer uma verificação se existe o usuário autenticado neste método. 
         */
        if (isset($_SESSION['usuario']) == false) {
            header("Location: /auth/login/");
            exit;
        }
        // fim manutenção

        $publicos = array('/index', '/index/', '/index/index', '/index/index/', '/index/acesso-negado', '/ajax/excluir', '/ajax/json', '/ajax/html', '/usuario/alterar-senha');
        $cont = strtolower($cont);
        $acao = strtolower($acao);
        $url = '/' . $cont . '/' . $acao;
        if ($acao == 'salvar')
            $url = $_SESSION['passos'][count($_SESSION['passos']) - 1];
        if (!in_array($url, $publicos) && !in_array($url, $_SESSION['acesso'])) {
            header("Location: /index/acesso-negado/c/{$cont}/a/{$acao}");
            exit;
        }
    }

}
