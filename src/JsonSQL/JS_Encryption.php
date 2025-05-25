<?php
namespace Src\JsonSQL;

trait JS_Encryption
{

    protected function encryptValue(string $plaintext): string {
        $key = $this->systemConfig['encryption_key'] ?? '';
        $iv = substr(hash('sha256', $key), 0, 16);
        return base64_encode(openssl_encrypt($plaintext, 'AES-256-CBC', $key, 0, $iv));
    }
    
    protected function decryptValue(string $ciphertext): string {
        $key = $this->systemConfig['encryption_key'] ?? '';
        $iv = substr(hash('sha256', $key), 0, 16);
        return openssl_decrypt(base64_decode($ciphertext), 'AES-256-CBC', $key, 0, $iv);
    }    

}