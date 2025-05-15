<?php

namespace App\GameProviders\V2\PLA;

use Illuminate\Http\Request;
use App\Libraries\Randomizer;
use App\GameProviders\V2\PCA\Contracts\IApi;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\GameProviders\V2\PCA\Contracts\IRepository;
use App\Exceptions\Casino\TransactionNotFoundException;
use App\GameProviders\V2\PCA\Contracts\ICredentialSetter;

class PlaCasinoService
{
    public function __construct(
        private IRepository $repository,
        private ICredentialSetter $credentials,
        private IApi $api,
        private Randomizer $randomizer
    ) {
    }

    public function getLaunchUrl(Request $request): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $request->playId);

        if (is_null($player) === true)
            $this->repository->createPlayer(
                playID: $request->playId,
                currency: $request->currency,
                username: $request->username
            );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $token = "{$credentials->getKioskName()}_{$this->randomizer->createToken()}";

        $this->repository->createOrUpdateToken(playID: $request->playId, token: $token);

        return $this->api->getGameLaunchUrl(credentials: $credentials, request: $request, token: $token);
    }

    public function getBetDetail(Request $request): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $transaction = $this->repository->getTransactionByRefID(refID: $request->bet_id);

        if (is_null($transaction) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        return $this->api->gameRoundStatus(credentials: $credentials, transactionID: $transaction->trx_id);
    }
}
