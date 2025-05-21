<?php

namespace Providers\Pca\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials
{
    public function getApiUrl(): string;
    public function getKioskKey(): string;
    public function getKioskName(): string;
    public function getServerName(): string;
    public function getAdminKey(): string;
}
