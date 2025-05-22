<?php

namespace Providers\Pca;

use Providers\Pca\Credentials\PcaIDR;
use Providers\Pca\Credentials\PcaMYR;
use Providers\Pca\Credentials\PcaPHP;
use Providers\Pca\Credentials\PcaTHB;
use Providers\Pca\Credentials\PcaUSD;
use Providers\Pca\Credentials\PcaVND;
use Providers\Pca\Contracts\ICredentials;
use Providers\Pca\Credentials\PcaStaging;

class PcaCredentials
{
    public function getCredentialsByCurrency(?string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            return match ($currency) {
                'IDR' => new PcaIDR,
                'PHP' => new PcaPHP,
                'THB' => new PcaTHB,
                'VND' => new PcaVND,
                'USD' => new PcaUSD,
                'MYR' => new PcaMYR,
            };
        }

        return new PcaStaging;
    }
}