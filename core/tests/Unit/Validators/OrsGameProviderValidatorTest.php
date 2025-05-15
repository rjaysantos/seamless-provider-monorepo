<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\OrsPlayer;
use App\Models\OrsReport;
use App\Models\OrsPlayGame;
use Illuminate\Http\Request;
use App\Services\Ors\OgSignature;
use App\Exceptions\Ors\InvalidTokenException;
use App\Validations\OrsGameProviderValidator;
use App\Exceptions\Ors\InvalidPublicKeyException;
use App\Exceptions\Ors\InvalidSignatureException;
use App\Exceptions\General\PlayerNotFoundException;
use App\Exceptions\General\TransactionNotFoundException;
use App\Exceptions\GameProvider\InsufficientFundException;
use App\Exceptions\GameProvider\TransactionAlreadyExistException;
use App\Exceptions\GameProvider\TransactionAlreadySettledException;

class OrsGameProviderValidatorTest extends TestCase
{
    public function makeValidator($signature = null)
    {
        $signature ??= $this->createStub(OgSignature::class);

        return new OrsGameProviderValidator($signature);
    }

    public function test_validateHasPlayer_hasPlayer_void()
    {
        $player = new OrsPlayer;

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

        $transaction = new OrsReport;

        $validator = $this->makeValidator();
        $validator->validateNoExistingTransaction($transaction);
    }

    public function test_validateTransactionExists_hasTransaction_void()
    {
        $transaction = new OrsReport;

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
        $transaction = new OrsReport;
        $transaction->updated_at = null;

        $validator = $this->makeValidator();
        $result = $validator->validateTransactionNotSettled($transaction);

        $this->assertNull($result);
    }

    public function test_validateTransactionNotSettled_hasTransaction_TransactionAlreadySettledException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $transaction = new OrsReport;
        $transaction->updated_at = Carbon::now()->format('Y-m-d H:i:s');

        $validator = $this->makeValidator();
        $validator->validateTransactionNotSettled($transaction);
    }

    public function test_validatePublicKey_validPublicKey_void()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x']);

        $validator = $this->makeValidator(null);
        $result = $validator->validatePublicKey($request);

        $this->assertNull($result);
    }

    public function test_validatePublicKey_invalidPublicKey_InvalidPublicKeyException()
    {
        $this->expectException(InvalidPublicKeyException::class);

        $request = new Request([], [], [], [], [], ['HTTP_Key' => 'invalid public key']);

        $validator = $this->makeValidator(null);
        $validator->validatePublicKey($request);
    }

    public function test_validateSignature_mockOgSignature_isSignatureValid()
    {
        $request = new Request();

        $mockSignature = $this->createMock(OgSignature::class);
        $mockSignature->expects($this->once())
            ->method('isSignatureValid')
            ->with($request)
            ->willReturn(true);

        $validator = $this->makeValidator($mockSignature);
        $validator->validateSignature($request);
    }

    public function test_validateSignature_stubSignatureReturnTrue_void()
    {
        $request = new Request();

        $stubSignature = $this->createStub(OgSignature::class);
        $stubSignature->method('isSignatureValid')
            ->willReturn(true);

        $validator = $this->makeValidator($stubSignature);
        $result = $validator->validateSignature($request);

        $this->assertNull($result);
    }

    public function test_validateSignature_stubSignatureReturnFalse_InvalidSignatureException()
    {
        $this->expectException(InvalidSignatureException::class);

        $request = new Request();

        $stubSignature = $this->createStub(OgSignature::class);
        $stubSignature->method('isSignatureValid')
            ->willReturn(false);

        $validator = $this->makeValidator($stubSignature);
        $validator->validateSignature($request);
    }

    public function test_validateToken_playGameTokenIsSameWithToken_void()
    {
        $playerGame = new OrsPlayGame;
        $playerGame->token = 'test_token';

        $token = 'test_token';

        $validator = $this->makeValidator();
        $result = $validator->validateToken($playerGame, $token);

        $this->assertNull($result);
    }

    public function test_validateToken_stubSignatureReturnFalse_InvalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $playerGame = new OrsPlayGame;
        $playerGame->token = 'test_token';

        $token = 'invalid_token';

        $validator = $this->makeValidator();
        $validator->validateToken($playerGame, $token);
    }
}
