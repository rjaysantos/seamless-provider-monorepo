<?php

namespace Providers\Red\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials
{
    public function getApiUrl(): string;
    public function getPrdID(): int;
    public function getCode(): string;
    public function getToken(): string;
    public function getSecretKey(): string;
}
