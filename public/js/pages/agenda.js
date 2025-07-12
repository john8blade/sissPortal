/**
 * Script com depencia do jQuery
 */
var UtilitariosAjax = new UtilitariosAjax();

function ajaxResgatarAtributosFuncionario() {
  var data = new Date();
  var url = "/ajax/json/servico/obter-funcionario-por-id-pessoa-empresa/";
  var imprimirRetorno = function(resposta) {
    if (resposta && resposta.length > 0) {
      ajaxResgataExamesDoPcmso();
    }
  };
  $.post(
    url,
    {
      pessoaId: funcionarioId,
      empresaId: empresaId
    },
    imprimirRetorno,
    "json"
  );
}

function renderizarModalInformativo() {
  $("#modalInformacao").modal("show");
}

function listarDiasAtendimento() {
  var mes = $("#disponibilidade-mes").val();
  var ano = $("#disponibilidade-ano").val();
  var unidadeId = $("#fk_unidade_id").val();
  UtilitariosAjax.obterDisponiblidadeMensalDeVagasParaAgendamento(
    mes,
    ano,
    unidadeId
  );
}

function ajaxResgatarAtributosFuncionario() {
  var funcionarioId = $("#funcionario_id").val();
  if (funcionarioId === "") return;
  var empresaId = $("#empresa_id").val();
  var data = new Date();
  var txt_idade = new Date();
  txt_idade.setDate(txt_idade.getDate() + 62);
  var diferenca =
    txt_idade.getFullYear() * 12 +
    txt_idade.getMonth() -
    (data.getFullYear() * 12 + data.getMonth());
  var url = "/ajax/json/servico/obter-funcionario-por-id-pessoa-empresa/";
  var imprimirRetorno = function(resposta) {
    if (resposta && resposta.length > 0) {
      $("#setor_id").val(resposta[0].setor_id);
      $("#cargo_id").val(resposta[0].cargo_id);
      $("#funcao_id").val(resposta[0].funcao_id);
      $("input[name=pessoa_cpf]").attr("value", resposta[0].pessoa_cpf);
      $("input[name=pessoa_identidade]").attr(
        "value",
        resposta[0].pessoa_identidade
      );
      $("input[name=funcionario_matricula]").attr(
        "value",
        resposta[0].funcionario_matricula
      );
      $("input[name=pessoa_data_nascimento]").attr(
        "value",
        resposta[0].pessoa_data_nascimento
      );
      $("input[name=alocacao_id]").attr("value", resposta[0].alocacao_id);
      $("input[name=fk_pessoa_id]").attr("value", resposta[0].fk_pessoa_id);
      $("input[name=cargo_nome]").attr("value", resposta[0].cargo_nome);
      $("input[name=funcao_nome]").attr("value", resposta[0].funcao_nome);
      ajaxResgataExamesDoPcmso();
    }
  };
  $.post(
    url,
    {
      pessoaId: funcionarioId,
      empresaId: empresaId
    },
    imprimirRetorno,
    "json"
  );
}

