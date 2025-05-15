<?php

use Tests\TestCase;
use App\GameProviders\Pla\PlaCredentials;

class PlaCredentialsTest extends TestCase
{
    public function makeCredentialSetter()
    {
        return new PlaCredentials();
    }

    /**
     * @dataProvider credentialParams
     */
    public function test_getCredentialsByCurrency_plaProduction_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE0NzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNzkzZmZmYzBhZWZkZGI3YjMwYjFkNTUzMTgyZjFiMDAiLCJzdWIiOiJBdWRTeXMifQ.TJ_BTNHNEI0c09qCaiU-rdkkuQ3LYB-5oh-c2vaDOOpAy7rjoD4_EeggILG3xQb-koyN2mUe3ZB_51QumxqoD743oeJlG3VDc9NJgG1Ru0PQ-6z8wRpnHeEJmV_87zNQd4uAwM86H0YL0FbwReQ5FsI5oeNJi8dnNMX6I2w85k1cdO-L0jERW99qQi0juBok9kKS6DZ8jrY3ScPZBKdX3EgnFBoyTjq1dKPwdVJvwwf4R2StDAXYyGIbcR5HlKWktI6X4ITR9KaPw75LhCeczYf9Shypl1O8bJnuQPhPFCl6rXJ1UT99WVPiF14s6SmUUTT3jU4wLwlYXE0iLkKQ1g',
            'grpcSignature' => '05f7a13c9d540b311c079bf3ae4a36d9',
            'providerCode' => 'PLA',
            'apiUrl' => 'https://api.torrospin.com/',
            'apiKey' => 'c936445a26be64a5d343a7224a65abf186db86bb0193d02ba22b695a6282625c',
            'secretKey' => 'bbe242e7fade53a08a4cf0743a59618b5e03cbf94b4aa6a450a4c65155f34466',
            'isLiveGame' => false,
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('USD');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    public static function credentialParams()
    {
        return [
            ['grpcHost'],
            ['grpcPort'],
            ['grpcToken'],
            ['grpcSignature'],
            ['providerCode'],
            ['apiUrl'],
            ['apiKey'],
            ['secretKey'],
            ['isLiveGame']
        ];
    }

    public function getCredentialValue($credentials, $field)
    {
        switch ($field) {
            case 'grpcHost':
                return $credentials->getGrpcHost();
            case 'grpcPort':
                return $credentials->getGrpcPort();
            case 'grpcToken':
                return $credentials->getGrpcToken();
            case 'grpcSignature':
                return $credentials->getGrpcSignature();
            case 'providerCode':
                return $credentials->getProviderCode();
            case 'apiUrl':
                return $credentials->getApiUrl();
            case 'apiKey':
                return $credentials->getApiKey();
            case 'secretKey':
                return $credentials->getSecretKey();
            case 'isLiveGame':
                return $credentials->isLiveGame();
            default:
                return null;
        }
    }
}
