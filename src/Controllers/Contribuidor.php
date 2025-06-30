<?php
namespace App\Controllers;

class Contribuidor {
    public function login() {
        
        try {
            
            $dados = json_decode(file_get_contents('php://input'), true);
            $usuario = $dados['usuario'] ?? '';
            $senha = $dados['senha'] ?? '';
            $plataforma = $dados['plataforma'] ?? '';
            if (empty($usuario) || empty($senha)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Usuário e senha são obrigatórios']);
                return;
            }

            // Verificar no DB
            $contribuidorModel = new \App\Models\Contribuidor();
            $usuario = $contribuidorModel->buscarPorUser($usuario);
            
            if (!$usuario) {
                http_response_code(401);
                echo json_encode(['erro' => 'Usuário não encontrado']);
                return;
            }

            $validaSenhaHelper = new \App\Helpers\ValidaSenha();
            if(!$validaSenhaHelper->validarComPermissao($senha, $usuario['senha'], $usuario['permissao'])) {
                // Se a senha não for válida, retorna erro
                http_response_code(401);
                echo json_encode(['erro' => 'Senha incorreta']);
                return;
            }

            // Valida plataforma
            $plataforma = (new \App\Helpers\TratamentoEntrada())->plataforma($plataforma);

            // Tenta gerar o token até 5 vezes se falhar
            $tentativas = 0;
            do {
                $sessao = $contribuidorModel->gerarToken($usuario['id'], $plataforma);
                $tentativas++;
            } while (!$sessao['token'] && $tentativas < 5);

            if (!$sessao['token']) {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao gerar token']);
                return;
            }

            // Retorna o usuário sem a senha
            http_response_code(200);
            unset($usuario['senha']);
            echo json_encode(['mensagem' => 'logado!', 'usuario' => $usuario, 'sessao' => $sessao]); // Expira em 5 horas     
            
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno no servidor', 'detalhe' => $e->getMessage()]);
        }
    }
}