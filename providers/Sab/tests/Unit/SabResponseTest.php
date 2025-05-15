<?php

use Carbon\Carbon;
use Tests\TestCase;
use Providers\Sab\SabResponse;
use Providers\Sab\Contracts\ISabSportsbookDetails;

class SabResponseTest extends TestCase
{
    private function makeResponse(): SabResponse
    {
        return new SabResponse();
    }

    public function test_casinoResponse_stubData_expectedData()
    {
        $data = 'testLaunchUrl.com&lang=en&OType=3';

        $response = $this->makeResponse();
        $result = $response->casinoResponse(data: $data);

        $this->assertSame(
            expected: [
                'success' => true,
                'code' => 200,
                'data' => $data,
                'error' => null
            ],
            actual: $result->getData(true)
        );
    }

    public function test_visualHtml_stubDataSportsbookDetails_expectedData()
    {
        $stubSportsbookDetails = $this->createMock(ISabSportsbookDetails::class);

        $stubSportsbookDetails->method('getTicketID')
            ->willReturn('testTicketID');
        $stubSportsbookDetails->method('getDateTimeSettle')
            ->willReturn('2025-01-01 12:00:00');
        $stubSportsbookDetails->method('getEvent')
            ->willReturn('Test Event');
        $stubSportsbookDetails->method('getMatch')
            ->willReturn('Team A vs Team B');
        $stubSportsbookDetails->method('getMarket')
            ->willReturn('Test Market');
        $stubSportsbookDetails->method('getBetChoice')
            ->willReturn('Team A');
        $stubSportsbookDetails->method('getHdp')
            ->willReturn('1.5');
        $stubSportsbookDetails->method('getOdds')
            ->willReturn(2.0);
        $stubSportsbookDetails->method('getOddsType')
            ->willReturn('1');
        $stubSportsbookDetails->method('getStake')
            ->willReturn(100.00);
        $stubSportsbookDetails->method('getScore')
            ->willReturn('1');
        $stubSportsbookDetails->method('getResult')
            ->willReturn('Win');

        $sportsbookDetails = [
            'ticketID' => $stubSportsbookDetails->getTicketID(),
            'dateTimeSettle' => $stubSportsbookDetails->getDateTimeSettle(),
            'event' => $stubSportsbookDetails->getEvent(),
            'match' => $stubSportsbookDetails->getMatch(),
            'betType' => $stubSportsbookDetails->getMarket(),
            'betChoice' => $stubSportsbookDetails->getBetChoice(),
            'hdp' => $stubSportsbookDetails->getHdp(),
            'odds' => $stubSportsbookDetails->getOdds(),
            'oddsType' => $stubSportsbookDetails->getOddsType(),
            'betAmount' => $stubSportsbookDetails->getStake(),
            'score' => $stubSportsbookDetails->getScore(),
            'status' => $stubSportsbookDetails->getResult(),
            'mixParlayData' => [],
            'singleParlayData' => [],
        ];

        $response = new SabResponse();

        $viewResult = $response->visualHtml(sportsbookDetails: $sportsbookDetails);

        $result = $viewResult->getData();

        $expectedData = [
            'ticketID' => 'testTicketID',
            'dateTimeSettle' => '2025-01-01 12:00:00',
            'event' => 'Test Event',
            'match' => 'Team A vs Team B',
            'betType' => 'Test Market',
            'betChoice' => 'Team A',
            'hdp' => '1.5',
            'odds' => '2.0',
            'oddsType' => '1',
            'betAmount' => 100.00,
            'score' => '1',
            'status' => 'Win',
            'mixParlayData' => [],
            'singleParlayData' => []
        ];

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    public function test_visualHtml_stubDataMixParlay_expectedData()
    {
        $stubSportsbookDetails = $this->createMock(ISabSportsbookDetails::class);

        $stubSportsbookDetails->method('getTicketID')
            ->willReturn('testTicketID');
        $stubSportsbookDetails->method('getDateTimeSettle')
            ->willReturn('2025-01-01 12:00:00');
        $stubSportsbookDetails->method('getEvent')
            ->willReturn('Test Event');
        $stubSportsbookDetails->method('getMatch')
            ->willReturn('Team A vs Team B');
        $stubSportsbookDetails->method('getMarket')
            ->willReturn('Test Market');
        $stubSportsbookDetails->method('getBetChoice')
            ->willReturn('Team A');
        $stubSportsbookDetails->method('getHdp')
            ->willReturn('1.5');
        $stubSportsbookDetails->method('getOdds')
            ->willReturn(2.0);
        $stubSportsbookDetails->method('getOddsType')
            ->willReturn('1');
        $stubSportsbookDetails->method('getStake')
            ->willReturn(100.00);
        $stubSportsbookDetails->method('getScore')
            ->willReturn('1');
        $stubSportsbookDetails->method('getResult')
            ->willReturn('Win');

        $createParlayMock = function ($event, $match, $market, $choice, $hdp, $odds, $score, $result) {
            $mock = $this->createMock(ISabSportsbookDetails::class);
            $mock->method('getEvent')->willReturn($event);
            $mock->method('getMatch')->willReturn($match);
            $mock->method('getMarket')->willReturn($market);
            $mock->method('getBetChoice')->willReturn($choice);
            $mock->method('getHdp')->willReturn($hdp);
            $mock->method('getOdds')->willReturn($odds);
            $mock->method('getScore')->willReturn($score);
            $mock->method('getResult')->willReturn($result);
            return $mock;
        };

        $parlayBets = [
            $createParlayMock('parlay-event-1', 'parlay-match-1', 'test1', '1', '2.0', 1.8, '0', 'Lose'),
            $createParlayMock('parlay-event-2', 'parlay-match-2', 'test2', '2', '1.5', 2.0, '1', 'Win'),
        ];

        $stubSportsbookDetails->method('getMixParlayBets')->willReturn($parlayBets);

        $sportsbookDetailsArray = [
            'ticketID' => $stubSportsbookDetails->getTicketID(),
            'dateTimeSettle' => $stubSportsbookDetails->getDateTimeSettle(),
            'event' => $stubSportsbookDetails->getEvent(),
            'match' => $stubSportsbookDetails->getMatch(),
            'betType' => $stubSportsbookDetails->getMarket(),
            'betChoice' => $stubSportsbookDetails->getBetChoice(),
            'hdp' => $stubSportsbookDetails->getHdp(),
            'odds' => $stubSportsbookDetails->getOdds(),
            'oddsType' => $stubSportsbookDetails->getOddsType(),
            'betAmount' => $stubSportsbookDetails->getStake(),
            'score' => $stubSportsbookDetails->getScore(),
            'status' => $stubSportsbookDetails->getResult(),
            'mixParlayData' => array_map(function ($parlay) {
                return (object) [
                    'event' => $parlay->getEvent(),
                    'match' => $parlay->getMatch(),
                    'betType' => $parlay->getMarket(),
                    'betChoice' => $parlay->getBetChoice(),
                    'hdp' => $parlay->getHdp(),
                    'odds' => $parlay->getOdds(),
                    'score' => $parlay->getScore(),
                    'status' => $parlay->getResult(),
                ];
            }, $parlayBets),
            'singleParlayData' => [],
        ];

        $response = new SabResponse();

        $viewResult = $response->visualHtml($sportsbookDetailsArray);

        $result = $viewResult->getData();

        $expectedData = [
            'ticketID' => 'testTicketID',
            'dateTimeSettle' => '2025-01-01 12:00:00',
            'event' => 'Test Event',
            'match' => 'Team A vs Team B',
            'betType' => 'Test Market',
            'betChoice' => 'Team A',
            'hdp' => '1.5',
            'odds' => 2.0,
            'oddsType' => '1',
            'betAmount' => 100.00,
            'score' => '1',
            'status' => 'Win',
            'mixParlayData' => [
                (object) [
                    'event' => 'parlay-event-1',
                    'match' => 'parlay-match-1',
                    'betType' => 'test1',
                    'betChoice' => '1',
                    'hdp' => '2.0',
                    'odds' => '1.8',
                    'score' => '0',
                    'status' => 'Lose',
                ],
                (object) [
                    'event' => 'parlay-event-2',
                    'match' => 'parlay-match-2',
                    'betType' => 'test2',
                    'betChoice' => '2',
                    'hdp' => '1.5',
                    'odds' => '2.0',
                    'score' => '1',
                    'status' => 'Win',
                ],
            ],
            'singleParlayData' => [],
        ];

        $this->assertEquals(expected: $expectedData, actual: $result);
    }


    public function test_visualHtml_stubDataSingleParlay_expectedData()
    {
        $stubSportsbookDetails = $this->createMock(ISabSportsbookDetails::class);

        $stubSportsbookDetails->method('getTicketID')
            ->willReturn('testTicketID');
        $stubSportsbookDetails->method('getDateTimeSettle')
            ->willReturn('2025-01-01 12:00:00');
        $stubSportsbookDetails->method('getEvent')
            ->willReturn('Test Event');
        $stubSportsbookDetails->method('getMatch')
            ->willReturn('Team A vs Team B');
        $stubSportsbookDetails->method('getMarket')
            ->willReturn('Test Market');
        $stubSportsbookDetails->method('getBetChoice')
            ->willReturn('Team A');
        $stubSportsbookDetails->method('getHdp')
            ->willReturn('1.5');
        $stubSportsbookDetails->method('getOdds')
            ->willReturn(2.0);
        $stubSportsbookDetails->method('getOddsType')
            ->willReturn('1');
        $stubSportsbookDetails->method('getStake')
            ->willReturn(100.00);
        $stubSportsbookDetails->method('getScore')
            ->willReturn('1');
        $stubSportsbookDetails->method('getResult')
            ->willReturn('Win');

        $singleParlayBet = (object) [
            'event' => 'single-event-1',
            'match' => 'single-match-1',
            'betType' => 'single-market-1',
            'betChoice' => 'a',
            'hdp' => '1.0',
            'odds' => 1.9,
            'score' => '1',
            'status' => 'Win',
        ];

        $stubSportsbookDetails->method('getSingleParlayBets')
            ->willReturn([$singleParlayBet]);
        $stubSportsbookDetails->method('getMixParlayBets')
            ->willReturn([]);

        $sportsbookDetails = [
            'ticketID' => $stubSportsbookDetails->getTicketID(),
            'dateTimeSettle' => $stubSportsbookDetails->getDateTimeSettle(),
            'event' => $stubSportsbookDetails->getEvent(),
            'match' => $stubSportsbookDetails->getMatch(),
            'betType' => $stubSportsbookDetails->getMarket(),
            'betChoice' => $stubSportsbookDetails->getBetChoice(),
            'hdp' => $stubSportsbookDetails->getHdp(),
            'odds' => $stubSportsbookDetails->getOdds(),
            'oddsType' => $stubSportsbookDetails->getOddsType(),
            'betAmount' => $stubSportsbookDetails->getStake(),
            'score' => $stubSportsbookDetails->getScore(),
            'status' => $stubSportsbookDetails->getResult(),
            'mixParlayData' => $stubSportsbookDetails->getMixParlayBets(),
            'singleParlayData' => $stubSportsbookDetails->getSingleParlayBets(),
        ];

        $response = new SabResponse();
        $viewResult = $response->visualHtml(sportsbookDetails: $sportsbookDetails);

        $result = $viewResult->getData();

        $expectedData = [
            'ticketID' => 'testTicketID',
            'dateTimeSettle' => '2025-01-01 12:00:00',
            'event' => 'Test Event',
            'match' => 'Team A vs Team B',
            'betType' => 'Test Market',
            'betChoice' => 'Team A',
            'hdp' => '1.5',
            'odds' => 2.0,
            'oddsType' => '1',
            'betAmount' => 100.00,
            'score' => '1',
            'status' => 'Win',
            'mixParlayData' => [],
            'singleParlayData' => [
                (object) [
                    'event' => 'single-event-1',
                    'match' => 'single-match-1',
                    'betType' => 'single-market-1',
                    'betChoice' => 'a',
                    'hdp' => '1.0',
                    'odds' => 1.9,
                    'score' => '1',
                    'status' => 'Win',
                ]
            ]
        ];

        $this->assertEquals(expected: $expectedData, actual: $result);
    }


    public function test_balance_stubData_expectedData()
    {
        $userID = 'testPlayID';
        $balance = 1;

        Carbon::setTestNow('2024-01-01T00:00:00-04:00');

        $response = $this->makeResponse();
        $result = $response->balance(userID: $userID, balance: $balance);

        $this->assertSame(
            expected: [
                'status' => 0,
                'userId' => $userID,
                'balance' => $balance,
                'balanceTs' => '2024-01-01T00:00:00-04:00',
            ],
            actual: $result->getData(true)
        );
    }

    public function test_placeBet_stubData_expectedData()
    {
        $refID = 'testTransactionID';

        $response = $this->makeResponse();
        $result = $response->placeBet(refID: $refID);

        $this->assertSame(
            expected: [
                'status' => 0,
                'msg' => null,
                'refId' => $refID,
                'licenseeTxId' => $refID
            ],
            actual: $result->getData(true)
        );
    }

    public function test_successWithBalance_expectedData()
    {
        $balance = 1;

        $response = $this->makeResponse();
        $result = $response->successWithBalance(balance: $balance);

        $this->assertSame(
            expected: [
                'status' => 0,
                'msg' => null,
                'balance' => $balance
            ],
            actual: $result->getData(true)
        );
    }

    public function test_placeBetParlay_stubData_expectedData()
    {
        $transactions = [
            [
                'refId' => 'testTransactionID-1'
            ],
            [
                'refId' => 'testTransactionID-2'
            ]
        ];

        $response = $this->makeResponse();
        $result = $response->placeBetParlay(transactions: $transactions);

        $this->assertSame(
            expected: [
                'status' => 0,
                'txns' => [
                    [
                        'refId' => 'testTransactionID-1',
                        'licenseeTxId' => 'testTransactionID-1'
                    ],
                    [
                        'refId' => 'testTransactionID-2',
                        'licenseeTxId' => 'testTransactionID-2'
                    ]
                ]
            ],
            actual: $result->getData(true)
        );
    }

    public function test_successWithoutBalance_stubData_expectedData()
    {
        $response = $this->makeResponse();
        $result = $response->successWithoutBalance();

        $this->assertSame(
            expected: [
                'status' => 0,
                'msg' => null
            ],
            actual: $result->getData(true)
        );
    }
}
