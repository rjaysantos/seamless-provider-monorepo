<?php

namespace Providers\Pca\Credentials;

use Providers\Pca\Contracts\ICredentials;

class PcaUSD implements ICredentials
{
    public function getGrpcHost(): string
    {
        return '10.8.134.48';
    }

    public function getGrpcPort(): string
    {
        return '3939';
    }

    public function getGrpcToken(): string
    {
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE1MTEsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGE3MzVhOGYzMGU3OWU5YTNjMWMzZTJkMWEwN2JmOTYiLCJzdWIiOiJBdWRTeXMifQ.DETHEk1hDzkf_VpMg-Eh-vtQ-gKCnqgXOg_QXOfflWW7xZ7wKQhG_g6fvHg1F-8kzIe5r72Zpuy4zaTrgx1SjPyziLGvx0VhRNWiWD1bHkMLY8U54QeiMQEh-yG60hS5sCghbCRmYqfpKHUravuuFMPhuGWw0zP0u27JfJ5SE2htmy8YipZlXWfB9TjMuu50kBWb_egyS5V1Z-soV8FIqGzXUODXuT9Qj91uK3MDvrgdz79Iq4T1VECQc064_2alomRG9UNJEAXzBwbocSO1dhJ3cEw6c3EiLbUowj4EqMpSGG1gJgiJmUCWW15KJF5zB002IsQSsGb40G5blR1m_w';
    }

    public function getGrpcSignature(): string
    {
        return '68a41af718ea0bf29dfe22807786623b';
    }

    public function getProviderCode(): string
    {
        return 'PCA';
    }

    public function getApiUrl(): string
    {
        return 'https://api.agmidway.com';
    }

    public function getKioskKey(): string
    {
        return 'bb052bd9a863bcefe0b5571e4e95788e8dc643b51331d5525624011c260b614c';
    }

    public function getKioskName(): string
    {
        return 'PCAUS';
    }

    public function getServerName(): string
    {
        return 'AGCASINO';
    }

    public function getAdminKey(): string
    {
        return '69223691b94cacefca8bd2ac2a0f2d519fe78d326a52da29d6928d7d7aa325f9';
    }
}