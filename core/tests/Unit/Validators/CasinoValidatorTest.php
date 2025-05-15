<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\RedPlayer;
use App\Models\RedReport;
use Illuminate\Http\Request;
use App\Validations\CasinoValidator;
use App\Exceptions\General\PlayerNotFoundException;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\General\TransactionNotFoundException;
use App\Exceptions\GameProvider\InsufficientFundException;
use App\Exceptions\GameProvider\TransactionAlreadyExistException;
use App\Exceptions\GameProvider\TransactionAlreadySettledException;

class CasinoValidatorTest extends TestCase
{
    public function makeValidator()
    {
        return new CasinoValidator;
    }

    public function test_validateHasPlayer_hasPlayer_void()
    {
        $player = new RedPlayer;

        $validator = $this->makeValidator();
        $result = $validator->validateHasPlayer($player);

        $this->assertNull($result);
    }

    public function test_validateHasPlayer_playerNull_PlayerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $player = null;

        $validator = $this->makeValidator();
        $validator->validateHasPlayer($player);
    }

    public function test_validateHasEnoughBalance_balanceIsGreaterThanOrEqualWithAmount_void()
    {
        $balance = 100.0;
        $amount = 100.0;

        $validator = $this->makeValidator();
        $result = $validator->validateHasEnoughBalance($balance, $amount);

        $this->assertNull($result);
    }

    public function test_validateHasEnoughBalance_balanceIsLessThanAmount_InsufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $balance = 10.0;
        $amount = 100.0;

        $validator = $this->makeValidator();
        $validator->validateHasEnoughBalance($balance, $amount);
    }

    public function test_validateNoExistingTransaction_transactionNull_void()
    {
        $transaction = null;

        $validator = $this->makeValidator();
        $result = $validator->validateNoExistingTransaction($transaction);

        $this->assertNull($result);
    }

    public function test_validateNoExistingTransaction_hasTransaction_TransactionAlreadyExistException()
    {
        $this->expectException(TransactionAlreadyExistException::class);

        $transaction = new RedReport;

        $validator = $this->makeValidator();
        $validator->validateNoExistingTransaction($transaction);
    }

    public function test_validateTransactionExists_hasTransaction_void()
    {
        $transaction = new RedReport;

        $validator = $this->makeValidator();
        $result = $validator->validateTransactionExists($transaction);

        $this->assertNull($result);
    }

    public function test_validateTransactionExists_transactionNull_TransactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $transaction = null;

        $validator = $this->makeValidator();
        $validator->validateTransactionExists($transaction);
    }

    public function test_validateTransactionNotSettled_transactionUpdatedAtIsNull_void()
    {
        $transaction = new RedReport;
        $transaction->updated_at = null;

        $validator = $this->makeValidator();
        $result = $validator->validateTransactionNotSettled($transaction);

        $this->assertNull($result);
    }

    public function test_validateTransactionNotSettled_hasTransaction_TransactionAlreadySettledException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $transaction = new RedReport;
        $transaction->updated_at = Carbon::now()->format('Y-m-d H:i:s');

        $validator = $this->makeValidator();
        $validator->validateTransactionNotSettled($transaction);
    }

    public function test_validateBearerToken_validBearerToken_void()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]);

        $validator = $this->makeValidator();
        $result = $validator->validateBearerToken($request);

        $this->assertNull($result);
    }

    public function test_validateBearerToken_ivalidBearerToken_InvalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([], [], [], [], [], ['HTTP_Authorization' => 'Bearer invalid_bearer_token']);

        $validator = $this->makeValidator();
        $validator->validateBearerToken($request);
    }
}
