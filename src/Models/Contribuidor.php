<?php
namespace App\Models;

class Contribuidor {
    public function buscarPorUser($usuario) {
        global $pdo; // Usa a conexão PDO definida no arquivo de configuração
        $stmt = $pdo->prepare('SELECT * FROM colaboradores WHERE usuario = :usuario');
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function iniciarSessao($id, $plataforma) {
        $geradorAleatorio = new \App\Helpers\GeradorAleatorio();
        $token = $geradorAleatorio->token();

        $expiracao = date('Y-m-d H:i:s', strtotime('+5 hour')); // Expira em 5 horas
        $status = 'ativo';

        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO sessoes (colaborador_id, token, plataforma, expira_em, status) VALUES (:id, :token, :plataforma, :expiracao, :status)');
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':plataforma', $plataforma);
        $stmt->bindParam(':expiracao', $expiracao);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            // Token inserido com sucesso
            return [
            'token' => $token,
            'expiracao' => $expiracao
            ];
        } else {
            // Falha ao inserir o token
            return false;
        }
    }

    public function finalizarSessao($token) {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE sessoes SET status = :status WHERE token = :token');
        $status = 'finalizado';
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':token', $token);
        return $stmt->execute();
    }
}