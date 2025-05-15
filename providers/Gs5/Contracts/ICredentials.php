<?php

namespace Providers\Gs5\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials
{
    public function getApiUrl(): string;
    public function getHostID(): string;
    public function getToken(): string;
}
