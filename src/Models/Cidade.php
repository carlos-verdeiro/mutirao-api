<?php
namespace App\Models;

class Cidade {

    public function obterOuInserir($nome, $colaborador_id, $estado = null, $adicao = 'adicionado') {

        global $pdo;
        // Tenta buscar por nome
        $stmt = $pdo->prepare('SELECT id FROM cidades WHERE nome = :nome');
        $stmt->execute([':nome' => $nome]);
        $cidade = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($cidade) {
            return (int) $cidade['id'];
        }

        // se não, insere nova cidade
        $stmt = $pdo->prepare(
            'INSERT INTO cidades (nome, colaborador_id, estado, adicao) VALUES (:nome, :colaborador_id, :estado, :adicao)'
        );
        $stmt->execute([
            ':nome' => $nome,
            ':colaborador_id' => $colaborador_id,
            ':estado' => $estado,
            ':adicao' => $adicao
        ]);

        return (int) $pdo->lastInsertId();
    }
}