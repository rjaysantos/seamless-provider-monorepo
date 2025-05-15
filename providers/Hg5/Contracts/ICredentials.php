<?php

namespace Providers\Hg5\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials
{
    public function getApiUrl(): string;
    public function getAgentID(): int;
    public function getAuthorizationToken(): string;
}
