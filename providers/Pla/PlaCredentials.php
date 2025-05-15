<?php

namespace App\GameProviders\V2\PLA;

use App\GameProviders\V2\PLA\Credentials\PlaIDR;
use App\GameProviders\V2\PLA\Credentials\PlaMYR;
use App\GameProviders\V2\PLA\Credentials\PlaPHP;
use App\GameProviders\V2\PLA\Credentials\PlaTHB;
use App\GameProviders\V2\PLA\Credentials\PlaUSD;
use App\GameProviders\V2\PLA\Credentials\PlaVND;
use App\GameProviders\V2\PLA\Contracts\ICredentials;
use App\GameProviders\V2\PLA\Credentials\PlaStaging;
use App\GameProviders\V2\PCA\Contracts\ICredentialSetter;

class PlaCredentials implements ICredentialSetter
{
    public function getCredentialsByCurrency(?string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            switch ($currency) {
                case 'IDR':
                    return new PlaIDR;
                case 'PHP':
                    return new PlaPHP;
                case 'THB':
                    return new PlaTHB;
                case 'VND':
                    return new PlaVND;
                case 'USD':
                    return new PlaUSD;
                case 'MYR':
                    return new PlaMYR;
            }
        }

        return new PlaStaging;
    }
}
