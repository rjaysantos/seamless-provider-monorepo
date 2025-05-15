<?php

namespace App\GameProviders\V2\PCA\Contracts;

use Illuminate\Http\Request;

interface IRepository
{
    public function getPlayerByPlayID(string $playID): ?object;
    public function getTransactionByRefID(string $refID): ?object;
    public function createPlayer(string $playID, string $currency, string $username): void;
    public function createOrUpdateToken(string $playID, string $token): void;
    public function getPlayGameByPlayIDToken(string $playID, string $token): ?object;
    public function deleteToken(string $playID, string $token): void;
    public function getTransactionByTransactionIDRefID(string $transactionID, string $refID): ?object;
    public function getBetTransactionByTransactionID(string $transactionID): ?object;
    public function getBetTransactionByTransactionIDRefID(string $transactionID, string $refID): ?object;
    public function getRefundTransactionByTransactionIDRefID(string $transactionID, string $refID): ?object;
    public function createBetTransaction(object $player, Request $request, string $betTime): void;
    public function createSettleTransaction(object $player, Request $request, string $settleTime): void;
    public function createLoseTransaction(object $player, Request $request): void;
    public function createRefundTransaction(object $player, Request $request, string $refundTime): void;
    public function updatePlayerStatusToJackpotBanned(string $playID): void;
}