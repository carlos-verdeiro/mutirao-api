<?php
namespace App\Helpers;

class TratamentoEntrada {
    public function plataforma($plataforma) {
        
        if ($plataforma !== 'web' && $plataforma !== 'android' && $plataforma !== 'ios') {
            return null;
        }
        return $plataforma;
    }

    public function validaToken($token) {

        // Token deve ter exatamente 254 caracteres hexadecimais
        $tokenValido = is_string($token ?? null) && preg_match('/^[a-f0-9]{254}$/i', $token ?? null);

        if ($tokenValido) {
            return true;
        } else {
            return false;
        }
    }

    function normalizarTexto($texto) {
    $texto = trim($texto);
    $texto = mb_strtolower($texto, 'UTF-8');

    // Substituir caracteres com acento por equivalentes sem acento
    $texto = preg_replace([
        "/[áàãâä]/u", "/[éèêë]/u", "/[íìîï]/u",
        "/[óòõôö]/u", "/[úùûü]/u", "/[ç]/u"
    ], [
        "a", "e", "i", "o", "u", "c"
    ], $texto);

    // Remove qualquer caractere especial restante
    $texto = preg_replace("/[^a-z0-9 ]/", "", $texto);

    return $texto;
}

}