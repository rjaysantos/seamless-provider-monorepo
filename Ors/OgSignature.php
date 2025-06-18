<?php

namespace Providers\Ors;

use Illuminate\Http\Request;
use Providers\Ors\Contracts\ICredentials;
use Providers\Ors\DTO\OrsRequestDTO;

class OgSignature
{
    public function isSignatureValid(OrsRequestDTO $requestDTO, ICredentials $credentials): bool
    {
        if (empty($requestDTO->content) === false) {
            $createdSignatureByObject = $this->createSignatureByObject(
                objectData: json_decode($requestDTO->content),
                credentials: $credentials
            );

            if ($createdSignatureByObject === $requestDTO->signature)
                return true;
        }

        $createdSignatureByArray = $this->createSignatureByArray(arrayData: $requestDTO->all, credentials: $credentials);

        if ($createdSignatureByArray === $requestDTO->signature)
            return true;

        return false;
    }

    public function createSignatureByObject(object $objectData, ICredentials $credentials): string
    {
        $keys = array_keys((array) $objectData);

        sort($keys);

        $ogString = '';
        foreach ($keys as $key) {
            if ($key !== 'signature') {
                if (is_object($objectData->$key) == true || is_array($objectData->$key) == true) {
                    $ogString .= "{$key}=" . json_encode($objectData->$key) . "&";
                } else {
                    $ogString .= "{$key}={$objectData->$key}&";
                }
            }
        }

        if (substr($ogString, -1) === '&')
            $ogString = substr($ogString, 0, -1);

        return md5($ogString . $credentials->getPrivateKey());
    }

    public function createSignatureByArray(array $arrayData, ICredentials $credentials): string
    {
        unset($arrayData['signature']);

        ksort($arrayData);

        foreach ($arrayData as $key => $value) {
            if (is_object($value) == true || is_array($value) == true) {
                $arraytoString[] = "{$key}=" . json_encode($value);
            } else {
                $arraytoString[] = "{$key}={$value}";
            }
        }

        $ogString = implode('&', $arraytoString);

        return md5($ogString . $credentials->getPrivateKey());
    }
}
