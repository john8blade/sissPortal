/*
 * Este Script requer que você tenha o framework jQuery (incluido nas páginas onde ele também é incluido)
 * para fazer sua utilização.
 */

/**
 
 
 /**
 * Resgata o fornecedor de compras pelo id do fornecedor
 * @param {int} ParamFornecedorId
 * @param {void} AcaoProcessarRetorno
 * @returns {void}
 */
function ResgatarFornecedorComprasPorId(ParamFornecedorId, AcaoProcessarRetorno) {
    var id = parseInt(ParamFornecedorId);
    if (id > 0) {
        $(function () {
            var url = '/ajax/json/servico/obter-fornecedor-de-compra-pelo-id/compra_fornecedor_id/' + ParamFornecedorId;
            $.get(url, null, AcaoProcessarRetorno, 'json');
        });
    }
}

/**
 * Resgata a coleção de produtos de uma ordem de compra
 * @param {int} ParamOrdemCompraId - Id da Ordem de Compra
 * @param {void} AcaoProcessarRetorno - Função ("callback") que trata o retorno da requisição usando ajax.
 * @returns {void}
 */
function ResgatarColecaoProdutosDaOrdemCompraPeloId(ParamOrdemCompraId, AcaoProcessarRetorno) {
    var id = parseInt(ParamOrdemCompraId);
    if (id > 0) {
        $(function () {
            var url = '/ajax/json/servico/obter-colecao-de-produtos-da-ordem-compra-pelo-id/funcionalidade_excluir/1/compra_proposta_item_id/' + ParamOrdemCompraId;
            $.get(url, null, AcaoProcessarRetorno, 'json');
        });
    }
}

/**
 * Resgata a coleção de produtos de uma ordem de pagamento
 * @param {int} ParamOrdemPagamentoId - Id da Ordem de Pagamento
 * @param {void} AcaoProcessarRetorno - Função ("callback") que trata o retorno da requisição usando ajax.
 * @returns {void}
 */
function ResgatarColecaoProdutosDaOrdemPagamentoPeloId(ParamOrdemPagamentoId, AcaoProcessarRetorno) {
    var id = parseInt(ParamOrdemPagamentoId);
    if (id > 0) {
        $(function () {
            var url = '/ajax/json/servico/obter-colecao-de-produtos-da-ordem-pagamento-pelo-id/funcionalidade_excluir/1/ordem_pagamento_id/' + ParamOrdemPagamentoId;
            $.get(url, null, AcaoProcessarRetorno, 'json');
        });
    }
}

function ResgatarColecaoComplementoSituacaoOsPeloNumeroStatus(ParamNumero, AcaoProcessarRetorno) {
    $(function () {
        var url = '/ajax/json/servico/obter-colecao-complemento-situacao-os-pelo-numero-status/numero/' + ParamNumero;
        $.get(url, null, AcaoProcessarRetorno, 'json');
    });
}


function ResgatarColecaoAreaMultiParametroId(ParamAreaId, ParamSetorId, ParamLocald, paramUnidadeId, AcaoProcessarRetorno) {

    var sufixoUrl = '/cntr/' + Math.floor((Math.random() * 1000) + 1);
    if (ParamAreaId) {
        sufixoUrl += '/paramAreaId/' + ParamAreaId;
    }
    if (ParamSetorId) {
        sufixoUrl += '/paramSetorId/' + ParamSetorId;
    }
    if (ParamLocald) {
        sufixoUrl += '/paramLocalId/' + ParamLocald;
    }
    if (paramUnidadeId) {
        sufixoUrl += '/paramUnidadeId/' + paramUnidadeId;
    }
    $(function () {
        var url = '/ajax/json/servico/obter-colecao-areas-com-multi-parametros' + sufixoUrl;
        $.get(url, null, AcaoProcessarRetorno, 'json');
    });
}


function ResgatarColecaoDeSetorPelaUnidade(ParamUnidadeId, AcaoProcessarRetorno) {
    var id = parseInt(ParamUnidadeId);
    if (id > 0) {
        $(function () {
            var url = '/ajax/json/servico/obter-colecao-setor-pelo-id-unidade/paramUnidadeId/' + ParamUnidadeId;
            $.get(url, null, AcaoProcessarRetorno, 'json');
        });
    }
}

/**
 * Resgata a coleção de produtos usando como parametro o id da categoria do produto
 * @param {int} ParamCategoriaId
 * @param {void} AcaoProcessarRetorno
 * @returns {void}
 */
function ResgatarColecaoDeProdutoPeloIdCategoria(ParamCategoriaId, AcaoProcessarRetorno) {
    var id = parseInt(ParamCategoriaId);
    if (id > 0) {
        $(function () {
            var url = ' /ajax/json/servico/obter-colecao-produtos-pelo-id-categoria/paramCategoriaId/' + ParamCategoriaId;
            $.get(url, null, AcaoProcessarRetorno, 'json');
        });
    }
}

