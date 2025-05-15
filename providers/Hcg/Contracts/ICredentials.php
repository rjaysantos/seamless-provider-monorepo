<?php

namespace Providers\Hcg\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface ICredentials extends IWalletCredentials
{
    public function getApiUrl(): string;
    public function getSignKey(): string;
    public function getEncryptionKey(): string;
    public function getAppID(): string;
    public function getAppSecret(): string;
    public function getAgentID(): string;
    public function getVisualUrl(): string;
    public function getWalletApiSignKey(): string;
    public function getCurrencyConversion(): int;
    public function getTransactionIDPrefix(): string;
}