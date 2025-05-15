<?php

namespace Providers\Sbo;

use Providers\Sbo\Credentials\SboIDR;
use Providers\Sbo\Credentials\SboTHB;
use Providers\Sbo\Credentials\SboVND;
use Providers\Sbo\Credentials\SboBRL;
use Providers\Sbo\Credentials\SboUSD;
use Providers\Sbo\Contracts\ICredentials;
use Providers\Sbo\Credentials\SboStaging;

class SboCredentials
{
    public function getCredentialsByCurrency(string $currency): ?ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            return match ($currency) {
                'IDR' => new SboIDR,
                'THB' => new SboTHB,
                'VND' => new SboVND,
                'BRL' => new SboBRL,
                'USD' => new SboUSD,
                default => null
            };
        }

        return new SboStaging;
    }
}
