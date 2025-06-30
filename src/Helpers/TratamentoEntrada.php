<?php
namespace App\Helpers;

class TratamentoEntrada {
    public function plataforma($plataforma) {
        
        if ($plataforma !== 'web' && $plataforma !== 'android' && $plataforma !== 'ios') {
            return null;
        }
        return $plataforma;
    }
}