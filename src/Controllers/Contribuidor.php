<?php
namespace App\Controllers;

class Contribuidor {
    public function login() {
        
        try {
            
            $dados = \App\Helpers\Dados::getDados();
            $usuario = $dados['usuario'] ?? null;
            $senha = $dados['senha'] ?? null;
            $plataforma = $dados['plataforma'] ?? null;
            if (empty($usuario) || empty($senha)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Usuário e senha são obrigatórios']);
                return false;
            }

            // Verificar no DB
            $contribuidorModel = new \App\Models\Contribuidor();
            $usuario = $contribuidorModel->buscarPorUser($usuario);
            
            if (!$usuario) {
                http_response_code(401);
                echo json_encode(['erro' => 'Usuário não encontrado']);
                return false;
            }

            if ($usuario['status'] !== 'ativo') {
                http_response_code(403);
                echo json_encode(['erro' => 'Usuário desativado']);
                return false;
            }

            $validaSenhaHelper = new \App\Helpers\ValidaSenha();
            if(!$validaSenhaHelper->validarComPermissao($senha, $usuario['senha'], $usuario['permissao'])) {
                // Se a senha não for válida, retorna erro
                http_response_code(401);
                echo json_encode(['erro' => 'Senha incorreta']);
                return false;
            }

            // Valida plataforma
            $plataforma = (new \App\Helpers\TratamentoEntrada())->plataforma($plataforma);

            // Tenta gerar o token até 5 vezes se falhar
            $tentativas = 0;
            do {
                $sessao = $contribuidorModel->iniciarSessao($usuario['id'], $plataforma);
                $tentativas++;
            } while (!$sessao['token'] && $tentativas < 5);

            if (!$sessao['token']) {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao gerar token']);
                return false;
            }else{
                $sessao['tipo'] = "Bearer";
            }

            // Retorna o usuário sem a senha
            http_response_code(200);
            unset($usuario['senha']);
            echo json_encode([
                'token' => $sessao['token'],
                'tipo' => 'Bearer',
                'expira_em' => $sessao['expiracao'],
                'usuario' => $usuario
            ]); // Expira em 5 horas     
            return true;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno no servidor', 'detalhe' => $e->getMessage()]);
        }
    }

    public function logout() {//adicionar a verificação se tem cadastro de participante que veio junto
        try {
            $dados = \App\Helpers\Dados::getDados();

            $contribuidorModel = new \App\Models\Contribuidor();
            $fSessao = $contribuidorModel->finalizarSessao($dados['token']);

            if ($fSessao === true) {
                http_response_code(200);
                echo json_encode(['mensagem' => 'Sessão encerrada com sucesso']);
                return true;
            } elseif ($fSessao === false) {
                http_response_code(400);
                echo json_encode(['erro' => 'Sessão não encontrada']);
                return false;
            } else {
                // Se houve erro na execução da query
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao encerrar sessão']);
                return false;
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno no servidor', 'detalhe' => $e->getMessage()]);
        }
    }
}