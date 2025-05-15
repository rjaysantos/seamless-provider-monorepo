<?php

namespace Providers\Ors;

use Providers\Ors\Credentials\OrsBRL;
use Providers\Ors\Credentials\OrsIDR;
use Providers\Ors\Credentials\OrsPHP;
use Providers\Ors\Credentials\OrsTHB;
use Providers\Ors\Credentials\OrsUSD;
use Providers\Ors\Credentials\OrsVND;
use Providers\Ors\Contracts\ICredentials;
use Providers\Ors\Credentials\OrsStaging;

class OrsCredentials
{
    public function getCredentialsByCurrency(string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            return match (strtoupper($currency)) {
                'IDR' => new OrsIDR,
                'PHP' => new OrsPHP,
                'THB' => new OrsTHB,
                'VND' => new OrsVND,
                'BRL' => new OrsBRL,
                'USD' => new OrsUSD,
            };
        }

        return new OrsStaging;
    }
}
