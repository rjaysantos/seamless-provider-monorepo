<?php

namespace Providers\Bes;

use Providers\Bes\Credentials\BesIDR;
use Providers\Bes\Credentials\Staging;
use Providers\Bes\Contracts\ICredentials;

class BesCredentials
{
    public function getCredentialsByCurrency(string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            return match ($currency) {
                'IDR' => new BesIDR
            };
        }

        return new Staging;
    }
}
