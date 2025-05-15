<?php

namespace Providers\Bes\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials
{
    public function getCert(): string;
    public function getAgentID(): string;
    public function getApiUrl(): string;
    public function getNavigationApiUrl(): string;
    public function getNavigationApiBearerToken(): string;
}
