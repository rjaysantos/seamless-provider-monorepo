<?php

use Tests\TestCase;
use Providers\Hcg\HcgEncryption;
use Providers\Hcg\Contracts\ICredentials;

class HcgEncryptionTest extends TestCase
{
    private function makeEncryption(): HcgEncryption
    {
        return new HcgEncryption;
    }

    public function test_encrypt_validEncryption_expected()
    {
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getSignKey')->willReturn('testSignKey');
        $credentials->method('getEncryptionKey')->willReturn('testEncryptionKey');
        $credentials->method('getWalletApiSignKey')->willReturn('testWalletApiSignKey');

        $data = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1'
        ];

        $encryption = $this->makeEncryption();
        $result = $encryption->encrypt(credentials: $credentials, data: $data);

        $this->assertSame(
            expected: 'xuJgzKqvZNZpuSg0eqWK4f0XGZRr1PICnWS41CSOKbzYbDPcMvv9gU7Eje1L1kk4ga9wpL3ifyR6JrQHpO7qSZoUz6Iejj' .
            'qM/CmMXMWy+h9xDuB+Dx+iKKETz9SpP13qboK5sx7wAseEEoOQ6voWKrEKbfkjrB4Kv1hEbkSCwlHNnu0pIGjNisCi+Pu9z9Fn',
            actual: $result
        );
    }

    public function test_createSignature_validEncryption_expected()
    {
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getWalletApiSignKey')->willReturn('testWalletApiSignKey');

        $data = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1'
        ];

        $encryption = $this->makeEncryption();
        $result = $encryption->createSignature(credentials: $credentials, data: $data);

        $this->assertSame(expected: '13f4d856916370b1b4e2078088d2ef0e', actual: $result);
    }
}