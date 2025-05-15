<?php

use Providers\Ors\Credentials\OrsStaging;
use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Ors\OgSignature;
use PHPUnit\Framework\Attributes\DataProvider;

class OgSignatureTest extends TestCase
{
    public function makeSignature()
    {
        return new OgSignature;
    }

    #[DataProvider('provider_ogSignatureRequestsJsonString')]
    public function test_isSignatureValid_givenDataJsonString_expected($requestData)
    {
        $request = new Request(json_decode($requestData, true), [], [], [], [], [], $requestData);

        $ogSignature = $this->makeSignature();
        $result = $ogSignature->isSignatureValid(request: $request, credentials: new OrsStaging);

        $this->assertTrue($result);
    }

    public static function provider_ogSignatureRequestsJsonString()
    {
        return [
            [
                '{
                    "player_id": "8dxw86xw6u027",
                    "timestamp": 1715071526,
                    "total_amount": 250,
                    "transaction_type": "debit",
                    "game_id": 123,
                    "round_id": "182xk5xvw5az7j",
                    "currency": "IDR",
                    "called_at": 1715071526,
                    "records": [
                        {
                            "transaction_id": "test_transacID_1",
                            "secondary_info": {},
                            "amount": 50,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "test_transacID_2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "a98f5302111b59052239f71f0382e883"
                }'
            ],
            [
                '{
                    "transaction_id": "uguhbkgvvu2gkn_74",
                    "secondary_info": {},
                    "amount": 30,
                    "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
                    "other_info": {},
                    "called_at": 1715052653,
                    "remark": {},
                    "bet_place": "BASEGAME",
                    "transaction_type": "credit",
                    "round_id": "uguhbkgvvu2gkn",
                    "effective_amount": 250,
                    "currency": "IDR",
                    "winlose_amount": -220,
                    "game_code": "pocketjungle",
                    "timestamp": 1715052653,
                    "player_id": "8dxw86xw6u027",
                    "game_id": 123,
                    "signature": "7b8aaf25d0862a16a5db9825d246430d"
                }'
            ],
            [
                '{
                    "player_id": "8dxw86xw6u027",
                    "timestamp": 1715051085,
                    "total_amount": 250,
                    "transaction_type": "debit",
                    "game_id": 123,
                    "round_id": "1e0e2s0vvt4usg",
                    "currency": "IDR",
                    "called_at": 1715051085,
                    "records": [
                        {
                            "transaction_id": "1e0e2s0vvt4usg_74",
                            "secondary_info": {},
                            "amount": 250,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "abb012400495e2a5f96116d4a3bf6553"
                }'
            ],
        ];
    }


    public function test_isSignatureValid_givenDataRequestArray_expected()
    {
        $requestData = [
            "player_id" => "8dxw86xw6u027",
            "timestamp" => 1715071526,
            "total_amount" => 250,
            "transaction_type" => "debit",
            "game_id" => 123,
            "round_id" => "182xk5xvw5az7j",
            "currency" => "IDR",
            "called_at" => 1715071526,
            "records" => [
                [
                    "transaction_id" => "test_transacID_1",
                    "secondary_info" => [],
                    "amount" => 50,
                    "other_info" => [],
                    "remark" => [],
                    "bet_place" => "BASEGAME"
                ],
                [
                    "transaction_id" => "test_transacID_2",
                    "secondary_info" => [],
                    "amount" => 100,
                    "other_info" => [],
                    "remark" => [],
                    "bet_place" => "BASEGAME"
                ]
            ],
            'signature' => '3f04098590a5eff7a85ad807694aa7ee'
        ];

        $request = new Request($requestData);

        $ogSignature = $this->makeSignature();
        $result = $ogSignature->isSignatureValid($request, credentials: new OrsStaging);

        $this->assertTrue($result);
    }

    public function test_isSignatureValid_invalidSignature_false()
    {
        $requestData = '{
            "player_id": "8dxw86xw6u027",
            "timestamp": 1715051085,
            "total_amount": 250,
            "transaction_type": "debit",
            "game_id": 123,
            "round_id": "1e0e2s0vvt4usg",
            "currency": "IDR",
            "called_at": 1715051085,
            "records": [
                {
                    "transaction_id": "1e0e2s0vvt4usg_74",
                    "secondary_info": {},
                    "amount": 250,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "invalid_signature"
        }';

        $request = new Request(json_decode($requestData, true), [], [], [], [], [], $requestData);

        $ogSignature = $this->makeSignature();
        $result = $ogSignature->isSignatureValid($request, credentials: new OrsStaging);

        $this->assertFalse($result);
    }

    public function test_createSignatureByObject_givenData_expected()
    {
        $requestData = '{
            "player_id": "8dxw86xw6u027",
            "timestamp": 1715051085,
            "total_amount": 250,
            "transaction_type": "debit",
            "game_id": 123,
            "round_id": "1e0e2s0vvt4usg",
            "currency": "IDR",
            "called_at": 1715051085,
            "records": [
                {
                    "transaction_id": "1e0e2s0vvt4usg_74",
                    "secondary_info": {},
                    "amount": 250,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "abb012400495e2a5f96116d4a3bf6553"
        }';

        $ogSignature = $this->makeSignature();
        $result = $ogSignature->createSignatureByObject(objectData: json_decode($requestData), credentials: new OrsStaging);

        $this->assertSame('abb012400495e2a5f96116d4a3bf6553', $result);
    }

    public function test_createSignatureByArray_givenData_expected()
    {
        $requestData = [
            "player_id" => "8dxw86xw6u027",
            "timestamp" => 1715071526,
            "total_amount" => 250,
            "transaction_type" => "debit",
            "game_id" => 123,
            "round_id" => "182xk5xvw5az7j",
            "currency" => "IDR",
            "called_at" => 1715071526,
            "records" => [
                [
                    "transaction_id" => "test_transacID_1",
                    "secondary_info" => [],
                    "amount" => 50,
                    "other_info" => [],
                    "remark" => [],
                    "bet_place" => "BASEGAME"
                ],
                [
                    "transaction_id" => "test_transacID_2",
                    "secondary_info" => [],
                    "amount" => 100,
                    "other_info" => [],
                    "remark" => [],
                    "bet_place" => "BASEGAME"
                ]
            ],
            'signature' => '3f04098590a5eff7a85ad807694aa7ee'
        ];

        $ogSignature = $this->makeSignature();
        $result = $ogSignature->createSignatureByArray(arrayData: $requestData, credentials: new OrsStaging);

        $this->assertSame('3f04098590a5eff7a85ad807694aa7ee', $result);
    }
}
