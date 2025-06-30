<?php

namespace Providers\Gs5;

use App\DTO\CasinoRequestDTO;
use Providers\Gs5\Contracts\ICredentials;
use Providers\Gs5\DTO\Gs5PlayerDTO;

class Gs5Api
{
    private function getProviderLanguage(string $language): string
    {
        return match ($language) {
            'id' => 'id-ID',
            'th' => 'th-TH',
            'vn' => 'vi-VN',
            'cn' => 'zh-CN',
            default => 'en-US'
        };
    }

    public function getLaunchUrl(
        ICredentials $credentials,
        Gs5PlayerDTO $playerDTO,
        CasinoRequestDTO $casinoRequestDTO,
    ): string {
        $apiRequest = http_build_query([
            'host_id' => $credentials->getHostID(),
            'game_id' => $playerDTO->gameCode,
            'lang' => $this->getProviderLanguage(language: $casinoRequestDTO->lang),
            'access_token' => $playerDTO->token
        ]);

        return "{$credentials->getApiUrl()}/launch/?{$apiRequest}";
    }

    public function getGameHistory(ICredentials $credentials, string $trxID): string
    {
        $apiRequest = http_build_query([
            'token' => $credentials->getToken(),
            'sn' => $trxID
        ]);

        return "{$credentials->getApiUrl()}/Resource/game_history?{$apiRequest}";
    }
}
