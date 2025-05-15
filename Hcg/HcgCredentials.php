<?php

namespace Providers\Hcg;

use Providers\Hcg\Credentials\HcgBRL;
use Providers\Hcg\Credentials\HcgPHP;
use Providers\Hcg\Credentials\HcgTHB;
use Providers\Hcg\Credentials\HcgUSD;
use Providers\Hcg\Credentials\HcgIDRK;
use Providers\Hcg\Credentials\HcgVNDK;
use Providers\Hcg\Contracts\ICredentials;
use Providers\Hcg\Credentials\HcgStagingIDR;
use Providers\Hcg\Credentials\HcgStagingPHP;

class HcgCredentials
{
    public function getCredentialsByCurrency(string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            switch (strtoupper($currency)) {
                case 'IDR':
                    return new HcgIDRK;
                case 'PHP':
                    return new HcgPHP;
                case 'THB':
                    return new HcgTHB;
                case 'VND':
                    return new HcgVNDK;
                case 'BRL':
                    return new HcgBRL;
                case 'USD':
                    return new HcgUSD;
            }
        }

        if (strtoupper($currency) === 'PHP')
            return new HcgStagingPHP;

        return new HcgStagingIDR;
    }
}