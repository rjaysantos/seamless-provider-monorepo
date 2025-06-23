<?php

namespace Providers\Ygr;

use Providers\Ygr\Contracts\ICredentials;
use Providers\Ygr\Credentials\YgrStaging;
use Providers\Ygr\Credentials\YgrProduction;
use App\Exceptions\Casino\InvalidCurrencyException;

class YgrCredentials
{
    public function getCredentials(string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            return match (strtoupper($currency)) {
                'IDR', 'PHP', 'THB', 'VND', 'BRL', 'USD' => new YgrProduction,
                default => throw new InvalidCurrencyException
            };
        }

        return new YgrStaging;
    }
}
