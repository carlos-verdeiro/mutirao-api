<?php
namespace App\Middlewares;

class Autenticacao {

    public function handle() {
        try {
            
            $dados = \App\Helpers\Dados::getDados();//pega dados passados
            
            global $pdo;
            $stmt = $pdo->prepare('
                SELECT c.id, c.status, c.ponto, c.permissao, c.usuario
                FROM colaboradores AS c
                INNER JOIN sessoes AS s ON c.id = s.colaborador_id
                WHERE s.token = :token
                AND s.status = "ativo"
                AND s.expira_em > NOW()
            ');
            $stmt->bindParam(':token', $dados['token']);
            $stmt->execute();
            $resultado = $stmt->fetchAll();
            $qtd = count($resultado);
            
            if ($qtd === 0) {
                // Nenhum registro encontrado
                http_response_code(401);
                echo json_encode(['erro' => 'Sessão expirada ou inválida']);
                return false;
            } else if ($qtd === 1) {
                // Exatamente um registro encontrado
                \App\Helpers\Dados::setUsuario($resultado[0]); // Armazena o registro encontrado
                return true;
            } else {
                // Mais de um registro encontrado (situação incomum)
                http_response_code(500);
                echo json_encode(['erro' => 'Sessão duplicada encontrada, faça login novamente']);
                return false;
            }

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno na autenticação', 'detalhe' => $e->getMessage()]);
            return false;
        }
    }
}