<?php

namespace App\Services\Tenant;

class NotaCifradaService
{
    protected $key;

    public function __construct()
    {
        // Se espera un string HEX de 64 caracteres (32 bytes)
        $hexKey = env('APP_NOTES_KEY');
        if (!$hexKey) {
            throw new \Exception('APP_NOTES_KEY no configurado en entorno.');
        }
        $this->key = hex2bin($hexKey);
    }

    public function cifrar($texto)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($texto, 'aes-256-cbc', $this->key, 0, $iv);
        
        return [
            'contenido' => $encrypted,
            'iv' => bin2hex($iv)
        ];
    }

    public function descifrar($contenidoCifrado, $ivHex)
    {
        try {
            $iv = hex2bin($ivHex);
            return openssl_decrypt($contenidoCifrado, 'aes-256-cbc', $this->key, 0, $iv);
        } catch (\Exception $e) {
            return "[Error al descifrar contenido]";
        }
    }
}
