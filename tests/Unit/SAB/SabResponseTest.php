<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\GameProviders\V2\Sab\SabResponse;
use App\GameProviders\V2\Sab\Contracts\ISportsbookDetails;

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
        $stubSportsbookDetails = $this->createMock(ISportsbookDetails::class);

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
            ->willReturn('2.0');
        $stubSportsbookDetails->method('getOddsType')
            ->willReturn('1');
        $stubSportsbookDetails->method('getStake')
            ->willReturn(100.00);
        $stubSportsbookDetails->method('getScore')
            ->willReturn('1');
        $stubSportsbookDetails->method('getResult')
            ->willReturn('Win');

        $response = new SabResponse();

        $viewResult = $response->visualHtml(sportsbookDetails: $stubSportsbookDetails);

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
        $stubSportsbookDetails = $this->createMock(ISportsbookDetails::class);

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
            ->willReturn('2.0');
        $stubSportsbookDetails->method('getOddsType')
            ->willReturn('1');
        $stubSportsbookDetails->method('getStake')
            ->willReturn(100.00);
        $stubSportsbookDetails->method('getScore')
            ->willReturn('1');
        $stubSportsbookDetails->method('getResult')
            ->willReturn('Win');

        $stubSportsbookDetails->method('getMixParlayBets')->willReturn([
            $this->createMock(ISportsbookDetails::class),
            $this->createMock(ISportsbookDetails::class),
        ]);

        // 1st parlay
        $stubSportsbookDetails->getMixParlayBets()[0]
            ->method('getEvent')
            ->willReturn('parlay-event-1');
        $stubSportsbookDetails->getMixParlayBets()[0]
            ->method('getMatch')
            ->willReturn('parlay-match-1');
        $stubSportsbookDetails->getMixParlayBets()[0]
            ->method('getMarket')
            ->willReturn('test1');
        $stubSportsbookDetails->getMixParlayBets()[0]
            ->method('getBetChoice')
            ->willReturn('1');
        $stubSportsbookDetails->getMixParlayBets()[0]
            ->method('getHdp')
            ->willReturn('2.0');
        $stubSportsbookDetails->getMixParlayBets()[0]
            ->method('getOdds')
            ->willReturn('1.8');
        $stubSportsbookDetails->getMixParlayBets()[0]
            ->method('getScore')
            ->willReturn('0');
        $stubSportsbookDetails->getMixParlayBets()[0]
            ->method('getResult')
            ->willReturn('Lose');

        // 2nd parlay
        $stubSportsbookDetails->getMixParlayBets()[1]
            ->method('getEvent')
            ->willReturn('parlay-event-2');
        $stubSportsbookDetails->getMixParlayBets()[1]
            ->method('getMatch')
            ->willReturn('parlay-match-2');
        $stubSportsbookDetails->getMixParlayBets()[1]
            ->method('getMarket')
            ->willReturn('test2');
        $stubSportsbookDetails->getMixParlayBets()[1]
            ->method('getBetChoice')
            ->willReturn('2');
        $stubSportsbookDetails->getMixParlayBets()[1]
            ->method('getHdp')
            ->willReturn('1.5');
        $stubSportsbookDetails->getMixParlayBets()[1]
            ->method('getOdds')
            ->willReturn('2.0');
        $stubSportsbookDetails->getMixParlayBets()[1]
            ->method('getScore')
            ->willReturn('1');
        $stubSportsbookDetails->getMixParlayBets()[1]
            ->method('getResult')
            ->willReturn('Win');

        $response = new SabResponse();
        $viewResult = $response->visualHtml(sportsbookDetails: $stubSportsbookDetails);

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
            'mixParlayData' => [
                (object)[
                    'event' => 'parlay-event-1',
                    'match' => 'parlay-match-1',
                    'betType' => 'test1',
                    'betChoice' => '1',
                    'hdp' => '2.0',
                    'odds' => '1.8',
                    'score' => '0',
                    'status' => 'Lose',
                ],
                (object)[
                    'event' => 'parlay-event-2',
                    'match' => 'parlay-match-2',
                    'betType' => 'test2',
                    'betChoice' => '2',
                    'hdp' => '1.5',
                    'odds' => '2.0',
                    'score' => '1',
                    'status' => 'Win',
                ]
            ],
            'singleParlayData' => []
        ];

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    public function test_visualHtml_stubDataSingleParlay_expectedData()
    {
        $stubSportsbookDetails = $this->createMock(ISportsbookDetails::class);

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
            ->willReturn('2.0');
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
            'odds' => '1.9',
            'score' => '1',
            'status' => 'Win',
        ];

        $stubSportsbookDetails->method('getSingleParlayBets')
            ->willReturn([$singleParlayBet]);
        $stubSportsbookDetails->method('getMixParlayBets')
            ->willReturn([]);

        $response = new SabResponse();
        $viewResult = $response->visualHtml(sportsbookDetails: $stubSportsbookDetails);

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
            'singleParlayData' => [
                (object)[
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

    public function test_confirmBet_expectedData()
    {
        $balance = 1;

        $response = $this->makeResponse();
        $result = $response->confirmBet(balance: $balance);

        $this->assertSame(
            expected: [
                'status' => 0,
                'msg' => null,
                'balance' => $balance
            ],
            actual: $result->getData(true)
        );
    }
}
