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
            switch ($currency) {
                case 'IDR':
                    return new PcaIDR;
                case 'PHP':
                    return new PcaPHP;
                case 'THB':
                    return new PcaTHB;
                case 'VND':
                    return new PcaVND;
                case 'USD':
                    return new PcaUSD;
                case 'MYR':
                    return new PcaMYR;
            }
        }

        return new PcaStaging;
    }
}