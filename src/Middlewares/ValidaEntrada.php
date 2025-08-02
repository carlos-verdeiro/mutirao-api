<?php
namespace App\Middlewares;

class ValidaEntrada {

    public function logout() {
        $dados = \App\Helpers\Dados::getDados();

        $tratador = new \App\Helpers\TratamentoEntrada();
        
        if ($tratador->validaToken($dados['token'] ?? null)) {
            return true;
        } else {
            http_response_code(400);
            echo json_encode(['erro' => 'Token inválido']);
            return false;
        }
    }

    public function participanteCadastro() {
        $dados = \App\Helpers\Dados::getDados();
        $tratador = new \App\Helpers\TratamentoEntrada();
        
        if (!$tratador->validaToken($dados['token'] ?? null)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Token inválido']);
            return false;
        }

        $quantidade = count((array) ($dados['participantes'] ?? []));

        if ($quantidade < 1) {// Verifica se há pelo menos um participante
            http_response_code(400);
            echo json_encode(['erro' => 'Número de participantes inválido']);
            return false;
        }

        $participantes = $dados['participantes'];

        $status = []; // Array para armazenar status de validação
        $ids_internos = [];
        $numeros = []; //números cadastrados
        $invalidos = 0;

        foreach ($participantes as $index => $participante) {
            $this->validaParticipanteCadastro($participante, $status, $index, $ids_internos, $numeros, $invalidos);
            $participantes[$index] = $participante; // Atualiza o participante com os dados validados
        }
        $dados['participantes'] = $participantes;
        \App\Helpers\Dados::setDados($dados);
        \App\Helpers\Dados::setStatus($status);

        if ($invalidos >= $quantidade) {
            http_response_code(400);

            $mensagem = $quantidade < 2 ? 'Cadastro com erro' : 'Todos os cadastros com erro';

            echo json_encode([
                'erro' => $mensagem,
                'status' =>$status
            ]);

            return false;
        }


        return true;
    }

    public function participanteEditar() {
        $dados = \App\Helpers\Dados::getDados();
        $tratador = new \App\Helpers\TratamentoEntrada();
        
        if (!$tratador->validaToken($dados['token'] ?? null)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Token inválido']);
            return false;
        }

        $quantidade = count((array) ($dados['participantes'] ?? []));

        if ($quantidade < 1) {// Verifica se há pelo menos um participante
            http_response_code(400);
            echo json_encode(['erro' => 'Número de participantes inválido']);
            return false;
        }

        $participantes = $dados['participantes'];

        $status = []; // Array para armazenar status de validação
        $ids_internos = [];
        $ids_externos = [];
        $numeros = []; //números cadastrados
        $invalidos = 0;

        foreach ($participantes as $index => $participante) {
            $this->validaParticipanteEditar($participante, $status, $index, $ids_internos, $ids_externos, $numeros, $invalidos);
            $participantes[$index] = $participante; // Atualiza o participante com os dados validados
        }
        
        $dados['participantes'] = $participantes;
        \App\Helpers\Dados::setDados($dados);
        \App\Helpers\Dados::setStatus($status);

        if ($invalidos >= $quantidade) {
            http_response_code(400);

            $mensagem = $quantidade < 2 ? 'Edição com erro' : 'Todos as edições com erro';

            echo json_encode([
                'erro' => $mensagem,
                'status' =>$status
            ]);

            return false;
        }


        return true;
    }

