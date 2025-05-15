<?php

use Tests\TestCase;
use App\Libraries\Logger;
use App\GameProviders\V2\Jdb\JdbEncryption;
use App\GameProviders\V2\Jdb\Contracts\ICredentials;

class JdbEncryptionTest extends TestCase
{
    private function makeEncryption(): JdbEncryption
    {
        return new JdbEncryption();
    }

    public function test_encrypt_validEncryption_expected()
    {
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getKey')->willReturn('testKey');
        $credentials->method('getIV')->willReturn('testIVtestIVtest');

        $data = [
            'action' => 21,
            'ts' => 1609430400000,
            'parent' => 'testParent',
            'uid' => 'testPlayID',
            'balance' => 1000.00,
            'lang' => 'en',
            'gType' => '0',
            'mType' => '8001'
        ];

        $encryption = $this->makeEncryption();
        $response = $encryption->encrypt(
            credentials: $credentials,
            data: $data
        );

        $this->assertSame(
            expected: 'RK3wn-eTsqaqNKQbX9kz6ngE6UktzyXXHiPZh1mQH1Oz9tITxuP7GdX8SZhtbOMPnONlHoiJ3QGgMYHm7oc' .
            '-OAGBBHBYTr9AWRkb2DgEqVaQEA2iBdBfSLtTeeLIeRA7vjw39gpYIcXqnRKvoMX7JkB58pryC-yDisadqo0TPvU',
            actual: $response
        );
    }

    public function test_decrypt_validEncryption_expected()
    {
        $expected = (object) [
            'action' => 21,
            'ts' => 1609430400000,
            'parent' => 'testParent',
            'uid' => 'testPlayID',
            'balance' => 1000.00,
            'lang' => 'en',
            'gType' => '0',
            'mType' => '8001'
        ];

        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getKey')->willReturn('testKey');
        $credentials->method('getIV')->willReturn('testIVtestIVtest');

        $data = 'RK3wn-eTsqaqNKQbX9kz6ngE6UktzyXXHiPZh1mQH1Oz9tITxuP7GdX8SZhtbOMPnONlHoiJ3QGgMYHm7oc' .
            '-OAGBBHBYTr9AWRkb2DgEqVaQEA2iBdBfSLtTeeLIeRA7vjw39gpYIcXqnRKvoMX7JkB58pryC-yDisadqo0TPvU';

        $encryption = $this->makeEncryption();
        $response = $encryption->decrypt(
            credentials: $credentials,
            data: $data
        );

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_decrypt_mockLogger_expected()
    {
        $expected = (object) [
            'action' => 21,
            'ts' => 1609430400000,
            'parent' => 'testParent',
            'uid' => 'testPlayID',
            'balance' => 1000.00,
            'lang' => 'en',
            'gType' => '0',
            'mType' => '8001'
        ];

        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getKey')->willReturn('testKey');
        $credentials->method('getIV')->willReturn('testIVtestIVtest');

        $data = 'RK3wn-eTsqaqNKQbX9kz6ngE6UktzyXXHiPZh1mQH1Oz9tITxuP7GdX8SZhtbOMPnONlHoiJ3QGgMYHm7oc' .
            '-OAGBBHBYTr9AWRkb2DgEqVaQEA2iBdBfSLtTeeLIeRA7vjw39gpYIcXqnRKvoMX7JkB58pryC-yDisadqo0TPvU';

        $mockLogger = $this->createMock(Logger::class);
        $this->app->instance(Logger::class, $mockLogger);
        $mockLogger->expects($this->once())
            ->method('logDecrypted')
            ->with((array) $expected);

        $encryption = $this->makeEncryption();
        $response = $encryption->decrypt(
            credentials: $credentials,
            data: $data
        );

        $this->assertEquals(expected: $expected, actual: $response);
    }
}