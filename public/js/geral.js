LINK_BRADESCO = "https://www.ib2.bradesco.com.br/emissaoBoletoIB/emissaoBoletoPesquisarLinhaDigitavel.do";

function mascara(obj, fun) {
    v_obj = obj;
    v_fun = fun;
    setTimeout("exemascara()", 1);
}

function exemascara() {
    try {
        v_obj.value = v_fun(v_obj.value);
    } catch (e) {
        alert(e);
    }
}

function mtel(v) {
    v = v.replace(/\D/g, "");
    v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
    v = v.replace(/(\d)(\d{4})$/, "$1-$2");
    return v;
}

function mnum(v) {
    v = v.replace(/[^0-9]/g, "");
    return v;
}

function mdata(v) {
    v = v.replace(/[^0-9]/g, "");
    v = v.replace(/([0-9]{2})([0-9]{2})([0-9]{4})/g, "$1/$2/$3");
    return v;
}

function ucase() {
    var inputs = document.getElementsByTagName('input');
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].id.indexOf('senha') < 0 && inputs[i].className.indexOf('mask-') === -1 && inputs[i].className.indexOf('ignora-ucase') === -1) {
//            inputs[i].onkeyup = function(e) {
//                var key = window.event ? event.keyCode : e.wich;
//                if (key !== 8)
//                    this.value = this.value.toUpperCase();
//            };
            inputs[i].onblur = function (e) {
                this.value = this.value.toUpperCase();
            };
        }
    }
    var textareas = document.getElementsByTagName('textarea');
    for (i = 0; i < textareas.length; i++) {
        if (textareas[i].className.indexOf('mask-') === -1 && inputs[i].className.indexOf('ignora-ucase') === -1) {
//            textareas[i].onkeyup = function(e) {
//                var key = window.event ? event.keyCode : e.wich;
//                if (key !== 8)
//                    this.value = this.value.toUpperCase();
//            };
            textareas[i].onblur = function (e) {
                this.value = this.value.toUpperCase();
            }
        }
    }
    return true;
}

function mval(v) {
    v = v.replace(/\D/g, "");
    v = v.replace(/^([0-9]{3}\.?){3}-[0-9]{2}$/, "$1.$2");
    v = v.replace(/^([0-9]{2})$/, "0.$1");
    v = v.replace(/^([0-9]{1})$/, "0.0$1");
    v = v.replace(/^0([0-9]{1,}\.?)$/, "$1");
    //v = v.replace(/(\d{3})(\d)/g, "$1,$2");
    v = v.replace(/(\d)(\d{2})$/, "$1.$2");
    return parseFloat(v).toFixed(2);
}

function mlet(v) {
    v = v.replace(/[^a-zA-Z]/g, "");
    return v;
}




$(function () {
    $(".mask-telefone").on("keypress", function () {
        mascara(this, mtel);
    });
    $(".mask-numero").on("keypress", function () {
        mascara(this, mnum);
    });
    $(".mask-letras").on("keypress", function () {
        mascara(this, mlet);
    });
    $(".mask-valor").on("keypress", function () {
        mascara(this, mval);
    });
    $(".mask-data").mask("99/99/9999");
    $(".mask-cpf").mask("999.999.999-99");
    $(".mask-cnpj").mask("99.999.999/9999-99");
    $(".mask-cep").mask("99.999-999");
    $(".mask-hora").mask("99:99");

    $('a[rel=tooltip]').tooltip();
});

/**
 * Monta o calendario para uma "div" definida com
 * o atributo "class" igual "componente-calendario".
 * Documentacao e exemplos: http://tarruda.github.io/bootstrap-datetimepicker/
 *
 *
 * HTML Utilizado:
 * <div class="input-append componente-calendario">
 *   <input id="dataExameId" data-format="dd/MM/yyyy" type="text" value="" name="agenda_data_exame" class="mask-data" />
 *   <span class="add-on">
 *      <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
 *    </span>
 * </div>
 */

$(function () {
    $('.componente-calendario').datetimepicker({
        pickTime: false,
        maskInput: false,
        format: 'dd/MM/yyyy',
        language: 'pt-BR',
        orientation: 'auto',
        minViewMode: 0
    });
});



TELEFONES = [];
function adicionaTelefone() {
    var ddi = Util.gebi('ddi').value;
    var tel = Util.gebi('telefone').value.replace(/\D/g, '');
    var ddd = tel.substr(0, 2);
    var num = tel.substr(2);
    var ramal = Util.gebi('ramal').value;
    var telefone = ddi + ' ' + ddd + ' ' + num + ' ' + ramal;
    var liid = 'tel' + TELEFONES.length;
    TELEFONES.push(telefone);
    Util.criaElemento({
        tag: 'li',
        onde: Util.gebi('popup-ul'),
        atribs: {
            id: liid
        }
    });
    Util.criaElemento({
        tag: 'a',
        onde: Util.gebi(liid),
        texto: "+" + ddi + " " + "(" + ddd + ") " + num + " " + ramal,
        atribs: {
            href: 'javascript:void(0);',
            onclick: 'Util.removeElemento(this.parentNode)'
        }
    });
    Util.criaElemento({
        tag: 'input',
        onde: Util.gebi(liid),
        atribs: {
            name: 'telefone[]',
            type: 'hidden',
            value: telefone
        }
    });
    return false;
}

