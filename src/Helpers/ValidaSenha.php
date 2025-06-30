<?php
namespace App\Helpers;

class ValidaSenha {
    public function validarComPermissao($senhaDig, $senhaDB, $permissao) {
        switch ($permissao) {
            case 'admin':
            case 'organizador':
                if (!password_verify($senhaDig, $senhaDB)) {
                    return false;
                }
                break;
            case 'voluntario':
                if ($senhaDig !== $senhaDB) {
                    return false;
                }
                break;
        }
        return true;
    }
}