<?php

namespace Providers\Sbo\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials
{
    public function getCompanyKey(): string;
    public function getServerID(): string;
    public function getAgent(): string;
    public function getApiUrl(): string;
}
