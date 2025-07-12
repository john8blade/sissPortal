
var ConstrutorUrlConsulta = {
    PrefixoUrl: '/',
    UrlParams: {},
    MapearParametro: function (ObjAtribComValor) {
        if (ObjAtribComValor && typeof (ObjAtribComValor) === 'object')
            this.UrlParams = ObjAtribComValor;
    },
    AdicionarParametro: function (nome, valor) {
        if (nome && typeof (nome) === 'string')
            this.UrlParams[nome] = valor;
    },
    CriarUrl: function () {
        var str = null;
        var colecao = new Array();
        for (var p in this.UrlParams) {
            if (this.UrlParams[p].length > 0) {
                str = p + '=' + encodeURI(this.UrlParams[p]);
                colecao.push(str);
            }
        }
        return  this.PrefixoUrl + colecao.join('&');
    },
    ExecutarChamadaUrl: function () {
        window.location.href = this.CriarUrl();
    }
};

var STO;
(function (window) {

    Util = {
        tempoDeEspera: 15000,
        trabalhando: false,
        /*
         * Atalho para document.getElementById
         */
        gebi: function (id) {
            return document.getElementById(id);
        },
        /*
         * Este método ativa a camada de carregamento e verifica o tempo de espera
         */
        requisicao: function () {
            Util.trabalhando = true;
            STO = setTimeout(function () {
                Util.carregador(1);
                clearTimeout(STO);
                STO = setTimeout(function () {
                    Util.gebi("aguarde").innerHTML = "Sua conexão parece estar lenta...";
                    clearTimeout(STO);
                    STO = setTimeout(function () {
                        window.stop();
                        Util.carregador(0);
                        Util.alerta('warning', '<h4>Atenção!</h4>Desculpe, mas sua requisição demorou demais. Provavelmente sua conexão caiu.<br/>Recarregue a página e tente novamente.');
                        clearTimeout(STO);
                        Util.trabalhando = false;
                    }, Util.tempoDeEspera);
                }, Util.tempoDeEspera);
            }, 500);
            return false;
        },
        /*
         * Este método requer como parâmetro um JSON como no exemplo:
         * Exemplo: {erro: 1, msg: 'Mensagem para o usuário!', js: 'alert("OK")'}
         * Obs.: o JS é opcional.
         */
        feedback: function (json) {
            Util.carregador(0);
            Util.alerta(json.erro === 0 ? 'success' : (json.erro === 1 ? 'danger' : 'warning'), json.msg !== '' ? (json.msg + (json.detalhes ? ('<br/><br/><b>Detalhes Técnicos:</b> ' + json.detalhes) : '')) : 'Nenhuma mensagem foi reportada');
            try {
                var form = Util.gebi('formulario');
                var inps = form.getElementsByTagName('input');
                var sels = form.getElementsByTagName('select');
                for (var i = 0; i < inps.length; i++)
                    inps[i].className = inps[i].className.replace('input-erro', '');
                for (var i = 0; i < sels.length; i++)
                    sels[i].className = sels[i].className.replace('input-erro', '');
                if (json.corrigir) {
                    for (var i = 0; i < json.corrigir.length; i++) {
                        form[json.corrigir[i]].className += ' input-erro';
                    }
                }
            } catch (e) {
            }
            if (json.redirecionar) {
                window.location.href = json.redirecionar;
            }
            if (json.js && json.js !== '') {
                try {
                    eval("(" + json.js + ")");
                } catch (erro) {
                }
            }

            if (json.erro === 0) {
                Util.removeElemento(Util.gebi('btn-salvar'));
                Util.removeElemento(Util.gebi('btn-cancelar'));
            }
            return false;
        },
        /*
         * Exibe/Oculta a camada de carregamento.
         */
        carregador: function (num) {
            Util.gebi("aguarde").innerHTML = "Aguarde...";
            if (num > 0) {
                Util.gebi("sombra").style.display = "block";
                Util.gebi("aguarde").style.display = "block";
            } else {
                clearTimeout(STO);
                Util.gebi("sombra").style.display = "none";
                Util.gebi("aguarde").style.display = "none";
            }
            return this;
        },
        /*
         * Exibe um alerta no layout: Util.alerta('danger', 'Sou um alerta vermelho')
         */
        alerta: function (tipo, texto, ondeid) {
            var scroll = document.body.scrollTop;
            if (ondeid) {
                var alertas = Util.gebi(ondeid);
            } else {
                var alertas = Util.gebi("alertas");
                k = setInterval(function () {
                    /*
                     if (document.body.scrollTop > 0) {
                     document.body.scrollTop -= 20;
                     } else {
                     clearInterval(k);
                     }*/
                    if ($(document).scrollTop()) {
                        $(document).scrollTop($(document).scrollTop() - 20);
                    } else {
                        clearInterval(k);
                    }
                }, 20);
            }
            var html = '<div class="alert alert-' + tipo + ' justo fade in">'
                    + '<button id="botao-fechar-alerta" type="button" class="close" data-dismiss="alert">×</button>'
                    + texto
                    + '</div>';
            alertas.innerHTML = html;
            return this;
        },
        returnSweetAlert: function (json) {            
            if (json.redirecionar) {
                window.location.href = json.redirecionar;
            }
            if (json.js && json.js !== '') {
                console.log('Feedback: JS recebido');
                try {
                    eval("(" + json.js + ")");
                    console.log('Feedback: JS executado');
                } catch (erro) {
                    console.log('Feedback JS Error: ' + erro);
                }
            }

            if (json.erro === 0) {
                Util.removeElemento(Util.gebi('btn-salvar'));
                Util.removeElemento(Util.gebi('btn-cancelar'));
            }
            return false;
        },
        /*
         * Confirma uma exclusão: Util.confirma('Tem certeza?', '/modulo/excluir/id/99')
         */
        confirma: function (texto, url, link) {
            var tr = link.parentNode.parentNode;
            var id = tr.id;
            tr.className = tr.className + ' alert-danger';
            Util.alerta('warning', texto + ' <a target="iframe-receptor" onclick="util.requisicao();Util.removeElemento(Util.gebi(\'' + id + '\'))" href="' + url + '"><i class="icon-ok"></i> Sim</a> | <a href="#" onclick="this.parentNode.parentNode.innerHTML=\'\';util.gebi(\'' + id + '\').className=\'\'"><i class="icon-remove"></i> Não</a>');
            return this;
        },
        /*
         * Apenas redireciona a página com um 'window.location'
         */
        redireciona: function (url) {
            Util.requisicao();
            window.location.href = url;
            return false;
        },
        /*
         * Cria um elemento no documento HTML e o insere em um outro elemento especifico
         * USO: Util.criaElemento({
         *          tag: 'input',
         *          onde: Util.gebi('id-elemento'),
         *          atribs: {
         *              type: 'text',
         *              value: Util.gebi('id-elemento').value
         *          }
         *      });
         */
        criaElemento: function (p) {
            if (p.tag && p.atribs && p.onde) {
                var novo = document.createElement(p.tag);
                for (var i in p.atribs) {
                    novo.setAttribute(i, p.atribs[i]);
                }
                novo.innerHTML = p.texto ? p.texto : "";
                p.onde.insertBefore(novo, p.onde.firstChild);
            }
            return false;
        },
        /*
         * Remove um elemento do DOM HTML.
         * USO: Util.removeElemento(objeto);
         */
        removeElemento: function (obj) {
            try {
                obj.parentNode.removeChild(obj);
            } catch (e) {
            }
            return this;
        },
        /*
         * Exibe um elemento oculto.
         * USO: ...onmouseover="Util.popup('id_div', 'exibir')"...
         */
        popup: function (id, acao) {
            var obj = Util.gebi(id);
            switch (acao) {
                case 'exibir':
                    obj.style.display = 'block';
                    break;
                case 'ocultar':
                    obj.style.display = 'none';
                    break;
            }
        },
        /*
         * Marca vários inputs checkboxes dentro de um elemento.
         * USO: Util.marcarTodos(objeto)
         */
        marcarTodos: function (obj) {
            var n = obj.checked;
            obj.checked = n ? false : true;
            var p = obj.getElementsByTagName('input');
            for (var i = 0; i < p.length; i++) {
                if (p[i] !== obj) {
                    p[i].checked = n;
                }
            }
        },
        /*
         * Compara duas datas
         */
        dataPassada: function (d, m, a) {
            var x = new Date();
            x.setFullYear(a, parseInt(m) - 1, d);
            var hoje = new Date();
            return x < hoje ? true : false;
        },
        /*
         * 
         */
        cpf: function (cpf) {
            return cpf.substr(0, 3) + '.' + cpf.substr(3, 3) + '.' + cpf.substr(6, 3) + '-' + cpf.substr(9, 2);
        },
        // iAJAX
        iAjax: function (id_form, callback) {

        }
    };
    window.util = Util;
})(window);