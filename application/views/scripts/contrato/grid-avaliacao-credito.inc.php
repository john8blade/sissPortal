<?php
$resultado = array();
$clausulaComando = "avaliacao_restricao.fk_empresa_id = {$this->atributos['empresa_id']} AND avaliacao_restricao_status = 0";
try {
    $modeloAvaliacaoRestricao = new Application_Model_AvaliacaoRestricao();
    $resultado = $modeloAvaliacaoRestricao->buscarUsandoFiltro($clausulaComando, "avaliacao_restricao_data_consulta DESC");
} catch (Exception $ex) {
    echo $ex->getMessage();
}
?>



<div class="well well-small"><i class="icon-search"></i>&nbsp;RESultado CONSULTA DE AVALIAÇÃO DE CRÉDITO</div>

<div class="row-fluid">
    <table border="0" class="table table-bordered table-condensed table-hover">
        <thead>
            <tr>
                <th style="width: 1%">#</th>
                <th style="width: 20%">Orgão Avaliador</th>
                <th style="width: 15%">Resultado</th>
                <th style="width: 15%">Data da Consulta</th>
                <th style="width: 35%">Autor Consulta</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resultado as $item): ?>
                <?php
                $json = json_encode(array(
                    'observacao' => $item['avaliacao_restricao_observacao'],
                    'resultado' => $item['avaliacao_restricao_resultado'],
                    'orgao_nome' => $item['orgao_avaliador_nome'],
                    'data' => Util::dataBR($item['avaliacao_restricao_data_consulta'])
                ));
                ?>
                <tr <?php echo "id=\"itemOrgaoAvalidador{$item['avaliacao_restricao_id']}\"" ?>>
                    <td class="icones-td">
                        <a rel="tooltip" href="javascript:;" onclick='abrirModalDetalheAvaliacao(<?php echo $json ?>)' data-original-title="Detalhes"><i class="icon-info-sign"></i></a>
                        <a rel="tooltip" href="javascript:void(0);" onclick="Util.confirma('Deseja excluir o registro?', '/ajax/excluir/registro/avaliacao_restricao/id/<?php echo $item['avaliacao_restricao_id'] ?>/', this)" data-original-title="Excluir"><i class="icon-trash"></i></a>
                    </td>
                    <td><?php echo $item['orgao_avaliador_nome'] ?></td>
                    <td><?php echo $item['avaliacao_restricao_resultado'] ?></td>
                    <td><?php echo Util::dataBR($item['avaliacao_restricao_data_consulta']) ?></td>
                    <td><?php echo $item['pessoa_nome'] ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

</div>