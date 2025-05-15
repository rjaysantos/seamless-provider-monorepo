<?php

namespace App\GameProviders\V2\PCA\Contracts;

use Illuminate\Http\Request;
use App\GameProviders\V2\PLA\Contracts\ICredentials;

interface IApi
{
    public function getGameLaunchUrl(ICredentials $credentials, Request $request, string $token): string;
    public function gameRoundStatus(ICredentials $credentials, string $transactionID): string;
    public function setPlayerTags(ICredentials $credentials, Request $request, string $playID): void;
}