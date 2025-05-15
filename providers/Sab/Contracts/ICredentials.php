<?php

namespace Providers\Sab\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials
{
    public function getApiUrl(): string;
    public function getVendorID(): string;
    public function getOperatorID(): string;
    public function getCurrency(): int;
    public function getSuffix(): string;
    public function getCurrencyConversion(): int;
}
