<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Http;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class LaravelHttpClient
{
    private $logger;

    public function __construct()
    {
        $this->logger = app()->make(Logger::class);
    }

    public function post(string $url, array $request, array $headers = []): object
    {
        $this->logger->startThirdPartyRequest();

        $response = Http::withHeaders($headers)->timeout(5)->post($url, $request);

        $this->logger->logThirdParty(
            $request,
            $headers,
            $url,
            $response->body(),
            $response->status()
        );

        if ($response->status() != 200)
            throw new ThirdPartyApiErrorException('Http Status ' . $response->status());

        $responseObj = json_decode($response->body());

        if (is_null($responseObj) === true)
            throw new ThirdPartyApiErrorException('Third Party Api Response ' . $response->body());

        return $responseObj;
    }

    public function get(string $url, array $request, array $headers): object
    {
        $this->logger->startThirdPartyRequest();

        $response = Http::withHeaders($headers)->get($url, $request);

        $this->logger->logThirdParty(
            $request,
            $headers,
            $url,
            $response->body(),
            $response->status()
        );

        if ($response->status() != 200)
            throw new ThirdPartyApiErrorException('Http Status ' . $response->status());

        $responseObj = json_decode($response->body());

        if (is_null($responseObj) === true)
            throw new ThirdPartyApiErrorException('Third Party Api Response ' . $response->body());

        return $responseObj;
    }

    public function postAsForm(string $url, array $request, array $header = []): object
    {
        $this->logger->startThirdPartyRequest();

        $response = Http::withHeaders($header)->asForm()->timeout(5)->post($url, $request);

        $this->logger->logThirdParty(
            $request,
            $header,
            $url,
            $response->body(),
            $response->status()
        );

        if ($response->status() != 200)
            throw new ThirdPartyApiErrorException('Http Status ' . $response->status());

        $responseObj = json_decode($response->body());

        if (is_null($responseObj) === true)
            throw new ThirdPartyApiErrorException('Third Party Api Response ' . $response->body());

        return $responseObj;
    }
}
