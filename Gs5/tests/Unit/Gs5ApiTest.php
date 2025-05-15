<?php

use Tests\TestCase;
use Providers\Gs5\Gs5Api;
use Providers\Gs5\Credentials\Gs5Staging;
use Providers\Gs5\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Gs5ApiTest extends TestCase
{
    public function makeApi()
    {
        return new Gs5Api;
    }

    #[DataProvider('languageProvider')]
    public function test_getLaunchUrl_givenData_expected($lang, $expectedLanguage)
    {
        $credentials = new Gs5Staging;
        $playerToken = 'testToken';
        $gameID = 'testGameID';

        $apiRequest = [
            'host_id' => $credentials->getHostID(),
            'game_id' => $gameID,
            'lang' => $expectedLanguage,
            'access_token' => $playerToken
        ];

        $expected = $credentials->getApiUrl() . '/launch/?' . http_build_query($apiRequest);

        $api = $this->makeApi();
        $result = $api->getLaunchUrl(
            credentials: $credentials,
            playerToken: $playerToken,
            gameID: $gameID,
            lang: $lang
        );

        $this->assertSame($expected, $result);
    }

    public static function languageProvider()
    {
        return [
            ['en', 'en-US'],
            ['tl', 'en-US'],
            ['id', 'id-ID'],
            ['th', 'th-TH'],
            ['vn', 'vi-VN'],
            ['cn', 'zh-CN']
        ];
    }

    public function test_getGameHistory_stubResponse_expectedData()
    {
        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getToken')->willReturn('testToken');
        $stubProviderCredentials->method('getApiUrl')->willReturn('testVisual.com');

        $trxID = 'testTransactionID';

        $expectedRequest = [
            'token' => 'testToken',
            'sn' => $trxID
        ];

        $expectedResponse = 'testVisual.com/Resource/game_history?' . http_build_query($expectedRequest);

        $api = $this->makeApi();
        $response = $api->getGameHistory(credentials: $stubProviderCredentials, trxID: $trxID);

        $this->assertSame(expected: $expectedResponse, actual: $response);
    }
}