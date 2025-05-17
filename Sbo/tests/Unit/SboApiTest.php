<?php

use Tests\TestCase;
use Providers\Sbo\SboApi;
use App\Libraries\LaravelHttpClient;
use Providers\Sbo\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class SboApiTest extends TestCase
{
    private function makeApi($http = null): SboApi
    {
        $http ??= $this->createStub(LaravelHttpClient::class);

        return new SboApi(http: $http);
    }

    public function test_getBetList_mockHttp_post()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');
        $providerCredentials->method('getServerID')->willReturn('testServerID');

        $request = [
            'companyKey' => 'testCompanyKey',
            'serverId' => 'testServerID',
            'refnos' => 'testTransactionID',
            'portfolio' => 'SportsBook',
            'language' => 'en'
        ];

        $apiHeader = ['Content-Type' => 'application/json'];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('post')
            ->with(
                url: 'http://testApiUrl.com/web-root/restricted/report/get-bet-list-by-refnos.aspx',
                request: $request,
                header: $apiHeader
            )
            ->willReturn((object) [
                'result' => [
                    (object) [
                        'subBet' => [
                            (object) [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'sportType' => 'Football',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => 'Over',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => (object) [
                    'id' => 0
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getBetList(credentials: $providerCredentials, trxID: 'testTransactionID');
    }

    public function test_getBetList_stubHttp_expectedData()
    {
        $expectedData = (object) [
            'subBet' => [
                (object) []
            ],
            'oddsStyle' => 'E'
        ];

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');
        $providerCredentials->method('getServerID')->willReturn('testServerID');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'result' => [
                    (object) [
                        'subBet' => [
                            (object) []
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => (object) [
                    'id' => 0
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $result = $api->getBetList(credentials: $providerCredentials, trxID: 'testTransactionID');

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    public function test_getBetList_stubHttpErrorIDNot0_ThirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');
        $providerCredentials->method('getServerID')->willReturn('testServerID');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'result' => [
                    (object) [
                        'subBet' => [
                            (object) []
                        ],
                    ]
                ],
                'error' => (object) [
                    'id' => 1
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetList(credentials: $providerCredentials, trxID: 'testTransactionID');
    }

    #[DataProvider('getBetListResponse')]
    public function test_getBetList_invalidThirdPartyApiResponseParameter_thirdPartyApiErrorException($param, $value)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = [
            'result' => [
                (object) [
                    'subBet' => [
                        (object) []
                    ],
                ]
            ],
            'error' => (object) [
                'id' => 0
            ]
        ];

        if ($param === 'result' || $param === 'error')
            $apiResponse[$param] = $value;
        else
            $apiResponse['error']->$param = $value;

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');
        $providerCredentials->method('getServerID')->willReturn('testServerID');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetList(credentials: $providerCredentials, trxID: 'testTransactionID');
    }

    #[DataProvider('getBetListResponse')]
    public function test_getBetList_missingThirdPartyApiResponseParameter_thirdPartyApiErrorException($param)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = [
            'result' => [
                (object) [
                    'subBet' => [
                        (object) []
                    ],
                ]
            ],
            'error' => (object) [
                'id' => 0
            ]
        ];

        if ($param === 'result' || $param === 'error')
            unset($apiResponse[$param]);
        else
            unset($apiResponse['error']->$param);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');
        $providerCredentials->method('getServerID')->willReturn('testServerID');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetList(credentials: $providerCredentials, trxID: 'testTransactionID');
    }

    public static function getBetListResponse()
    {
        return [
            ['result', 'test'],
            ['error', 123],
            ['id', 'test'],
        ];
    }
}
