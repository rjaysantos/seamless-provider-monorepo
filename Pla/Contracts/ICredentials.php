<?php

namespace App\GameProviders\V2\PLA\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials, IPlaGameList
{
    public function getApiUrl(): string;
    public function getKioskKey(): string;
    public function getKioskName(): string;
    public function getServerName(): string;
    public function getAdminKey(): string;
}
