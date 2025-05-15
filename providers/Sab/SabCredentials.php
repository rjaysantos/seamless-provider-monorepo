<?php

namespace Providers\Sab;

use Providers\Sab\Credentials\SabBRL;
use Providers\Sab\Credentials\SabTHB;
use Providers\Sab\Credentials\SabUSD;
use Providers\Sab\Credentials\SabIDRK;
use Providers\Sab\Credentials\SabVNDK;
use Providers\Sab\Contracts\ICredentials;
use Providers\Sab\Credentials\SabStaging;
use Providers\Sab\Credentials\SabStagingKCurrency;

class SabCredentials
{
    public function getCredentialsByCurrency(string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            switch ($currency) {
                case 'IDR':
                    return new SabIDRK;
                case 'THB':
                    return new SabTHB;
                case 'VND':
                    return new SabVNDK;
                case 'BRL':
                    return new SabBRL;
                case 'USD':
                    return new SabUSD;
            }
        }

        switch ($currency) {
            case 'IDR':
            case 'VND':
                return new SabStagingKCurrency;
            default:
                return new SabStaging;
        }
    }
}
