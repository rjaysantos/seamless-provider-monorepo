<?php

namespace App\GameProviders\V2\PCA\Contracts;

use App\GameProviders\V2\PLA\Contracts\ICredentials;

interface ICredentialSetter 
{
    public function getCredentialsByCurrency(?string $currency): ICredentials;
}