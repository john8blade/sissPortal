$(function() {

    // FILTRO

    $(".filtro").on("change", function() { filtrar('/agenda'); });

    // ADICIONAR

    var div = document.getElementById('lista-unidades');
    var goto = (i) => window.location.href = `/agenda/adicionar/unidade/${i}`;
    var error = (s) => div.innerHTML = `<div class="alert alert-danger">${s}</div>`;

    $("#btn-add").click(function() {

        $("#modalUnidades").modal();

        div.innerHTML = 'Carregando...';

        fetch('/ajax/json/servico/obter-unidades')

        .then(p => p.json()).then(p => {

            if ('error' in p) { error(p.error); }

            else {

                var table = `<table class="table table-bordered table-hover">`;
                table += `<tr><th>#</th><th>Nome</th><th>Telefone</th><th>Email</th></tr>`;

                p.map(item => {

                    var desc = { nome: '', telefone: '', email: '' };

                    try {

                        var json = JSON.parse(item.unidade_descricao);
                        if (json && 'nome' in json) desc = json;

                    } catch(e) { }

                    var styl = `font-size: 110%;`;
                    var href = `/agenda/adicionar/unidade/${item.unidade_id}`;
                    var link = `<a class="btn btn-mini btn-primary" href="${href}"><i class="fa fa-chevron-right"></i> ${item.unidade_sigla}</a>`;
                    table += `<tr><td style="width:1px;">${link}</td><td style="${styl}">${desc.nome}</td><td style="${styl}">${desc.telefone}</td><td style="${styl}">${desc.email}</td></tr>`;

                });

                table += '</table>';
                div.innerHTML = table;

            }

        }).catch(e => error(e));

    });

});