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
            $this->validaParticipante($participante, $status, $index, $ids_internos, $numeros, $invalidos);
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

    private function validaParticipante(&$participante, &$status, &$index, &$ids_internos, &$numeros, &$invalidos) {
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
}