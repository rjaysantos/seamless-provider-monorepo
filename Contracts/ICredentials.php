<?php

namespace Providers\Ygr\Contracts;

use Providers\Ygr\Contracts\IYgrGameList;
use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials, IYgrGameList
{
    public function getApiUrl(): string;
    public function getVendorID(): string;
}
