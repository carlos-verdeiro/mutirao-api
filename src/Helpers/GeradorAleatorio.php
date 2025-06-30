<?php
namespace App\Helpers;

require_once __DIR__ . '/../../config/db.php';

class GeradorAleatorio {
    public function token() {
        return bin2hex(random_bytes(127)); // 127 bytes = 254 caracteres hexadecimais
    }
}