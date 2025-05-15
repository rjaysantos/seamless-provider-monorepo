<?php

use Tests\TestCase;
use App\GameProviders\V2\Jdb\JdbCredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\GameProviders\V2\Jdb\Contracts\ICredentials;

class JdbCredentialsTest extends TestCase
{
    private function makeCredentialSetter(): JdbCredentials
    {
        return new JdbCredentials();
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_JdbIDR_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJKREIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjQwNTM2MDMsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMWRlMWFhZDNkOGRlMjVkYjFiODU5ZDU5NjcyOGE0YTciLCJzdWIiOiJBdWRTeXMifQ.EaJhqSiocxaAqbdujg-pFHW4oc3Pkv0f2vTrnI3X3i2SvPw6c9yMwJ4gwcrozRaz-sRB0crrnSBnqJuay5_BDoXarqU9ZAD2zQotdAOTgjZv-5b9BYFe69vMVqUSazSSRg5HHpgCvuhCC4HArzTZ7f86rqIv3tZZKJMlYHrvNQ8BSli8SiRB2xmxq5VKhkjo4QMPhfPE9HcCzq2pxnkImxwfgKEzPutEvDSIBS0sRD6wnDmKuE5IjS5H-tUQnwvV1PXme7duc9ABEwq1nZ-JtbccIvmpGEFdcv0fkrXmFOl6J5JLMzs1BzxD_X-vHPhxk_6nxkgZrqZ1x820egdy1A',
            'grpcSignature' => '8700ccb74ca3dd6a07c57e200e805010',
            'providerCode' => 'JDB',
            'key' => 'aeeb06d4f766d0ed',
            'IV' => 'c8073ae48c2a5acc',
            'DC' => 'COLS',
            'apiUrl' => 'http://api.jdb1688.net',
            'parent' => 'colsrpoagaix'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'IDR');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_JdbBRL_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJKREIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjQwNTM2MDMsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMWRlMWFhZDNkOGRlMjVkYjFiODU5ZDU5NjcyOGE0YTciLCJzdWIiOiJBdWRTeXMifQ.EaJhqSiocxaAqbdujg-pFHW4oc3Pkv0f2vTrnI3X3i2SvPw6c9yMwJ4gwcrozRaz-sRB0crrnSBnqJuay5_BDoXarqU9ZAD2zQotdAOTgjZv-5b9BYFe69vMVqUSazSSRg5HHpgCvuhCC4HArzTZ7f86rqIv3tZZKJMlYHrvNQ8BSli8SiRB2xmxq5VKhkjo4QMPhfPE9HcCzq2pxnkImxwfgKEzPutEvDSIBS0sRD6wnDmKuE5IjS5H-tUQnwvV1PXme7duc9ABEwq1nZ-JtbccIvmpGEFdcv0fkrXmFOl6J5JLMzs1BzxD_X-vHPhxk_6nxkgZrqZ1x820egdy1A',
            'grpcSignature' => '8700ccb74ca3dd6a07c57e200e805010',
            'providerCode' => 'JDB',
            'key' => 'aeeb06d4f766d0ed',
            'IV' => 'c8073ae48c2a5acc',
            'DC' => 'COLS',
            'apiUrl' => 'http://api.jdb1688.net',
            'parent' => 'colsbrlagaix'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'BRL');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_JdbPHP_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJKREIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjQwNTM2MDMsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMWRlMWFhZDNkOGRlMjVkYjFiODU5ZDU5NjcyOGE0YTciLCJzdWIiOiJBdWRTeXMifQ.EaJhqSiocxaAqbdujg-pFHW4oc3Pkv0f2vTrnI3X3i2SvPw6c9yMwJ4gwcrozRaz-sRB0crrnSBnqJuay5_BDoXarqU9ZAD2zQotdAOTgjZv-5b9BYFe69vMVqUSazSSRg5HHpgCvuhCC4HArzTZ7f86rqIv3tZZKJMlYHrvNQ8BSli8SiRB2xmxq5VKhkjo4QMPhfPE9HcCzq2pxnkImxwfgKEzPutEvDSIBS0sRD6wnDmKuE5IjS5H-tUQnwvV1PXme7duc9ABEwq1nZ-JtbccIvmpGEFdcv0fkrXmFOl6J5JLMzs1BzxD_X-vHPhxk_6nxkgZrqZ1x820egdy1A',
            'grpcSignature' => '8700ccb74ca3dd6a07c57e200e805010',
            'providerCode' => 'JDB',
            'key' => 'aeeb06d4f766d0ed',
            'IV' => 'c8073ae48c2a5acc',
            'DC' => 'COLS',
            'apiUrl' => 'http://api.jdb1688.net',
            'parent' => 'colsphpagaix'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'PHP');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_JdbTHB_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJKREIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjQwNTM2MDMsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMWRlMWFhZDNkOGRlMjVkYjFiODU5ZDU5NjcyOGE0YTciLCJzdWIiOiJBdWRTeXMifQ.EaJhqSiocxaAqbdujg-pFHW4oc3Pkv0f2vTrnI3X3i2SvPw6c9yMwJ4gwcrozRaz-sRB0crrnSBnqJuay5_BDoXarqU9ZAD2zQotdAOTgjZv-5b9BYFe69vMVqUSazSSRg5HHpgCvuhCC4HArzTZ7f86rqIv3tZZKJMlYHrvNQ8BSli8SiRB2xmxq5VKhkjo4QMPhfPE9HcCzq2pxnkImxwfgKEzPutEvDSIBS0sRD6wnDmKuE5IjS5H-tUQnwvV1PXme7duc9ABEwq1nZ-JtbccIvmpGEFdcv0fkrXmFOl6J5JLMzs1BzxD_X-vHPhxk_6nxkgZrqZ1x820egdy1A',
            'grpcSignature' => '8700ccb74ca3dd6a07c57e200e805010',
            'providerCode' => 'JDB',
            'key' => 'aeeb06d4f766d0ed',
            'IV' => 'c8073ae48c2a5acc',
            'DC' => 'COLS',
            'apiUrl' => 'http://api.jdb1688.net',
            'parent' => 'colsthbagaix'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'THB');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_JdbUSD_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJKREIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjQwNTM2MDMsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMWRlMWFhZDNkOGRlMjVkYjFiODU5ZDU5NjcyOGE0YTciLCJzdWIiOiJBdWRTeXMifQ.EaJhqSiocxaAqbdujg-pFHW4oc3Pkv0f2vTrnI3X3i2SvPw6c9yMwJ4gwcrozRaz-sRB0crrnSBnqJuay5_BDoXarqU9ZAD2zQotdAOTgjZv-5b9BYFe69vMVqUSazSSRg5HHpgCvuhCC4HArzTZ7f86rqIv3tZZKJMlYHrvNQ8BSli8SiRB2xmxq5VKhkjo4QMPhfPE9HcCzq2pxnkImxwfgKEzPutEvDSIBS0sRD6wnDmKuE5IjS5H-tUQnwvV1PXme7duc9ABEwq1nZ-JtbccIvmpGEFdcv0fkrXmFOl6J5JLMzs1BzxD_X-vHPhxk_6nxkgZrqZ1x820egdy1A',
            'grpcSignature' => '8700ccb74ca3dd6a07c57e200e805010',
            'providerCode' => 'JDB',
            'key' => 'aeeb06d4f766d0ed',
            'IV' => 'c8073ae48c2a5acc',
            'DC' => 'COLS',
            'apiUrl' => 'http://api.jdb1688.net',
            'parent' => 'colsusdagaix'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'USD');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_JdbVND_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJKREIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjQwNTM2MDMsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMWRlMWFhZDNkOGRlMjVkYjFiODU5ZDU5NjcyOGE0YTciLCJzdWIiOiJBdWRTeXMifQ.EaJhqSiocxaAqbdujg-pFHW4oc3Pkv0f2vTrnI3X3i2SvPw6c9yMwJ4gwcrozRaz-sRB0crrnSBnqJuay5_BDoXarqU9ZAD2zQotdAOTgjZv-5b9BYFe69vMVqUSazSSRg5HHpgCvuhCC4HArzTZ7f86rqIv3tZZKJMlYHrvNQ8BSli8SiRB2xmxq5VKhkjo4QMPhfPE9HcCzq2pxnkImxwfgKEzPutEvDSIBS0sRD6wnDmKuE5IjS5H-tUQnwvV1PXme7duc9ABEwq1nZ-JtbccIvmpGEFdcv0fkrXmFOl6J5JLMzs1BzxD_X-vHPhxk_6nxkgZrqZ1x820egdy1A',
            'grpcSignature' => '8700ccb74ca3dd6a07c57e200e805010',
            'providerCode' => 'JDB',
            'key' => 'aeeb06d4f766d0ed',
            'IV' => 'c8073ae48c2a5acc',
            'DC' => 'COLS',
            'apiUrl' => 'http://api.jdb1688.net',
            'parent' => 'colsvnoagaix'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'VND');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    public static function credentialParameters()
    {
        return [
            ['grpcHost'],
            ['grpcPort'],
            ['grpcToken'],
            ['grpcSignature'],
            ['providerCode'],
            ['key'],
            ['IV'],
            ['DC'],
            ['apiUrl'],
            ['parent']
        ];
    }

    public function getCredentialValue(
        ICredentials $credentials,
        string $field
    ): string {
        switch ($field) {
            case 'grpcHost':
                return $credentials->getGrpcHost();
            case 'grpcPort':
                return $credentials->getGrpcPort();
            case 'grpcToken':
                return $credentials->getGrpcToken();
            case 'grpcSignature':
                return $credentials->getGrpcSignature();
            case 'providerCode':
                return $credentials->getProviderCode();
            case 'key':
                return $credentials->getKey();
            case 'IV':
                return $credentials->getIV();
            case 'DC':
                return $credentials->getDC();
            case 'apiUrl':
                return $credentials->getApiUrl();
            case 'parent':
                return $credentials->getParent();
            default:
                return null;
        }
    }
}