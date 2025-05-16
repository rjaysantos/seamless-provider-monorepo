<?php

namespace Providers\Jdb;

use Providers\Jdb\Credentials\JdbBRL;
use Providers\Jdb\Credentials\JdbIDR;
use Providers\Jdb\Credentials\JdbPHP;
use Providers\Jdb\Credentials\JdbTHB;
use Providers\Jdb\Credentials\JdbUSD;
use Providers\Jdb\Credentials\JdbVND;
use Providers\Jdb\Contracts\ICredentials;
use Providers\Jdb\Credentials\JdbStaging;

class JdbCredentials
{
    public function getCredentialsByCurrency(string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            switch (strtoupper($currency)) {
                case 'IDR':
                    return new JdbIDR;
                case 'PHP':
                    return new JdbPHP;
                case 'THB':
                    return new JdbTHB;
                case 'VND':
                    return new JdbVND;
                case 'BRL':
                    return new JdbBRL;
                case 'USD':
                    return new JdbUSD;
            }
        }

        return new JdbStaging;
    }
}