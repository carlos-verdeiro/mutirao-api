<?php
namespace App\Helpers;

class Dados {
    private static array $dados = [];
    private static array $status = [];
    private static array $usuario = [];

    private static function jsonInput() {
        $dados = json_decode(file_get_contents('php://input'), true) ?? [];
        return $dados;
    }

    public static function getDados() {
        if (empty(self::$dados)) {
            self::$dados = self::jsonInput();
        }
        return self::$dados;
    }

    public static function setDados($atualizado) {
        self::$dados = $atualizado;
        return true;
    }

    // Métodos para erros de validação
    public static function getStatus() {
        return self::$status;
    }

    public static function setStatus($value) {
        self::$status = $value;
        return true;
    }

    // Métodos para dados de usuário
    public static function getUsuario() {
        return self::$usuario;
    }

    public static function setUsuario($value) {
        self::$usuario = $value;
        return true;
    }

}