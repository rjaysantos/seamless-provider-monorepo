<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\RedPlayer;
use App\Models\RedReport;
use Illuminate\Http\Request;
use App\Validations\RedGameProviderValidator;
use App\Exceptions\Red\DuplicateBonusException;
use App\Exceptions\Red\InvalidSecretKeyException;
use App\Exceptions\General\PlayerNotFoundException;
use App\Exceptions\General\TransactionNotFoundException;
use App\Exceptions\GameProvider\InsufficientFundException;
use App\Exceptions\GameProvider\TransactionAlreadyExistException;
use App\Exceptions\GameProvider\TransactionAlreadySettledException;

class RedGameProviderValidatorTest extends TestCase
{
    public function makeValidator()
    {
        return new RedGameProviderValidator();
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

    public function test_validateSecretKey_validSecretKey_void()
    {
        $request = new Request([], [], [], [], [], ['HTTP_SECRET_KEY' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad']);

        $validator = $this->makeValidator();
        $result = $validator->validateSecretKey($request);

        $this->assertNull($result);
    }

    public function test_validateSecretKey_secretKeyNotSame_InvalidSecretKeyException()
    {
        $this->expectException(InvalidSecretKeyException::class);

        $request = new Request([], [], [], [], [], ['HTTP_SECRET_KEY' => 'invalid_secret_key']);

        $validator = $this->makeValidator();
        $validator->validateSecretKey($request);
    }

    public function test_validateNoExistingBonus_givenNull_void()
    {
        $transaction = null;

        $validator = $this->makeValidator();
        $result = $validator->validateNoExistingBonus($transaction);

        $this->assertNull($result);
    }

    public function test_validateNoExistingBonus_givenRedReport_DuplicateBonusException()
    {
        $this->expectException(DuplicateBonusException::class);

        $transaction = new RedReport;

        $validator = $this->makeValidator();
        $validator->validateNoExistingBonus($transaction);
    }
}
