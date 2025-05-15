<?php

namespace App\Contracts\V2;

interface IWalletCredentials
{
    public function getGrpcHost(): string;
    public function getGrpcPort(): string;
    public function getGrpcToken(): string;
    public function getGrpcSignature(): string;
    public function getProviderCode(): string;
}
