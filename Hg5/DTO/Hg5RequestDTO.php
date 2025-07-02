<?php

namespace Providers\Hg5\DTO;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class Hg5RequestDTO
{
    public function __construct(
        public readonly ?string $authToken = null,
        public readonly ?string $playID = null,
        public readonly ?int $agentID = null,
        public readonly ?string $token = null,
        public readonly ?string $roundID = null,
        public readonly ?string $currency = null,
    ) {}

    public static function fromAuthenticateRequest(Request $request): self
    {
        return new self(
            authToken: $request->header('Authorization'),
            playID: $request->playerId,
            agentID: $request->agentId,
            token: $request->launchToken
        );
    }

    public static function fromVisualHTMLRequest(string $playID, string $trxID): self
    {
        return new self(
            playID: Crypt::decryptString($playID),
            roundID: Crypt::decryptString($trxID)
        );
    }

    public static function fromVisualFishGameRequest(Request $request): self
    {
        return new self(
            playID: $request->playID,
            roundID: $request->trxID
        );
    }
}
