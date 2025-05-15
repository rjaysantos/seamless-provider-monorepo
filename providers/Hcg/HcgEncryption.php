<?php

namespace Providers\Hcg;

use phpseclib3\Crypt\DES;
use Providers\Hcg\Contracts\ICredentials;

class HcgEncryption
{
    public function encrypt(ICredentials $credentials, array $data): string
    {
        $md5Request = $data;
        $md5Request['sign_key'] = $credentials->getSignKey();

        foreach ($md5Request as $key => $value) {
            if (is_object($value) == true || is_array($value) == true) {
                $arraytoString[] = "{$key}={json_encode($value)}";
            } else {
                $arraytoString[] = "{$key}={$value}";
            }
        }

        $stringRequest = implode(separator: '&', array: $arraytoString);

        $data['sign_key'] = strtoupper(md5($stringRequest));
        $des = new DES('ecb');
        $des->setKey(substr(string: $credentials->getEncryptionKey(), offset: 0, length: 8));

        $encrypted = $des->encrypt(json_encode($data));

        return base64_encode($encrypted);
    }

    public function createSignature(ICredentials $credentials, array $data): string
    {
        unset($data['sign']);

        ksort($data);

        $data['key'] = $credentials->getWalletApiSignKey();

        return md5(json_encode($data, 320));
    }
}