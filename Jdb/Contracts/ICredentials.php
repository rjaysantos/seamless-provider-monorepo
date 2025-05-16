<?php

namespace Providers\Jdb\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials
{
    public function getKey(): string;
    public function getIV(): string;
    public function getDC(): string;
    public function getApiUrl(): string;
    public function getParent(): string;
}