    private function validaParticipanteCadastro(&$participante, &$status, &$index, &$ids_internos, &$numeros, &$invalidos) {
        $status[$index] = [
            'valido' => true,
            'erros' => [],
            'identificadores' => []
        ];
        if (!isset($participante['id_interno']) || $participante['id_interno'] === '') {
            $status[$index]['erros']['id_interno'] = 'ID interno obrigatório';
        } else {
            if (!is_numeric($participante['id_interno']) || $participante['id_interno'] < 1) {
                $status[$index]['erros']['id_interno'] = 'Deve ser um número natural';
            } else {
                $participante['id_interno'] = (int) $participante['id_interno'];
                if (in_array($participante['id_interno'], $ids_internos)) {
                    $status[$index]['erros']['id_interno'] = 'ID interno duplicado';
                }else{
                    $ids_internos[] = $participante['id_interno'];
                }
            }
        }

        if (!isset($participante['nome']) || !is_string($participante['nome'])) {
            $status[$index]['erros']['nome'] = 'Nome deve ser uma string';
        } else if (trim($participante['nome']) === '') {
            $status[$index]['erros']['nome'] = 'Nome obrigatório';
        } else if (mb_strlen($participante['nome']) < 3 || mb_strlen($participante['nome']) > 50) {
            $status[$index]['erros']['nome'] = 'Nome deve ter entre 3 e 50 caracteres';
        }

        if (!isset($participante['tipo_numero']) || !is_numeric($participante['tipo_numero']) || ($participante['tipo_numero'] != 1 && $participante['tipo_numero'] != 2)) {
            $participante['tipo_numero'] = 1;
        }else{
            $participante['tipo_numero'] = (int) $participante['tipo_numero'];
        }

        if (!isset($participante['numero']) || !is_string($participante['numero']) || trim($participante['numero']) === '') {
            $status[$index]['erros']['numero'] = 'Número obrigatório';
        } else {
            // Se não for 1 ou 2, considera como celular (1)
            $tipoNumero = ($participante['tipo_numero'] == 1 || $participante['tipo_numero'] == 2) ? $participante['tipo_numero'] : 1;

            if ($tipoNumero == 1) { // Celular
                if (!preg_match('/^\d{11}$/', $participante['numero'])) {
                    if (!isset($status[$index]['erros']['numero'])) {
                        $status[$index]['erros']['numero'] = 'Celular deve ter 11 dígitos';
                    }
                }
            } else if ($tipoNumero == 2) { // Telefone
                if (!preg_match('/^\d{10}$/', $participante['numero'])) {
                    if (!isset($status[$index]['erros']['numero'])) {
                        $status[$index]['erros']['numero'] = 'Telefone deve ter 10 dígitos';
                    }
                }
            }

            //tratamento de duplicidade
            if (in_array($participante['numero'], $numeros)) {
                if (!isset($status[$index]['erros']['numero'])) {
                    $status[$index]['erros']['numero'] = 'Número duplicado';
                }
            } else {
                $numeros[] = $participante['numero'];
            }
        }

        if (!isset($participante['cidade']) || !is_string($participante['cidade']) || trim($participante['cidade']) === '') {
            $status[$index]['erros']['cidade'] = 'Cidade deve ser uma string não vazia';
        }else if (mb_strlen($participante['cidade']) < 3 || mb_strlen($participante['cidade']) > 50) {
            $status[$index]['erros']['cidade'] = 'Cidade deve ter entre 3 e 50 caracteres';
        }
        $tratador = new \App\Helpers\TratamentoEntrada();
        $participante['cidade'] = $tratador->normalizarTexto($participante['cidade']);


        // Se não for 0 ou 1, considera 0(não participou anteriormente)
        if (!isset($participante['participacao_pas']) || ($participante['participacao_pas'] != 'nao' && $participante['participacao_pas'] != 'sim')) {
            $participante['participacao_pas'] = 'nao';
        }else{
            $participante['participacao_pas'] = $tratador->normalizarTexto($participante['participacao_pas']);
        }
        

        if (!isset($participante['data_registro']) || trim($participante['data_registro']) === '') {
            $status[$index]['erros']['data_registro'] = 'Data de registro obrigatório';
        } else {
            // Verifica se está no formato do MySQL: 'YYYY-MM-DD HH:MM:SS'
            $data = $participante['data_registro'];
            $d = \DateTime::createFromFormat('Y-m-d H:i:s', $data);
            if (!($d && $d->format('Y-m-d H:i:s') === $data)) {
                $status[$index]['erros']['data_registro'] = 'Data de registro deve estar no formato YYYY-MM-DD HH:MM:SS';
            }
        }

        if(!empty($status[$index]['erros'])) {
            $status[$index]['valido'] = false; // Marca o participante como inválido
            $invalidos++;
        } else {
            $status[$index]['valido'] = true; // Marca o participante como válido
        }

        //adiciona identificadores
        $status[$index]['identificadores']['id_interno'] = $participante['id_interno'];
        $status[$index]['identificadores']['numero'] = $participante['numero'];
    }

