<?php
namespace App\Middlewares;

class ValidaEntrada {

    public function logout() {
        $dados = \App\Helpers\CapturaDados::json();
        
        if ($this->token($dados['token'] ?? null)) {
            return true;
        } else {
            http_response_code(400);
            echo json_encode(['erro' => 'Token inválido']);
            return false;
        }
    }

    private function token($token) {
        // Token deve ter exatamente 254 caracteres hexadecimais
        return is_string($token) && preg_match('/^[a-f0-9]{254}$/i', $token);
    }
}