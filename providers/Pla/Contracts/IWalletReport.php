<?php

namespace App\GameProviders\V2\PCA\Contracts;

use App\GameProviders\V2\PLA\Contracts\ICredentials;
use Wallet\V1\ProvSys\Transfer\Report;

interface IWalletReport
{
    public function makeReport(
        ICredentials $credentials,
        string $transactionID,
        string $gameCode,
        string $betTime
    ): Report;
}