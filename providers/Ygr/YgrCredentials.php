<?php

namespace Providers\Ygr;

use Providers\Ygr\Contracts\ICredentials;
use Providers\Ygr\Credentials\YgrStaging;
use Providers\Ygr\Credentials\YgrProduction;

class YgrCredentials
{
    public function getCredentials(): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            return new YgrProduction;
        }

        return new YgrStaging;
    }
}