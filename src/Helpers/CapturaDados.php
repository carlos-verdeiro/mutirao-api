<?php
namespace App\Helpers;

class CapturaDados {
    public static function json() {
        static $cache = null;
        if ($cache === null) {
            $cache = json_decode(file_get_contents('php://input'), true) ?? [];
        }
        return $cache;
    }
}