/**
 * ### ATENÇÃO###
 * ### MÉTODO OBSOLETO ###
 * Resgata todos os contrato de uma empresa
 * @param {int} ParamEmpresaId
 * @param {void} AcaoProcessarRetorno
 * @returns {void}
 */

function ResgarColecaoDeContratosPeloIdEmpresa(ParamEmpresaId, AcaoProcessarRetorno) {
    var id = parseInt(ParamEmpresaId);
    if (id > 0) {
        $(function () {
            var url = '/ajax/json/servico/obter-colecao-de-contratos-pelo-id-empresa/id/' + ParamEmpresaId;
            $.get(url, null, AcaoProcessarRetorno, 'json');
        });
    }
}

/**
 * Resgata todos os contrato de uma empresa
 * @param {int} ParamEmpresaId
 * @param {void} AcaoProcessarRetorno
 * @returns {void}
 */

function ResgatarColecaoDeContratosPeloIdEmpresa(ParamEmpresaId, AcaoProcessarRetorno) {
    var id = parseInt(ParamEmpresaId);
    if (id > 0) {
        $(function () {
            var url = '/ajax/json/servico/obter-colecao-de-contratos-pelo-id-empresa/id/' + ParamEmpresaId;
            $.get(url, null, AcaoProcessarRetorno, 'json');
        });
    }
}

/**
 * Resgata uma coleção de PPRA's pelo parametros empresa e contrato.
 * @param {int} ParamContratoId - Id do contrato
 * @param {int} ParamEmpresaId - Id da empesa
 * @param {void} AcaoProcessarRetorno - "callback" para tratar o resultado da requisição
 * @returns {void}
 */
function ResgatarColecaoDePpraPeloIdContratoIdEmpresa(ParamContratoId, ParamEmpresaId, AcaoProcessarRetorno) {
    var contratoId = parseInt(ParamContratoId);
    var empresaId = parseInt(ParamEmpresaId);
    if (empresaId > 0 && contratoId > 0) {
        $(function () {
            var url = '/ajax/json/servico/obter-colecao-de-ppra-pelo-contrato-empresa/contratoid/' + contratoId + '/empresaid/' + empresaId;
            $.getJSON(url, null, AcaoProcessarRetorno);
        });
    }
}

/**
 * Resgata os atributos da pessoa pelo CPF.
 * @param {int} ParamCpf - Numero de CPF
 * @param {mixed} callback para processar o retorno
 * @returns {void}
 */
function ResgatarAtributoPessoaPeloCpf(ParamCpf, AcaoProcessarRetorno) {
    if (typeof (ParamCpf) != 'undefined' && ParamCpf.length > 0) {
        $(function () {
            var url = '/ajax/json/servico/resgatar-atributo-pessoa-pelo-cpf/cpf/' + ParamCpf;
            $.getJSON(url, null, AcaoProcessarRetorno);
        });
    }
}

/**
 * Resgata coleção de empresas vinculadas ao contrato mais os subcontrato
 * @param {int} paramContratoId
 * @param {mixed} AcaoProcessarRetorno
 * @returns {void}
 */
function ResgatarColecaoGrupoEmpresasDoContratoMaisSubcontrato(paramContratoId, AcaoProcessarRetorno) {
    if (typeof (paramContratoId) !== 'undefined' && paramContratoId.length > 0) {
        $(function () {
            var url = '/ajax/json/servico/obter-colecao-grupo-empresas-do-contrato-mais-subcontrato/contrato_id/' + paramContratoId;
            $.getJSON(url, null, AcaoProcessarRetorno);
        });
    }
}

/**
 * Resgata coleção de suboperações pela operação
 * @param {int} paramOperacaoId
 * @param {mixed} AcaoProcessarRetorno
 * @returns {void}
 */
function ResgatarColecaoSubOperacoesPelaOperacao(paramOperacaoId, AcaoProcessarRetorno) {
    if (typeof (paramOperacaoId) !== 'undefined' && paramOperacaoId.length !== '') {
        $(function () {
            var url = '/ajax/json/servico/obter-colecao-suboperacoes-pela-operacao/caixa_operacao_id/' + paramOperacaoId;
            $.getJSON(url, null, AcaoProcessarRetorno);
        });
    }
}

/**
 * Resgata Contrato pelo ID
 * @param {int} paramContratoId
 * @param {mixed} AcaoProcessarRetorno
 * @returns {void}
 */
function ResgatarContratoPeloId(paramContratoId, AcaoProcessarRetorno) {
    if (typeof (paramContratoId) !== 'undefined' && paramContratoId.length !== 0) {
        $(function () {
            var url = '/ajax/json/servico/resgatar-cadastro-por-id/tbl/contrato/id/' + paramContratoId;
            $.getJSON(url, null, AcaoProcessarRetorno);
        });
    }
}