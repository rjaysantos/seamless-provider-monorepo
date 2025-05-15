<?php

namespace App\Libraries;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Logger
{
    private $id;

    protected $thirdParty = [];
    protected $grpcWallet = [];
    protected $exception = [];
    protected $decrypted;

    private $requestDateTime;
    private $startDateTime;

    public function __construct()
    {
        $this->id = uniqid();
    }

    public function exception($exception)
    {
        $this->exception = [
            'thrown' => get_class($exception),
            'class' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage(),
        ];
    }

    public function startLog()
    {
        $this->startDateTime = Carbon::now();
    }

    public function writeLog(Request $request, ?string $response = null)
    {
        $responseDateTime = Carbon::now();

        $main = [
            'main' => [
                'request' => [
                    'url' => $request->url(),
                    'body' => $request->all(),
                    'decryptedBody' => $this->decrypted,
                ],
                'response' => $response,
                'requestTime' => $this->startDateTime->format('Y-m-d H:i:s.u'),
                'responseTime' => $responseDateTime->format('Y-m-d H:i:s.u'),
                'executionTime' => $responseDateTime->floatDiffInSeconds(Carbon::parse($this->startDateTime)),
            ]
        ];
        $logData[] = json_encode($main);

        if (empty($this->thirdParty) === false)
            $logData[] = json_encode(['thirdParty' => $this->thirdParty]);

        if (empty($this->grpcWallet) === false)
            $logData[] = json_encode(['grpcWallet' => $this->grpcWallet]);

        if (empty($this->exception) === false)
            $logData[] = json_encode(['exception' => $this->exception]);

        Log::info(implode(" \n ", $logData),  $main);
    }

    public function startThirdPartyRequest()
    {
        $this->requestDateTime = Carbon::now();
    }

    public function logThirdParty($request, $requestHeaders, $url, $response, $statusCode)
    {
        $responseDateTime = Carbon::now();

        $this->thirdParty[] = [
            'request' => [
                'url' => $url,
                'body' => $request,
                'headers' => $requestHeaders
            ],
            'response' => $response,
            'statusCode' => $statusCode,
            'requestTime' => $this->requestDateTime->format('Y-m-d H:i:s.u'),
            'responseTime' => $responseDateTime->format('Y-m-d H:i:s.u'),
            'executionTime' => $responseDateTime->floatDiffInSeconds(Carbon::parse($this->requestDateTime))
        ];
    }

    public function logWallet(array $payload, $walletResponse)
    {
        $this->grpcWallet[]['payload'] = $payload;
        $this->grpcWallet[]['walletResponse'] = $walletResponse;
    }

    public function logWalletCredentials(array $walletCredentials)
    {
        $this->grpcWallet[]['credentials'] = $walletCredentials;
    }

    public function logWalletReport(array $report)
    {
        $this->grpcWallet[]['report'] = $report;
    }

    public function resetWalletLog()
    {
        $this->grpcWallet = [];
    }

    public function logDecrypted($decrypted)
    {
        $this->decrypted = $decrypted;
    }
}
