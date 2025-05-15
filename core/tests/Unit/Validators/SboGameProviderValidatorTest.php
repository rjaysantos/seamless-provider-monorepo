<?php

use Tests\TestCase;
use App\Models\SboPlayer;
use App\Models\SboReport;
use Illuminate\Http\Request;
use App\Validations\SboGameProviderValidator;
use App\Exceptions\Sbo\InvalidCompanyKeyException;
use App\Exceptions\General\PlayerNotFoundException;
use App\Exceptions\Sbo\TransactionAlreadyVoidException;
use App\Exceptions\General\TransactionNotFoundException;
use App\Exceptions\GameProvider\InsufficientFundException;
use App\Exceptions\Sbo\TransactionAlreadyRollbackException;
use App\Exceptions\Sbo\validateBetAmountHigherThanLastException;
use App\Exceptions\Sbo\validateRngGameNotSettledOrVoidException;
use App\Exceptions\GameProvider\TransactionAlreadyExistException;
use App\Exceptions\GameProvider\TransactionAlreadySettledException;

class SboGameProviderValidatorTest extends TestCase
{
    public function makeValidator()
    {
        return new SboGameProviderValidator;
    }

    public function test_validateCompanyKey_validCompanyKey_void()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB'
        ]);

        $validator = $this->makeValidator();
        $result = $validator->validateCompanyKey($request);

        $this->assertNull($result);
    }

    public function test_validateCompanyKey_invalidCompanyKey_InvalidCompanyKeyException()
    {
        $this->expectException(InvalidCompanyKeyException::class);

        $request = new Request([
            'CompanyKey' => 'invalid company key'
        ]);

        $validator = $this->makeValidator();
        $validator->validateCompanyKey($request);
    }

    public function test_validateSBOTransactionNotSettled_validFlag_void()
    {
        $transaction = new SboReport;
        $transaction->flag = 'running';

        $validator = $this->makeValidator();
        $result = $validator->validateSBOTransactionNotSettled($transaction);

        $this->assertNull($result);
    }

    public function test_validateSBOTransactionNotSettled_flagSettled_TransactionAlreadySettledException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $transaction = new SboReport;
        $transaction->flag = 'settled';

        $validator = $this->makeValidator();
        $result = $validator->validateSBOTransactionNotSettled($transaction);

        $this->assertNull($result);
    }

    public function test_validateSBOTransactionNotVoid_validFlag_void()
    {
        $transaction = new SboReport;
        $transaction->flag = 'running';

        $validator = $this->makeValidator();
        $result = $validator->validateSBOTransactionNotVoid($transaction);

        $this->assertNull($result);
    }

    public function test_validateSBOTransactionNotVoid_flagVoid_TransactionAlreadyVoidException()
    {
        $this->expectException(TransactionAlreadyVoidException::class);

        $transaction = new SboReport;
        $transaction->flag = 'void';

        $validator = $this->makeValidator();
        $result = $validator->validateSBOTransactionNotVoid($transaction);

        $this->assertNull($result);
    }

    public function test_validateSBOTransactionAlreadyRollback_validFlag_void()
    {
        $transaction = new SboReport;
        $transaction->flag = 'running';

        $validator = $this->makeValidator();
        $result = $validator->validateSBOTransactionAlreadyRollback($transaction);

        $this->assertNull($result);
    }

    public function test_validateSBOTransactionAlreadyRollback_flagRollback_TransactionAlreadyRollbackException()
    {
        $this->expectException(TransactionAlreadyRollbackException::class);

        $transaction = new SboReport;
        $transaction->flag = 'rollback';

        $validator = $this->makeValidator();
        $result = $validator->validateSBOTransactionAlreadyRollback($transaction);

        $this->assertNull($result);
    }

    public function test_validateHasPlayer_hasPlayer_null()
    {
        $player = new SboPlayer;

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

    public function test_validateHasTransaction_hasTransaction_null()
    {
        $transaction = new SboReport;

        $validator = $this->makeValidator();
        $result = $validator->validateHasTransaction($transaction);

        $this->assertNull($result);
    }

    public function test_validateHasTransaction_transactionNull_TransactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $player = null;

        $validator = $this->makeValidator();
        $validator->validateHasTransaction($player);
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

        $transaction = new SboReport;

        $validator = $this->makeValidator();
        $validator->validateNoExistingTransaction($transaction);
    }

    public function test_validateTransactionExists_hasTransaction_void()
    {
        $transaction = new SboReport;

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

    public function test_validateTransactionNotSettled_hasTransaction_void()
    {
        $transaction = new SboReport;

        $validator = $this->makeValidator();
        $result = $validator->validateTransactionNotSettled($transaction);

        $this->assertNull($result);
    }

    public function test_validateTransactionNotSettled_transactionNull_TransactionAlreadySettledException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $transaction = new SboReport;
        $transaction->updated_at = '2020-01-01 00:00:00';

        $validator = $this->makeValidator();
        $validator->validateTransactionNotSettled($transaction);
    }

    public function test_validateBetAmountHigherThanLast_firstBetSmaller_void()
    {
        $firstBet = 100.00;
        $newBet = 200.00;

        $validator = $this->makeValidator();
        $result = $validator->validateBetAmountHigherThanLast($firstBet, $newBet);

        $this->assertNull($result);
    }

    public function test_validateBetAmountHigherThanLast_firstBetBigger_validateBetAmountHigherThanLastException()
    {
        $this->expectException(validateBetAmountHigherThanLastException::class);

        $firstBet = 200.00;
        $newBet = 100.00;

        $validator = $this->makeValidator();
        $validator->validateBetAmountHigherThanLast($firstBet, $newBet);
    }

    public function test_validateRngGameNotSettledOrVoid_validFlag_void()
    {
        $transaction = new SboReport;
        $transaction->flag = 'running';

        $validator = $this->makeValidator();
        $result = $validator->validateRngGameNotSettledOrVoid($transaction);

        $this->assertNull($result);
    }

    public function test_validateRngGameNotSettledOrVoid_transactionSettled_validateRngGameNotSettledOrVoidException()
    {
        $this->expectException(validateRngGameNotSettledOrVoidException::class);

        $transaction = new SboReport;
        $transaction->flag = 'settled';

        $validator = $this->makeValidator();
        $validator->validateRngGameNotSettledOrVoid($transaction);
    }

    public function test_validateRngGameNotSettledOrVoid_transactionVoid_validateRngGameNotSettledOrVoidException()
    {
        $this->expectException(validateRngGameNotSettledOrVoidException::class);

        $transaction = new SboReport;
        $transaction->flag = 'void';

        $validator = $this->makeValidator();
        $validator->validateRngGameNotSettledOrVoid($transaction);
    }
}
