<?php

namespace Providers\Jdb;

use App\Libraries\Logger;
use Providers\Jdb\Contracts\ICredentials;

class JdbEncryption
{
    public function encrypt(ICredentials $credentials, array $data): string
    {
        $source = json_encode($data);
        $paddingChar = ' ';
        $size = 16;
        $padLength = $size - (strlen($source) % $size);

        $data = str_pad($source, strlen($source) + $padLength, $paddingChar);

        $encrypted = openssl_encrypt(
            data: $data,
            cipher_algo: 'AES-128-CBC',
            passphrase: $credentials->getKey(),
            options: OPENSSL_NO_PADDING,
            iv: $credentials->getIV()
        );

        $encrypted = base64_encode($encrypted);

        return str_replace(
            search: ['+', '/', '='],
            replace: ['-', '_', ''],
            subject: $encrypted
        );
    }

    public function decrypt(ICredentials $credentials, string $data)
    {
        $data = str_replace(
            search: ['-', '_'],
            replace: ['+', '/'],
            subject: $data
        );

        $data = base64_decode($data);

        $decrypted = openssl_decrypt(
            data: $data,
            cipher_algo: 'AES-128-CBC',
            passphrase: $credentials->getKey(),
            options: OPENSSL_NO_PADDING,
            iv: $credentials->getIV()
        );

        $decryptedData = json_decode(mb_convert_encoding(
            string: trim($decrypted),
            to_encoding: 'UTF-8',
            from_encoding: 'ISO-8859-1'
        ));

        $logger = app()->make(Logger::class);
        $logger->logDecrypted((array) $decryptedData);

        return $decryptedData;
    }
}