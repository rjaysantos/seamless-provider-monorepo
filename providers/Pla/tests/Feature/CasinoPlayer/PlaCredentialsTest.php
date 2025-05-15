<?php

use Tests\TestCase;
use App\GameProviders\V2\PLA\PlaCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class PlaCredentialsTest extends TestCase
{
    public function makeCredentialSetter()
    {
        return new PlaCredentials;
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaIDR_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE0NzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNzkzZmZmYzBhZWZkZGI3YjMwYjFkNTUzMTgyZjFiMDAiLCJzdWIiOiJBdWRTeXMifQ.TJ_BTNHNEI0c09qCaiU-rdkkuQ3LYB-5oh-c2vaDOOpAy7rjoD4_EeggILG3xQb-koyN2mUe3ZB_51QumxqoD743oeJlG3VDc9NJgG1Ru0PQ-6z8wRpnHeEJmV_87zNQd4uAwM86H0YL0FbwReQ5FsI5oeNJi8dnNMX6I2w85k1cdO-L0jERW99qQi0juBok9kKS6DZ8jrY3ScPZBKdX3EgnFBoyTjq1dKPwdVJvwwf4R2StDAXYyGIbcR5HlKWktI6X4ITR9KaPw75LhCeczYf9Shypl1O8bJnuQPhPFCl6rXJ1UT99WVPiF14s6SmUUTT3jU4wLwlYXE0iLkKQ1g',
            'grpcSignature' => '05f7a13c9d540b311c079bf3ae4a36d9',
            'providerCode' => 'PLA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => '613e5d694b0a17f5d22fe3bd3b031e2494c3f23cdc3efe8b9d94e4b803979e36',
            'getKioskName' => 'PLAID',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '5b1f3ec393c9fbd072d2e14642dfe902c596258112fac46492403b3fb24b3ce3',
            'getArcadeGameList' => [
                'db1000ro',
                'ro101',
                '3cb',
                'sem',
                'ba',
                'baws',
                'bafr',
                'mobbj',
                'mbj',
                'bjsd2',
                'bjs',
                'bjbu',
                'ccro',
                'ccrobf',
                'crit',
                'cpx5k',
                'gpas_critmp_pop',
                'bjcb',
                'cheaa',
                'circ',
                'ro',
                'dbro',
                'eufro',
                'fishshr',
                'fcbj',
                'hilop',
                'huh',
                'po',
                'jbc',
                'gpas_kickitmp_pop',
                'bjll',
                'mfbro',
                'mro',
                'mpbj',
                'mpro',
                'prol',
                'pfbj_mh5',
                'rodz_g',
                'bjto_sh',
                'bjto',
                'ro_g',
                'frr_g',
                'qbjp',
                'qro',
                'presrol',
                'mro_g',
                'shsfc',
                'lwh',
                'stywro',
                'sbro',
                'supro',
                'bj65'
            ]
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('IDR');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaPHP_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE0NzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNzkzZmZmYzBhZWZkZGI3YjMwYjFkNTUzMTgyZjFiMDAiLCJzdWIiOiJBdWRTeXMifQ.TJ_BTNHNEI0c09qCaiU-rdkkuQ3LYB-5oh-c2vaDOOpAy7rjoD4_EeggILG3xQb-koyN2mUe3ZB_51QumxqoD743oeJlG3VDc9NJgG1Ru0PQ-6z8wRpnHeEJmV_87zNQd4uAwM86H0YL0FbwReQ5FsI5oeNJi8dnNMX6I2w85k1cdO-L0jERW99qQi0juBok9kKS6DZ8jrY3ScPZBKdX3EgnFBoyTjq1dKPwdVJvwwf4R2StDAXYyGIbcR5HlKWktI6X4ITR9KaPw75LhCeczYf9Shypl1O8bJnuQPhPFCl6rXJ1UT99WVPiF14s6SmUUTT3jU4wLwlYXE0iLkKQ1g',
            'grpcSignature' => '05f7a13c9d540b311c079bf3ae4a36d9',
            'providerCode' => 'PLA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => 'ddb0c7f59d10234ad4bafe086f65e44816c05afbf946abc26668ef56c68dbb3e',
            'getKioskName' => 'PLAPH',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '5b1f3ec393c9fbd072d2e14642dfe902c596258112fac46492403b3fb24b3ce3',
            'getArcadeGameList' => [
                'db1000ro',
                'ro101',
                '3cb',
                'sem',
                'ba',
                'baws',
                'bafr',
                'mobbj',
                'mbj',
                'bjsd2',
                'bjs',
                'bjbu',
                'ccro',
                'ccrobf',
                'crit',
                'cpx5k',
                'gpas_critmp_pop',
                'bjcb',
                'cheaa',
                'circ',
                'ro',
                'dbro',
                'eufro',
                'fishshr',
                'fcbj',
                'hilop',
                'huh',
                'po',
                'jbc',
                'gpas_kickitmp_pop',
                'bjll',
                'mfbro',
                'mro',
                'mpbj',
                'mpro',
                'prol',
                'pfbj_mh5',
                'rodz_g',
                'bjto_sh',
                'bjto',
                'ro_g',
                'frr_g',
                'qbjp',
                'qro',
                'presrol',
                'mro_g',
                'shsfc',
                'lwh',
                'stywro',
                'sbro',
                'supro',
                'bj65'
            ]
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('PHP');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaTHB_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE0NzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNzkzZmZmYzBhZWZkZGI3YjMwYjFkNTUzMTgyZjFiMDAiLCJzdWIiOiJBdWRTeXMifQ.TJ_BTNHNEI0c09qCaiU-rdkkuQ3LYB-5oh-c2vaDOOpAy7rjoD4_EeggILG3xQb-koyN2mUe3ZB_51QumxqoD743oeJlG3VDc9NJgG1Ru0PQ-6z8wRpnHeEJmV_87zNQd4uAwM86H0YL0FbwReQ5FsI5oeNJi8dnNMX6I2w85k1cdO-L0jERW99qQi0juBok9kKS6DZ8jrY3ScPZBKdX3EgnFBoyTjq1dKPwdVJvwwf4R2StDAXYyGIbcR5HlKWktI6X4ITR9KaPw75LhCeczYf9Shypl1O8bJnuQPhPFCl6rXJ1UT99WVPiF14s6SmUUTT3jU4wLwlYXE0iLkKQ1g',
            'grpcSignature' => '05f7a13c9d540b311c079bf3ae4a36d9',
            'providerCode' => 'PLA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => 'e90bea04c55e002fce910de8268ab215526008594592bb8522e332748bbe6d05',
            'getKioskName' => 'PLATH',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '5b1f3ec393c9fbd072d2e14642dfe902c596258112fac46492403b3fb24b3ce3',
            'getArcadeGameList' => [
                'db1000ro',
                'ro101',
                '3cb',
                'sem',
                'ba',
                'baws',
                'bafr',
                'mobbj',
                'mbj',
                'bjsd2',
                'bjs',
                'bjbu',
                'ccro',
                'ccrobf',
                'crit',
                'cpx5k',
                'gpas_critmp_pop',
                'bjcb',
                'cheaa',
                'circ',
                'ro',
                'dbro',
                'eufro',
                'fishshr',
                'fcbj',
                'hilop',
                'huh',
                'po',
                'jbc',
                'gpas_kickitmp_pop',
                'bjll',
                'mfbro',
                'mro',
                'mpbj',
                'mpro',
                'prol',
                'pfbj_mh5',
                'rodz_g',
                'bjto_sh',
                'bjto',
                'ro_g',
                'frr_g',
                'qbjp',
                'qro',
                'presrol',
                'mro_g',
                'shsfc',
                'lwh',
                'stywro',
                'sbro',
                'supro',
                'bj65'
            ]
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('THB');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaVND_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE0NzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNzkzZmZmYzBhZWZkZGI3YjMwYjFkNTUzMTgyZjFiMDAiLCJzdWIiOiJBdWRTeXMifQ.TJ_BTNHNEI0c09qCaiU-rdkkuQ3LYB-5oh-c2vaDOOpAy7rjoD4_EeggILG3xQb-koyN2mUe3ZB_51QumxqoD743oeJlG3VDc9NJgG1Ru0PQ-6z8wRpnHeEJmV_87zNQd4uAwM86H0YL0FbwReQ5FsI5oeNJi8dnNMX6I2w85k1cdO-L0jERW99qQi0juBok9kKS6DZ8jrY3ScPZBKdX3EgnFBoyTjq1dKPwdVJvwwf4R2StDAXYyGIbcR5HlKWktI6X4ITR9KaPw75LhCeczYf9Shypl1O8bJnuQPhPFCl6rXJ1UT99WVPiF14s6SmUUTT3jU4wLwlYXE0iLkKQ1g',
            'grpcSignature' => '05f7a13c9d540b311c079bf3ae4a36d9',
            'providerCode' => 'PLA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => 'b83b68e6e804ce2f0c0d4cb2443b454f20e994ca90940d29608f1642c2c3d00e',
            'getKioskName' => 'PLAVN',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '5b1f3ec393c9fbd072d2e14642dfe902c596258112fac46492403b3fb24b3ce3',
            'getArcadeGameList' => [
                'db1000ro',
                'ro101',
                '3cb',
                'sem',
                'ba',
                'baws',
                'bafr',
                'mobbj',
                'mbj',
                'bjsd2',
                'bjs',
                'bjbu',
                'ccro',
                'ccrobf',
                'crit',
                'cpx5k',
                'gpas_critmp_pop',
                'bjcb',
                'cheaa',
                'circ',
                'ro',
                'dbro',
                'eufro',
                'fishshr',
                'fcbj',
                'hilop',
                'huh',
                'po',
                'jbc',
                'gpas_kickitmp_pop',
                'bjll',
                'mfbro',
                'mro',
                'mpbj',
                'mpro',
                'prol',
                'pfbj_mh5',
                'rodz_g',
                'bjto_sh',
                'bjto',
                'ro_g',
                'frr_g',
                'qbjp',
                'qro',
                'presrol',
                'mro_g',
                'shsfc',
                'lwh',
                'stywro',
                'sbro',
                'supro',
                'bj65'
            ]
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('VND');

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
            ['getKioskKey'],
            ['getKioskName'],
            ['getServerName'],
            ['getAdminKey'],
            ['getArcadeGameList']
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
            case 'getKioskKey':
                return $credentials->getKioskKey();
            case 'getKioskName':
                return $credentials->getKioskName();
            case 'getServerName':
                return $credentials->getServerName();
            case 'getAdminKey':
                return $credentials->getAdminKey();
            case 'getArcadeGameList':
                return $credentials->getArcadeGameList();
            default:
                return null;
        }
    }
}