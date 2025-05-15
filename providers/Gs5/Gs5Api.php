<?php

namespace Providers\Gs5;

use Providers\Gs5\Contracts\ICredentials;

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
        string $playerToken,
        string $gameID,
        ?string $lang = null
    ): string {
        $apiRequest = http_build_query([
            'host_id' => $credentials->getHostID(),
            'game_id' => $gameID,
            'lang' => $this->getProviderLanguage(language: $lang),
            'access_token' => $playerToken
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