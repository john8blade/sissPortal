<?php

class FuncionarioEpiController extends Controller {

    public function excluirAction() {
        $feedback = array('erro' => 0, 'msg' => "");
        $id = (int) $this->_getParam('id');
        try {
            $FuncionarioEpi = new Application_Model_FuncionarioEpi();
            $FuncionarioEpi->update(array('funcionario_epi_status' => 2), array('funcionario_epi_id = ?' => $id));
            $feedback['msg'] = "Excluído com sucesso.";
        } catch (Exception $ex) {
            $feedback['erro'] = 1;
            $feedback['msg'] = $ex->getMessage();
        }
        $this->feedback($feedback);
    }

}
