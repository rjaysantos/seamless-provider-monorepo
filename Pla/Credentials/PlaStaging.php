<?php

namespace Providers\Pla\Credentials;

use Providers\Pla\Contracts\ICredentials;

class PlaStaging implements ICredentials
{
    public function getGrpcHost(): string
    {
        return '12.0.129.253';
    }

    public function getGrpcPort(): string
    {
        return '3939';
    }

    public function getGrpcToken(): string
    {
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTEzNDMsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMDljMGM2OTk5NzljNGYzYzE1OWNlZmRiNzdhYjk1NzciLCJzdWIiOiJBdWRTeXMifQ.KZdeKTRDG3ROXFH6GCt0J5P9Ahwi7fjXuQqNWasAGlEinXTAyQuDQDqolkmDNwKsqytAbjXmzfSY-0YiYNEGOcbcndwXKOsgmwWhqeaeqRycKr25gNQGJCC268UHKwrjZAXHStk9cS6AUs0DWqVzJyg2lDXW1AMBTZ8VNZ8bBiTkt24_iGlv0qIsGs4gxVSyHTV-mhQZwVCmjALc_ZwftCd2xDNwOSUPiQBpvk4jXiIukatvGTWg7k5FPtjRtMYX9M4V9yBHckbVyV98jcQy-PdmWuwlQ2q_DEg5cLgRGdwN3P9GYvh6YwaQqxw5I_kUlZi67_HeGKSL77s3WsvX8g';
    }

    public function getGrpcSignature(): string
    {
        return 'd112418755d72795a402fc33a6ba8035';
    }

    public function getProviderCode(): string
    {
        return 'PLA';
    }

    public function getApiUrl(): string
    {
        return 'https://api-uat.agmidway.net';
    }

    public function getKioskKey(): string
    {
        return '4d45ab9bee2ab5a924629d18e5f07606cbfeb5fd7c0d2de2b13cab42ee966a1c';
    }

    public function getKioskName(): string
    {
        return 'PLAUCN';
    }

    public function getServerName(): string
    {
        return 'AGCASTG';
    }

    public function getAdminKey(): string
    {
        return '5d4cf20f73ca4413060d41cf2733c64c7d7b93a03f7f4fdebd9c9a660f8a0dab';
    }

    public function getArcadeGameList(): array
    {
        return [
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
        ];
    }
}