function UtilitariosAjax() {
  this.obterAlocacaoPorId = function(alocacaoId) {
    $(
      "#pessoa_cpf",
      "#pessoa_identidade",
      "#funcionario_matricula",
      "#pessoa_data_nascimento",
      "#funcionario_data_admissao",
      "#setor_nome",
      "#cargo_nome",
      "#funcao_nome",
      "#fk_pessoa_id"
    ).val("");
    var onde = "/ajax/json/servico/obter-alocacao-pelo-id/";
    interpretarRetorno = function(itemResposta) {
      if (itemResposta.alocacao) {
        if (itemResposta.quantidadeItemAlocado > 0) {
          var alocacao = itemResposta.alocacao;
          var tpexame = $("#fk_tipoexame_id").val();
          var atualizaralocacao = false;
          if (alocacao.fk_ppra_item_id === null || alocacao.fk_item_pcmso_id === null) {
            atualizaralocacao = true;
          }
          //console.log(alocacao.fk_ppra_item_id === null);
          var text = '';
          if (alocacao.funcionario_matricula === "") {
             text += ' <b>Matrícula eSocial</b>';
          }
          if (alocacao.funcionario_data_admissao === "" || alocacao.funcionario_data_admissao === "00/00/0000") {
            if (text.length > 0) {
              text += ' e <b>Data de Admissão</b> ';
            }else{
              text += ' <b>Data de Admissão</b> ';
            }
          }
          if (tpexame != 1 && atualizaralocacao == true) {
            if (text.length > 0) {
              text += ' e <b>Alocação do Funcionário</b> ';
            }else{
              text += ' <b>Alocação do Funcionário</b> ';
            }
          }
     
          if (text.length > 0 ) {
            
            swal({
              html: true,
              title: "Atenção",
              type: 'info',              
              text: `Favor atualizar a `+ text +` na aba <b>Funcionários</b>.`,                  
              confirmButtonText: "OK",
              cancelButtonText: "Cancelar",
              showCancelButton: false,
              closeOnConfirm: true,
              showLoaderOnConfirm: false
              },
              function(){
                window.location.href = "/funcionario/alterar/id/"+alocacao.funcionario_id;
            });

          } else {

              $("#pessoa_cpf").val(alocacao.pessoa_cpf);
              $("#pessoa_identidade").val(alocacao.pessoa_identidade);
              $("#funcionario_matricula").val(alocacao.funcionario_matricula);
              $("#pessoa_data_nascimento").val(alocacao.pessoa_data_nascimento);
              $("#funcionario_data_admissao").val(
                alocacao.funcionario_data_admissao
              );
              $("#setor_nome").val(alocacao.setor_nome);
              $("#cargo_nome").val(alocacao.cargo_nome);
              if (alocacao.ppra_item_funcao == null || alocacao.ppra_item_funcao == '') {
                  $("#funcao_nome").val(alocacao.funcao_nome);
              }else{
                  $("#funcao_nome").val(alocacao.ppra_item_funcao); 
              }
              $("#fk_pessoa_id").val(alocacao.pessoa_id);
          }

        }
      }
    }; // fecha "interpretarRetorno"
    $.post(
      onde,
      {
        alocacao_id: alocacaoId
      },
      interpretarRetorno,
      "json"
    );
  };

  this.obterDisponiblidadeMensalDeVagasParaAgendamento = function(
    mes,
    ano,
    unidadeId
  ) {
    var url = "/ajax/html/servico/obter-dias-atendimento";
    var imprimirRetorno = function(resposta) {
      if (resposta && resposta.length > 0) {
        $("#datas_atendimento").html(resposta);
      }
    };
    $.post(
      url,
      {
        mes: mes,
        ano: ano,
        unidadeId: unidadeId
      },
      imprimirRetorno
    );
  };
}

var Agenda = {
  form: function() {
    $(function() {
      var agenda_id = $("#agenda_id").val();
      if (parseInt(agenda_id) > 0) {
        UtilitariosAjax.obterAlocacaoPorId($("#fk_alocacao_id").val());
      }

      $("#tipoexame_id").change(function() {
        ajaxResgataExamesDoPcmso();
      });
      listarDiasAtendimento();

      $("#fk_tipoexame_id").change(function() {
        document.getElementById("fk_alocacao_id").disabled = false;
      });

      $("#botao-salvar-padrao").click(function() {
        $("#formulario-padrao-salvar").submit();
      });

      $("#fk_alocacao_id").change(function() {
        UtilitariosAjax.obterAlocacaoPorId(this.value);
        //verificarAlocacaoFuncionario()
      });

      $("#verificar-disponibilidade").click(function() {
        listarDiasAtendimento();
      });

      $("#disponibilidade-mes").change(function() {
        $("#datas_atendimento").html('<img src="/img/ajax.gif"/>');
        listarDiasAtendimento();
      });

      $("#disponibilidade-ano").change(function() {
        $("#datas_atendimento").html('<img src="/img/ajax.gif"/>');
        listarDiasAtendimento();
      });
    });
  }
};