/*
 * Função clonada do SISS
 */
function adicionarOptions(id_select, dados, valor, texto, limpar) {
    var select = document.getElementById(id_select);
    select.innerHTML = limpar ? "" : select.innerHTML;
    var option = document.createElement("option");
    option.value = "";
    option.innerHTML = "---";
    select.appendChild(option);
    for (var i = 0; i < dados.length; i++) {
        var option = document.createElement("option");
        option.value = dados[i][valor];
        option.innerHTML = dados[i][texto];
        select.appendChild(option);
    }
}

/**
 * Silas Stoffel
 * Está função adapta o tamanho do componente "INPUT APPEND" do bootstrap front-end framework.
 * @param {HTMLDivElement} objDiv Objeto com a div que armazena o input append.
 * @param {int} porcentagem [opcional] tamanho em porcentagem do campo de busca. Se nao houver valor especificado é assumido por 96.
 * @param {int} tamanhoDivBtnGroup  [opcional] tamanho da div que armazena o botão "ok" mais icone em formato de setinha.
 * Se não houver valor especificado é assumido 72, que tamanho fixo do boostrap
 * @returns {void}
 */
function ajustarTamanhoComponenteInputAppend(objDiv, porcentagem, tamanhoDivBtnGroup) {
    if (!objDiv || typeof (objDiv) === "undefined")
        return null;
    var largura = parseInt(objDiv.clientWidth);
    tamanhoDivBtnGroup = (typeof (tamanhoDivBtnGroup) === 'undefined' || !tamanhoDivBtnGroup) ? 72 : tamanhoDivBtnGroup;
    var tamanhoMaximoInput = (typeof (porcentagem) === 'undefined' || !porcentagem) ? 96 : porcentagem;
    var novoValor = (largura * tamanhoMaximoInput) / 100;
    var input = objDiv.getElementsByTagName('input');
    if (input[0].type && input[0].type === 'text') {
        input[0].style.width = "" + parseInt(novoValor) - tamanhoDivBtnGroup + "px";
    }
}

/*
 *  AUTO AJUSTA O TAMANHO DOS TEXTAREAS
 */
window.onload = function () {

    var ts = document.getElementsByTagName('textarea');

    for (var i = 0; i < ts.length; i++) {
        var t = ts[i];
        var offset = !window.opera ? (t.offsetHeight - t.clientHeight) : (t.offsetHeight + parseInt(window.getComputedStyle(t, null).getPropertyValue('border-top-width')));

        var resize = function (t) {
            t.style.height = 'auto';
            t.style.height = (t.scrollHeight + offset) + 'px';
        };

        t.addEventListener && t.addEventListener('input', function (event) {
            resize(t);
        });

        t['attachEvent'] && t.attachEvent('onkeyup', function () {
            resize(t);
        });
    }

};

function igualarAlturaPelaClasse(classe) {
    classe = '.' + classe;
    var itens = $(classe);
    var maior = itens[0].offsetHeight;
    for (var i in itens) {
        maior = itens[i].offsetHeight > maior ? itens[i].offsetHeight : maior;
    }
    $(classe).css('min-height', maior + 'px');
}

/**
 *  @author Douglas
 *  @description Envia dados JSON via POST normal [via form.submit()] com campos hidden
 *  @param action string - URL da ação do formulário: /controller/action
 *  @param params object - Um objeto com os dados a serem enviados: { p1: "Batata", p2: "Cebola", p3: 98767, ... }
 */
function post(action, params) {

    var form = document.createElement("form");
    form.setAttribute("method", "post");
    form.setAttribute("action", action);

    for (var key in params) {
        if (params.hasOwnProperty(key)) {
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("value", params[key]);
            hiddenField.setAttribute("type", "hidden");
            hiddenField.setAttribute("name", key);
            form.appendChild(hiddenField);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

function obterFiltros() {
    var params = {};
    var inputs = $(".filtro");
    for (var i in inputs) {
        if (inputs[i] && inputs[i].value !== 'undefined') {
            params[inputs[i].id] = inputs[i].value;
        }
    }
    return params;
}

function filtrar(url) {
    post(url, {filters: JSON.stringify(obterFiltros())});
}

$(() => {

    window.addEventListener('keydown', ev => {
        if ((ev.key === ' ') && (document.activeElement.className.match(/(?:^|\s)mask-data(?:\s|$)/))) {
            $(document.activeElement).val(util.hoje());
        } 
    });

    $('select.pesquisa').select2();

    /* conserta dropdowns quebrados no mobile */
    $('a.dropdown-toggle, .dropdown-menu a').on('touchstart', function(e) {   e.stopPropagation(); });
});