<?php

use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\GameProviders\V2\ORS\OrsCredentials;

class OrsCredentialsTest extends TestCase
{
    private function makeCredentialSetter(): OrsCredentials
    {
        return new OrsCredentials();
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_OrsIDR_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJPUlMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTc0MDIwODUsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGY3ZDM1ZWFlNTQxY2I1MTNhYmM0NTBjM2JlNGQyNWUiLCJzdWIiOiJBdWRTeXMifQ.Q8-7dofAubMcSJOgl8hMTHcQ0re62A_d0bK-nmKlXuv1MXsU55JfjAZKgXasB_AtN6lXboDAwKBd533ex8Y6dYnMjSC0XaP1SUlJS-038fJ0tT7px46lyQ6A6NStCWSJvAADMMyQ8PR09WC1Yc-0mEU8ZzERlJGzTRg80b0DDG7P8vHJkBCLVDYYYCcoAh9EDEWS3NAU1kCPZ3ebyG7bhoxVvbwPizeLIi67B-U84bT84lqqexhtz7aF4FdrMCylxjCm3gb1KUaV1e9hTj_QA0w5pyl3XRl8epeoDnRhXw8zQ-EO8ZpocYB456xE6HivV5mpP6PMMl7ifIcunqgDLQ',
            'grpcSignature' => '2901dc61c18ebc7c0db3c9bab643a281',
            'providerCode' => 'ORS',
            'apiUrl' => 'https://apollo2.all5555.com',
            'operatorName' => 'mog052slotidr',
            'publicKey' => 'vPJmbdRMpvNeJ26RC4khwvQ7hBAgwxYJ',
            'privateKey' => 'Yth70vEMu31m0waHNZsZaiqGkcZ2bHW7',
            'arcadeGameList' => json_decode(env('ORS_ARCADE_GAMES'), true)
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('IDR');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_OrsBRL_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJPUlMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTc0MDIwODUsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGY3ZDM1ZWFlNTQxY2I1MTNhYmM0NTBjM2JlNGQyNWUiLCJzdWIiOiJBdWRTeXMifQ.Q8-7dofAubMcSJOgl8hMTHcQ0re62A_d0bK-nmKlXuv1MXsU55JfjAZKgXasB_AtN6lXboDAwKBd533ex8Y6dYnMjSC0XaP1SUlJS-038fJ0tT7px46lyQ6A6NStCWSJvAADMMyQ8PR09WC1Yc-0mEU8ZzERlJGzTRg80b0DDG7P8vHJkBCLVDYYYCcoAh9EDEWS3NAU1kCPZ3ebyG7bhoxVvbwPizeLIi67B-U84bT84lqqexhtz7aF4FdrMCylxjCm3gb1KUaV1e9hTj_QA0w5pyl3XRl8epeoDnRhXw8zQ-EO8ZpocYB456xE6HivV5mpP6PMMl7ifIcunqgDLQ',
            'grpcSignature' => '2901dc61c18ebc7c0db3c9bab643a281',
            'providerCode' => 'ORS',
            'apiUrl' => 'https://apollo2.all5555.com',
            'operatorName' => 'mog052slotbrl',
            'publicKey' => 'vAaAYEWbtHEdshR5fZGK4lDpYHGCI2DE',
            'privateKey' => 'a8CpWy7PH7MkrrNSgxxlKZIq3TNsCVxb',
            'arcadeGameList' => json_decode(env('ORS_ARCADE_GAMES'), true)
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('BRL');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_OrsPHP_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJPUlMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTc0MDIwODUsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGY3ZDM1ZWFlNTQxY2I1MTNhYmM0NTBjM2JlNGQyNWUiLCJzdWIiOiJBdWRTeXMifQ.Q8-7dofAubMcSJOgl8hMTHcQ0re62A_d0bK-nmKlXuv1MXsU55JfjAZKgXasB_AtN6lXboDAwKBd533ex8Y6dYnMjSC0XaP1SUlJS-038fJ0tT7px46lyQ6A6NStCWSJvAADMMyQ8PR09WC1Yc-0mEU8ZzERlJGzTRg80b0DDG7P8vHJkBCLVDYYYCcoAh9EDEWS3NAU1kCPZ3ebyG7bhoxVvbwPizeLIi67B-U84bT84lqqexhtz7aF4FdrMCylxjCm3gb1KUaV1e9hTj_QA0w5pyl3XRl8epeoDnRhXw8zQ-EO8ZpocYB456xE6HivV5mpP6PMMl7ifIcunqgDLQ',
            'grpcSignature' => '2901dc61c18ebc7c0db3c9bab643a281',
            'providerCode' => 'ORS',
            'apiUrl' => 'https://apollo2.all5555.com',
            'operatorName' => 'mog052slotphp',
            'publicKey' => '4NUH3zFeOXmhe5PACHTe7uV92vYStthj',
            'privateKey' => '2JZmxfaFCRzbhes2SHb7tFpdQKp8Cc2p',
            'arcadeGameList' => json_decode(env('ORS_ARCADE_GAMES'), true)
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('PHP');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_OrsTHB_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJPUlMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTc0MDIwODUsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGY3ZDM1ZWFlNTQxY2I1MTNhYmM0NTBjM2JlNGQyNWUiLCJzdWIiOiJBdWRTeXMifQ.Q8-7dofAubMcSJOgl8hMTHcQ0re62A_d0bK-nmKlXuv1MXsU55JfjAZKgXasB_AtN6lXboDAwKBd533ex8Y6dYnMjSC0XaP1SUlJS-038fJ0tT7px46lyQ6A6NStCWSJvAADMMyQ8PR09WC1Yc-0mEU8ZzERlJGzTRg80b0DDG7P8vHJkBCLVDYYYCcoAh9EDEWS3NAU1kCPZ3ebyG7bhoxVvbwPizeLIi67B-U84bT84lqqexhtz7aF4FdrMCylxjCm3gb1KUaV1e9hTj_QA0w5pyl3XRl8epeoDnRhXw8zQ-EO8ZpocYB456xE6HivV5mpP6PMMl7ifIcunqgDLQ',
            'grpcSignature' => '2901dc61c18ebc7c0db3c9bab643a281',
            'providerCode' => 'ORS',
            'apiUrl' => 'https://apollo2.all5555.com',
            'operatorName' => 'mog052slotthb',
            'publicKey' => 'YXFdYTmY8wMa4FtxuQ4EqS0QQLc0vHNS',
            'privateKey' => 'XOJsCMPRyevzB8JS2MU9QjWkSuydlrLr',
            'arcadeGameList' => json_decode(env('ORS_ARCADE_GAMES'), true)
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('THB');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_OrsUSD_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJPUlMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTc0MDIwODUsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGY3ZDM1ZWFlNTQxY2I1MTNhYmM0NTBjM2JlNGQyNWUiLCJzdWIiOiJBdWRTeXMifQ.Q8-7dofAubMcSJOgl8hMTHcQ0re62A_d0bK-nmKlXuv1MXsU55JfjAZKgXasB_AtN6lXboDAwKBd533ex8Y6dYnMjSC0XaP1SUlJS-038fJ0tT7px46lyQ6A6NStCWSJvAADMMyQ8PR09WC1Yc-0mEU8ZzERlJGzTRg80b0DDG7P8vHJkBCLVDYYYCcoAh9EDEWS3NAU1kCPZ3ebyG7bhoxVvbwPizeLIi67B-U84bT84lqqexhtz7aF4FdrMCylxjCm3gb1KUaV1e9hTj_QA0w5pyl3XRl8epeoDnRhXw8zQ-EO8ZpocYB456xE6HivV5mpP6PMMl7ifIcunqgDLQ',
            'grpcSignature' => '2901dc61c18ebc7c0db3c9bab643a281',
            'providerCode' => 'ORS',
            'apiUrl' => 'https://apollo2.all5555.com',
            'operatorName' => 'mog052slotusd',
            'publicKey' => 'BPCOai2ys6l85Gt7EAK3bw1AeJruzDyZ',
            'privateKey' => 'e1EaAbWCwfCXxZwBxOFyiaYzr7ukXNFZ',
            'arcadeGameList' => json_decode(env('ORS_ARCADE_GAMES'), true)
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('USD');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_OrsVND_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJPUlMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTc0MDIwODUsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGY3ZDM1ZWFlNTQxY2I1MTNhYmM0NTBjM2JlNGQyNWUiLCJzdWIiOiJBdWRTeXMifQ.Q8-7dofAubMcSJOgl8hMTHcQ0re62A_d0bK-nmKlXuv1MXsU55JfjAZKgXasB_AtN6lXboDAwKBd533ex8Y6dYnMjSC0XaP1SUlJS-038fJ0tT7px46lyQ6A6NStCWSJvAADMMyQ8PR09WC1Yc-0mEU8ZzERlJGzTRg80b0DDG7P8vHJkBCLVDYYYCcoAh9EDEWS3NAU1kCPZ3ebyG7bhoxVvbwPizeLIi67B-U84bT84lqqexhtz7aF4FdrMCylxjCm3gb1KUaV1e9hTj_QA0w5pyl3XRl8epeoDnRhXw8zQ-EO8ZpocYB456xE6HivV5mpP6PMMl7ifIcunqgDLQ',
            'grpcSignature' => '2901dc61c18ebc7c0db3c9bab643a281',
            'providerCode' => 'ORS',
            'apiUrl' => 'https://apollo2.all5555.com',
            'operatorName' => 'mog052slotvnd',
            'publicKey' => 'L3hEACJsTTLn8LSXogwkr3CDDt0LGmVG',
            'privateKey' => 'I1t27U78I8jnd4BVg1SRQT3aWlTLGh1B',
            'arcadeGameList' => json_decode(env('ORS_ARCADE_GAMES'), true)
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('VND');

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
            ['apiUrl'],
            ['operatorName'],
            ['publicKey'],
            ['privateKey'],
            ['arcadeGameList']
        ];
    }

    public function getCredentialValue($credentials, $field)
    {
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
            case 'apiUrl':
                return $credentials->getApiUrl();
            case 'operatorName':
                return $credentials->getOperatorName();
            case 'publicKey':
                return $credentials->getPublicKey();
            case 'privateKey':
                return $credentials->getPrivateKey();
            case 'arcadeGameList':
                return $credentials->getArcadeGameList();
            default:
                return null;
        }
    }
}