<?php

use Tests\TestCase;
use Providers\Pla\PlaCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class PlaCredentialsTest extends TestCase
{
    public function makeCredentialSetter()
    {
        return new PlaCredentials;
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaStaging_expected($field)
    {
        $expected = [
            'grpcHost' => '12.0.129.253',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTEzNDMsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMDljMGM2OTk5NzljNGYzYzE1OWNlZmRiNzdhYjk1NzciLCJzdWIiOiJBdWRTeXMifQ.KZdeKTRDG3ROXFH6GCt0J5P9Ahwi7fjXuQqNWasAGlEinXTAyQuDQDqolkmDNwKsqytAbjXmzfSY-0YiYNEGOcbcndwXKOsgmwWhqeaeqRycKr25gNQGJCC268UHKwrjZAXHStk9cS6AUs0DWqVzJyg2lDXW1AMBTZ8VNZ8bBiTkt24_iGlv0qIsGs4gxVSyHTV-mhQZwVCmjALc_ZwftCd2xDNwOSUPiQBpvk4jXiIukatvGTWg7k5FPtjRtMYX9M4V9yBHckbVyV98jcQy-PdmWuwlQ2q_DEg5cLgRGdwN3P9GYvh6YwaQqxw5I_kUlZi67_HeGKSL77s3WsvX8g',
            'grpcSignature' => 'd112418755d72795a402fc33a6ba8035',
            'providerCode' => 'PLA',
            'apiUrl' => 'https://api-uat.agmidway.net',
            'getKioskKey' => '4d45ab9bee2ab5a924629d18e5f07606cbfeb5fd7c0d2de2b13cab42ee966a1c',
            'getKioskName' => 'PLAUCN',
            'getServerName' => 'AGCASTG',
            'getAdminKey' => '5d4cf20f73ca4413060d41cf2733c64c7d7b93a03f7f4fdebd9c9a660f8a0dab',
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

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaUSD_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE0NzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNzkzZmZmYzBhZWZkZGI3YjMwYjFkNTUzMTgyZjFiMDAiLCJzdWIiOiJBdWRTeXMifQ.TJ_BTNHNEI0c09qCaiU-rdkkuQ3LYB-5oh-c2vaDOOpAy7rjoD4_EeggILG3xQb-koyN2mUe3ZB_51QumxqoD743oeJlG3VDc9NJgG1Ru0PQ-6z8wRpnHeEJmV_87zNQd4uAwM86H0YL0FbwReQ5FsI5oeNJi8dnNMX6I2w85k1cdO-L0jERW99qQi0juBok9kKS6DZ8jrY3ScPZBKdX3EgnFBoyTjq1dKPwdVJvwwf4R2StDAXYyGIbcR5HlKWktI6X4ITR9KaPw75LhCeczYf9Shypl1O8bJnuQPhPFCl6rXJ1UT99WVPiF14s6SmUUTT3jU4wLwlYXE0iLkKQ1g',
            'grpcSignature' => '05f7a13c9d540b311c079bf3ae4a36d9',
            'providerCode' => 'PLA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => '15b6b114f9ff5040daed223cff7dee94792d5c3569e78890044456a2b6941d2e',
            'getKioskName' => 'PLAUS',
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
        $credentials = $credentialSetter->getCredentialsByCurrency('USD');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaMYR_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE0NzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNzkzZmZmYzBhZWZkZGI3YjMwYjFkNTUzMTgyZjFiMDAiLCJzdWIiOiJBdWRTeXMifQ.TJ_BTNHNEI0c09qCaiU-rdkkuQ3LYB-5oh-c2vaDOOpAy7rjoD4_EeggILG3xQb-koyN2mUe3ZB_51QumxqoD743oeJlG3VDc9NJgG1Ru0PQ-6z8wRpnHeEJmV_87zNQd4uAwM86H0YL0FbwReQ5FsI5oeNJi8dnNMX6I2w85k1cdO-L0jERW99qQi0juBok9kKS6DZ8jrY3ScPZBKdX3EgnFBoyTjq1dKPwdVJvwwf4R2StDAXYyGIbcR5HlKWktI6X4ITR9KaPw75LhCeczYf9Shypl1O8bJnuQPhPFCl6rXJ1UT99WVPiF14s6SmUUTT3jU4wLwlYXE0iLkKQ1g',
            'grpcSignature' => '05f7a13c9d540b311c079bf3ae4a36d9',
            'providerCode' => 'PLA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => 'a4866ad9e12312c74e9349f3d3e1cc7f8e8933abd7f2d90ed5d19507d20ef20d',
            'getKioskName' => 'PLAMY',
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
        $credentials = $credentialSetter->getCredentialsByCurrency('MYR');

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