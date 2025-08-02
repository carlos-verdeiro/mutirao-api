<?php
namespace App\Controllers;

class Participante {
    public function cadastro() {
        $dados = \App\Helpers\Dados::getDados();
        $status = \App\Helpers\Dados::getStatus();
        $usuario = \App\Helpers\Dados::getUsuario();
        $participantes = $dados['participantes'] ?? [];

        $modelParticipante = new \App\Models\Participante();
        $resposta = $modelParticipante ->cadastro($usuario['id'], $participantes, $status);

        $status = $resposta['status'];
        $quantidadeInseridos = $resposta['quantidade_inseridos'];


        if ($quantidadeInseridos === 0) {
            http_response_code(400);
            echo json_encode(['mensagem' => 'Nenhum participante foi cadastrado', "status" => $status]);
            return false;
        }else if ($quantidadeInseridos < count($participantes)) {
            http_response_code(200);
            echo json_encode(['mensagem' => $quantidadeInseridos.' participantes cadastrados com sucesso', "status" => $status]);
            return true;
        } else {
            http_response_code(200);
            echo json_encode(['mensagem' => 'Todos os participantes cadastrados com sucesso', "status" => $status]);
            return true;
        }

        
    }

    public function editar() {
        $dados = \App\Helpers\Dados::getDados();
        $status = \App\Helpers\Dados::getStatus();
        $usuario = \App\Helpers\Dados::getUsuario();
        $participantes = $dados['participantes'] ?? [];

        $modelParticipante = new \App\Models\Participante();
        $resposta = $modelParticipante->editar($usuario['id'], $participantes, $status);
        
        $status = $resposta['status'];
        $quantidadeEditados = $resposta['quantidade_editados'];

        
        if ($quantidadeEditados === 0) {
            http_response_code(400);
            echo json_encode(['mensagem' => 'Nenhum participante foi editado', "status" => $status]);
            return false;
        }else if ($quantidadeEditados < count($participantes)) {
            http_response_code(200);
            echo json_encode(['mensagem' => $quantidadeEditados.' participantes editados com sucesso', "status" => $status]);
            return true;
        } else {
            http_response_code(200);
            echo json_encode(['mensagem' => 'Todos os participantes editados com sucesso', "status" => $status]);
            return true;
        }
        
    }

}