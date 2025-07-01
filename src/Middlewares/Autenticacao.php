<?php
namespace App\Middlewares;

class Autenticacao {

    public function handle() {
        try {
            $dados = \App\Helpers\CapturaDados::json();

            global $pdo;
            $stmt = $pdo->prepare('
                SELECT count(*) as total
                FROM sessoes
                WHERE token = :token AND status = "ativo" AND expira_em > NOW()
            ');
            $stmt->bindParam(':token', $dados['token']);
            $stmt->execute();
            $resultado = $stmt->fetch();
            
            if ($resultado['total'] > 0) {
                return true;
            } else {
                http_response_code(401);
                echo json_encode(['erro' => 'Sessão inválida ou expirada']);
                return false;
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno na autenticação', 'detalhe' => $e->getMessage()]);
            return false;
        }
    }
}