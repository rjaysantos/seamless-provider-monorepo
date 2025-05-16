<?php

namespace Providers\Jdb\Credentials;

use Providers\Jdb\Contracts\ICredentials;

class JdbStaging implements ICredentials
{
    public function getGrpcHost(): string
    {
        return '12.0.129.253';
    }

    public function getGrpcPort(): string
    {
        return '3939';
    }

    public function getGrpcToken(): string
    {
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJKREIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjMxODg5MTAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMjU5NDI5MTEwMjlkYzA5NzE5YWNmODY5MjFkNGMxMTkiLCJzdWIiOiJBdWRTeXMifQ.MgThQTD-JEbB4Afz4RbMNFV7_1hkZONIFqPjrdAutvFQe5Wh2YWQwa-CJy70sExGe1Dc7fv7EnbVOOdekC24maiFoartAGi28QRiwkgpMV9jeWWlzN7cv9nG-0-EgfzpVBB1umUb72h8IseMkgTa8y_MhoUXn2vl3wr-27Nan5hVNtmGfzvDD-GHk9c30_ZeaTIjWEgdIpbAgRMsCD1XPeNs52z0BvSGvdV8mf5jtD-KRR8VqI_-FM0Cgcyo3B5osfB7hp0VTgz6dMImRLpS93mb4tGVMT0vijIxpCDjtA53sou_wydZcywJC-YjXldQ5laXyuuS7F2yUc4_aKnP6g';
    }

    public function getGrpcSignature(): string
    {
        return '7e7c1335a5ada2d9e1d2174051b4def3';
    }

    public function getProviderCode(): string
    {
        return 'JDB';
    }

    public function getKey(): string
    {
        return 'b5db72c939382d31';
    }

    public function getIV(): string
    {
        return '4f80c39467b21481';
    }

    public function getDC(): string
    {
        return 'COLS';
    }

    public function getApiUrl(): string
    {
        return 'http://api.jdb711.com';
    }

    public function getParent(): string
    {
        return 'testrpoagent01';
    }
}
