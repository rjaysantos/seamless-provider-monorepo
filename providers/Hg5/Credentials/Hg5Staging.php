<?php

namespace Providers\Hg5\Credentials;

use Providers\Hg5\Contracts\ICredentials;

class Hg5Staging implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJIRzUiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3NDE4Mzc0ODgsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNThjMTIwNDU5NGM3ZGNlOGYyMmU2OWJmNTljZWEwYTIiLCJzdWIiOiJBdWRTeXMifQ.sdNwmY6ouzWXSMNpfejB2FEFd-G2JqvgZ6izjNDtqMF_ffKmsW1spqLH3qzHShWJNrjRnnGakfD8V6c9gP7Js1Fb8ZX1jQFIBqM25dnxHrV59psbuqxrTNOHP-rIvHH09dbGCRRzISRh8NgzpU93UkTbw9a0JO5qzDx2RfBzNTDKASPfFGlW6hmpk9ZT0nfLUzC3Nj2e5GmVi9kkPTmjPSpnk88Gw_B8ibGbzhn9hXB-MOx9xBGJNKvEfwD2vesFQrRqiK4BWKUiQ43VFbtrCgYQwG5Lw1sDPgTNskyKr7WH0xSxnLPPpmCjDVVAaoBa2vQIuKY_wZ4fF_rr2yWvew';
    }

    public function getGrpcSignature(): string
    {
        return 'cd6a3bb9bff7f406f42a4936f48c8b60';
    }

    public function getProviderCode(): string
    {
        return 'HG5';
    }

    public function getApiUrl(): string
    {
        return 'https://wallet-csw-test.hg5games.com:5500';
    }

    public function getAgentID(): int
    {
        return 111;
    }

    public function getAuthorizationToken(): string
    {
        return 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk';
    }
}