    private function validaParticipanteEditar(&$participante, &$status, &$index, &$ids_internos, &$ids_externos, &$numeros, &$invalidos) {
        $tratador = new \App\Helpers\TratamentoEntrada();
        $status[$index] = [
            'valido' => true,
            'erros' => [],
            'identificadores' => []
        ];


        $permitidos = ['nome', 'tipo_numero', 'numero', 'participacao_pas', 'cidade'];
        foreach ($participante['edicoes'] as $campo => $valor) {
            if (!in_array($campo, $permitidos)) {
                $status[$index]['erros'][$campo] = 'Campo não permitido';
            }
        }

        
        if (!isset($participante['id_interno']) || $participante['id_interno'] === '') {
            $status[$index]['erros']['id_interno'] = 'ID interno obrigatório';
        } else {
            if (!is_numeric($participante['id_interno']) || $participante['id_interno'] < 1) {
                $status[$index]['erros']['id_interno'] = 'Deve ser um número natural';
            } else {
                $participante['id_interno'] = (int) $participante['id_interno'];
                if (in_array($participante['id_interno'], $ids_internos)) {
                    $status[$index]['erros']['id_interno'] = 'ID interno duplicado';
                }else{
                    $ids_internos[] = $participante['id_interno'];
                }
            }
        }

        if (!isset($participante['id_externo']) || $participante['id_externo'] === '') {
            $status[$index]['erros']['id_externo'] = 'ID externo obrigatório';
        } else {
            if (!is_numeric($participante['id_externo']) || $participante['id_externo'] < 1) {
                $status[$index]['erros']['id_externo'] = 'Deve ser um número natural';
            } else {
                $participante['id_externo'] = (int) $participante['id_externo'];
                if (in_array($participante['id_externo'], $ids_externos)) {
                    $status[$index]['erros']['id_externo'] = 'ID externo duplicado';
                }else{
                    $ids_externos[] = $participante['id_externo'];
                }
            }
        }

        if (isset($participante['edicoes']['nome'])) {
            if (!is_string($participante['edicoes']['nome'])) {
                $status[$index]['erros']['nome'] = 'Nome deve ser uma string';
            } else if (trim($participante['edicoes']['nome']) === '') {
                $status[$index]['erros']['nome'] = 'Nome não pode estar em branco';
            } else {
                $tamanho = mb_strlen($participante['edicoes']['nome']);
                if ($tamanho < 3 || $tamanho > 50) {
                    $status[$index]['erros']['nome'] = 'Nome deve ter entre 3 e 50 caracteres';
                }
            }
        }



        if (isset($participante['edicoes']['numero']) ){
            if (!is_string($participante['edicoes']['numero'])) {
                $status[$index]['erros']['numero'] = 'Número deve ser uma string';
            }else if(trim($participante['edicoes']['numero']) === ''){
                $status[$index]['erros']['numero'] = 'Número não pode estar em vazio';
            }else if (!preg_match('/^\d+$/', $participante['edicoes']['numero'])) {
                $status[$index]['erros']['numero'] = 'Número deve conter apenas dígitos';
            }else if (!isset($participante['edicoes']['tipo_numero']) || !is_numeric($participante['edicoes']['tipo_numero']) || ($participante['edicoes']['tipo_numero'] != 1 && $participante['edicoes']['tipo_numero'] != 2)) {
                $status[$index]['erros']['tipo_numero'] = 'Tipo de número inválido';
            }else if ($participante['edicoes']['numero'] == $participante['numero']) {
                $status[$index]['erros']['tipo_numero'] = 'Número deve ser diferente do atual';
            }else{
                $participante['edicoes']['tipo_numero'] = (int) $participante['edicoes']['tipo_numero'];
                if ($tipoNumero == 1) { // Celular
                    if (!preg_match('/^\d{11}$/', $participante['edicoes']['numero'])) {
                        if (!isset($status[$index]['erros']['numero'])) {
                            $status[$index]['erros']['numero'] = 'Celular deve ter 11 dígitos';
                        }
                    }
                } else if ($tipoNumero == 2) { // Telefone
                    if (!preg_match('/^\d{10}$/', $participante['edicoes']['numero'])) {
                        if (!isset($status[$index]['erros']['numero'])) {
                            $status[$index]['erros']['numero'] = 'Telefone deve ter 10 dígitos';
                        }
                    }
                }

                //tratamento de duplicidade
                if (in_array($participante['edicoes']['numero'], $numeros)) {
                    if (!isset($status[$index]['erros']['numero'])) {
                        $status[$index]['erros']['numero'] = 'Número duplicado';
                    }
                } else {
                    $numeros[] = $participante['edicoes']['numero'];
                }
            }
        }else if (isset($participante['tipo_numero'])){
            $status[$index]['erros']['numero'] = 'Número não informado';
        }

        if (isset($participante['edicoes']['cidade'])) {
            if(!is_string($participante['edicoes']['cidade']) || trim($participante['edicoes']['cidade']) === ''){
                $status[$index]['erros']['cidade'] = 'Cidade deve ser uma string não vazia';
            }else if (mb_strlen($participante['edicoes']['cidade']) < 3 || mb_strlen($participante['edicoes']['cidade']) > 50) {
                $status[$index]['erros']['cidade'] = 'Cidade deve ter entre 3 e 50 caracteres';
            }else{ 
                $tratador = new \App\Helpers\TratamentoEntrada();
                $participante['edicoes']['cidade'] = $tratador->normalizarTexto($participante['edicoes']['cidade']);
            }
        }


        // Se não for 0 ou 1, considera 0(não participou anteriormente)
        if (isset($participante['edicoes']['participacao_pas'])){ 
            if( ($participante['edicoes']['participacao_pas'] != 'nao' && $participante['edicoes']['participacao_pas'] != 'sim')) {
            $participante['edicoes']['participacao_pas'] = 'nao';
            }else{
                $participante['edicoes']['participacao_pas'] = $tratador->normalizarTexto($participante['edicoes']['participacao_pas']);
            }
        }

        if(!empty($status[$index]['erros'])) {
            $status[$index]['valido'] = false; // Marca o participante como inválido
            $invalidos++;
        } else {
            $status[$index]['valido'] = true; // Marca o participante como válido
        }

        //adiciona identificadores
        $status[$index]['identificadores']['id_interno'] = $participante['id_interno'];
        $status[$index]['identificadores']['id_externo'] = $participante['id_externo'];
        $status[$index]['identificadores']['numero'] = $participante['numero'];
    }
}