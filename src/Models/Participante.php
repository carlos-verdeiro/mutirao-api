<?php
namespace App\Models;

class Participante {

    public function cadastro($colaborador_id, $participantes, $status) {
        $quantidadeInseridos = 0;
        global $pdo;
        $modelCidade = new \App\Models\Cidade();
        foreach ($participantes as $index => $participante) {
            //pega o id da cidade ou insere uma nova
            $cidade_id = $modelCidade ->obterOuInserir($participante['cidade'], $colaborador_id);
            try {
                if ($status[$index]['valido'] === true && empty($status[$index]['erros'])) {

                    if ($this -> numeroJaCadastrado($participante['numero'])) {
                        $status[$index]['inserido'] = false;
                        $status[$index]['erros']['numero'] = 'Número já utilizado no sistema';
                    }else{
                        $stmt = $pdo->prepare('
                            INSERT INTO participantes (colaborador_id, cidade_id, nome, tipo_numero, numero, participacao_pas, hora_cadastro_app) 
                            VALUES (:colaborador_id, :cidade_id, :nome, :tipo_numero, :numero, :participacao_pas, :hora_cadastro_app)
                        ');
                        $stmt->bindParam(':colaborador_id', $colaborador_id);
                        $stmt->bindParam(':cidade_id', $cidade_id);
                        $stmt->bindParam(':nome', $participante['nome']);
                        $stmt->bindParam(':tipo_numero', $participante['tipo_numero']);
                        $stmt->bindParam(':numero', $participante['numero']);
                        $stmt->bindParam(':participacao_pas', $participante['participacao_pas']);
                        $stmt->bindParam(':hora_cadastro_app', $participante['data_registro']);

                        if ($stmt->execute()) {
                            $status[$index]['inserido'] = true;
                            $status[$index]['identificadores']['id_externo'] = (int) $pdo->lastInsertId();
                            $quantidadeInseridos++;
                        }else {
                            $status[$index]['inserido'] = false;
                            $status[$index]['erros']['insercao'] = $stmt->errorInfo();
                        }
                    }
                }else 
                {
                    $status[$index]['inserido'] = false;
                }
            } catch (\Exception $e) {
                $status[$index]['inserido'] = false;
            }
        }

        return ['status' => $status, 'quantidade_inseridos' => $quantidadeInseridos];
    }

    public function editar($colaborador_id, $participantes, $status) {
        $quantidadeEditados = 0;
        global $pdo;
        $modelCidade = new \App\Models\Cidade();
        foreach ($participantes as $index => $participante) {
            try {
                if(!empty($participante['edicoes']['numero']) && $this->numeroJaCadastrado($participante['edicoes']['numero'])) {
                    $status[$index]['inserido'] = false;
                    $status[$index]['erros']['numero'] = 'Número já utilizado no sistema';
                }else if ($status[$index]['valido'] === true && empty($status[$index]['erros'])) {
                    $sql = "UPDATE participantes SET " ;
                    $updates = [];
                    
                    $edicoesFiltradas = $this->filtrarCamposPermitidos($participante['edicoes']);
                    if(!empty($edicoesFiltradas['cidade']))
                    {
                        $cidade_id = $modelCidade->obterOuInserir($edicoesFiltradas['cidade'], $colaborador_id);
                        $edicoesFiltradas['cidade_id'] = $cidade_id;
                        unset($edicoesFiltradas['cidade']);
                    }

                    foreach ($edicoesFiltradas as $key => $value) {
                        $updates[] = "$key = :$key";
                    }

                    $sql .= implode(", ", $updates);
                    $sql .= " WHERE id = :id_externo AND colaborador_id = :colaborador_id";

                    $stmt = $pdo->prepare($sql);

                    $id = $participante['id_externo'];
                    $stmt->bindParam(':id_externo', $id);
                    $stmt->bindParam(':colaborador_id', $colaborador_id);
                    foreach ($edicoesFiltradas as $key => $value) {
                        $stmt->bindValue(":$key", $value);
                    }
                    if ($stmt->execute()) {
                        $status[$index]['editado'] = true;
                        $quantidadeEditados++;
                    } else {
                        $status[$index]['editado'] = false;
                        $status[$index]['erros']['edicao'] = $stmt->errorInfo();
                    }
                    
                }else 
                {
                    $status[$index]['editado'] = false;
                }
            } catch (\Exception $e) {
                $status[$index]['editado'] = false;
                $status[$index]['erros']['insercao'] = $e->getMessage();
            }
        }

        return ['status' => $status, 'quantidade_editados' => $quantidadeEditados];
    }

    private function numeroJaCadastrado($numero) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT 1 FROM participantes WHERE numero = :numero LIMIT 1');
        $stmt->bindParam(':numero', $numero);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    private function filtrarCamposPermitidos($dados) {
    $camposProibidos = ['id_externo', 'colaborador_id', 'hora_cadastro_app', 'hora_insert', 'id'];
    return array_filter($dados, function($key) use ($camposProibidos) {
        return !in_array($key, $camposProibidos);
    }, ARRAY_FILTER_USE_KEY);
}
}