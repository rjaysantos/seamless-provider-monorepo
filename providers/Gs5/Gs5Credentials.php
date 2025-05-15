<?php

namespace Providers\Gs5;

use Providers\Gs5\Contracts\ICredentials;
use Providers\Gs5\Credentials\Gs5Staging;

class Gs5Credentials
{
    public function getCredentialsByCurrency(string $currency): ICredentials
    {
        return new Gs5Staging;
    }
}
