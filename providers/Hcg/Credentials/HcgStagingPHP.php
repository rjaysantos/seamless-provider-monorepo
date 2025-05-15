<?php

namespace Providers\Hcg\Credentials;

use Providers\Hcg\Contracts\ICredentials;

class HcgStagingPHP implements ICredentials
{
    public function getGrpcHost(): string
    {
        return 'mcs-wallet-stg-a465ab3678a45b68.elb.ap-southeast-1.amazonaws.com';
    }

    public function getGrpcPort(): string
    {
        return '3939';
    }

    public function getGrpcToken(): string
    {
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJIQ0ciLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjMxODg5NDcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiYWYzNmI2ZjUyODA1OTljYWY2ZDcyYmI3ZDIyZDEzNjciLCJzdWIiOiJBdWRTeXMifQ.S_EYiUCNp7hKOiqqpjppV_Kvzk2wUkRF7XBGMEAcP2nDQMBbpSqXVZk0iUuX-XGGfG-4C6axwtNd1HIPdJEPmtpG1CSy1nI-b6Nv2P6Kkw1czxb_7RATPkFuZ8oCv_le5kGerdj9bT2NJ49mSjW0zCH0MT2yocKb6PRqHuHfmV67weCEVqSADam3BYW2fXSYicox8b3mNGqkiIoad5wq-vGw3hto5nXydHeqJj5DINB9h5DpXosxSDn_FmYOypyalgWXR_RVzcHfU6jW-kmLo6DxpTlPt7hwXmBWKboQOS-ord0dNDuE4ZkTC_hmZpZud24FaQ0ge2Mlcq9dhMsYBQ';
    }

    public function getGrpcSignature(): string
    {
        return '0ee589b24f6ccbdb9d790ae8965de102';
    }

    public function getProviderCode(): string
    {
        return 'HCG';
    }

    public function getApiUrl(): string
    {
        return 'https://api.hcgame888.com/hcRequest';
    }
    public function getSignKey(): string
    {
        return '357d4cf555d6b4a18dd1617487bf6bad';
    }

    public function getWalletApiSignKey(): string
    {
        return '1|8MojGMjQ878CFY4mBBgFNXDq7yP6GJf6XBYwfGxHa304467b';
    }

    public function getEncryptionKey(): string
    {
        return 'ebfc8cc9e3b4111142049be708c3b07c';
    }

    public function getAppID(): string
    {
        return 't5QU1tqPnHXY5tkxJZ';
    }

    public function getAppSecret(): string
    {
        return 'jV1KEmSCdPMZu3VEWvC1KO1lvsWjV1vi';
    }

    public function getAgentID(): string
    {
        return '1578';
    }

    public function getVisualUrl(): string
    {
        return 'https://order.hcgame888.com';
    }

    public function getCurrencyConversion(): int
    {
        return 1;
    }

    public function getTransactionIDPrefix(): string
    {
        return '0';
    }
}
