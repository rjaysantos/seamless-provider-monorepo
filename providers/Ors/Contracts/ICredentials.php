<?php

namespace Providers\Ors\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials, IOrsGameList
{
    public function getApiUrl(): string;
    public function getOperatorName(): string;
    public function getPublicKey(): string;
    public function getPrivateKey(): string;
}
