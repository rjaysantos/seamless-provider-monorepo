<?php

namespace Providers\Hg5;

use Providers\Hg5\Contracts\ICredentials;
use Providers\Hg5\Credentials\Hg5Staging;

class Hg5Credentials
{
    public function getCredentialsByCurrency(string $currency): ICredentials
    {
        return new Hg5Staging;
    }
}
