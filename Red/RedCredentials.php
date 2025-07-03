<?php

namespace Providers\Red;

use Providers\Red\Credentials\RedBRL;
use Providers\Red\Credentials\RedIDR;
use Providers\Red\Credentials\RedPHP;
use Providers\Red\Credentials\RedTHB;
use Providers\Red\Credentials\RedUSD;
use Providers\Red\Credentials\RedVND;
use Providers\Red\Contracts\ICredentials;
use Providers\Red\Credentials\RedStaging;
use App\Exceptions\Casino\InvalidCurrencyException;

class RedCredentials
{
    public function getCredentialsByCurrency(string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            return match (strtoupper($currency)) {
                'IDR' => new RedIDR,
                'PHP' => new RedPHP,
                'THB' => new RedTHB,
                'VND' => new RedVND,
                'BRL' => new RedBRL,
                'USD' => new RedUSD,
                default => throw new InvalidCurrencyException
            };
        }

        return new RedStaging;
    }
}
