<?php

namespace Vendor\E164;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Vendor\E164\Exception\ApiException;
use Vendor\E164\Exception\InvalidPhoneNumberException;

class E164
{
    private const API_BASE_URL = 'https://e164.com/';
    private const USER_AGENT = 'e164-php-sdk/1.0';

    private ClientInterface $client;
    private ?string $apiKey;

    /**
     * @param ClientInterface|null $client Optional Guzzle client instance.
     * @param string|null $apiKey Optional API key for authenticated requests.
     */
    public function __construct(?ClientInterface $client = null, ?string $apiKey = null)
    {
        $this->apiKey = $apiKey;

        $headers = [
            'User-Agent' => self::USER_AGENT,
            'Referer' => self::API_BASE_URL,
        ];

        if ($this->apiKey !== null) {
            $headers['X-API-Key'] = $this->apiKey;
        }

        $this->client = $client ?? new Client([
            'base_uri' => self::API_BASE_URL,
            'headers' => $headers,
        ]);
    }

    /**
     * Looks up information about a phone number using the e164.com API.
     *
     * @param string $number The phone number to look up (digits only, e.g. "441133910781").
     * @return Response The response from the API.
     * @throws InvalidPhoneNumberException When the provided number is invalid or not found.
     * @throws ApiException When the API request fails.
     */
    public function lookup(string $number): Response
    {
        $number = preg_replace('/[^0-9+]/', '', $number);

        if ($number === '' || $number === null) {
            throw new InvalidPhoneNumberException("Invalid phone number: empty input");
        }

        try {
            $raw = $this->client->request('GET', $number)->getBody()->getContents();
        } catch (\Throwable $e) {
            throw new ApiException("Error processing phone number data: " . $e->getMessage(), 0, $e);
        }

        $data = json_decode($raw, true);

        if (empty($data) || !isset($data[0])) {
            throw new InvalidPhoneNumberException("Invalid phone number: $number");
        }

        $entry = $data[0];
        $response = new Response();

        $response->setPrefix($entry['prefix'] ?? null);
        $response->setCallingCode($entry['calling_code'] ?? null);
        $response->setIso3($entry['iso3'] ?? null);
        $response->setTadig($entry['tadig'] ?? null);
        $response->setMccmnc($entry['mccmnc'] ?? null);
        $response->setType($entry['type'] ?? null);
        $response->setLocation($entry['location'] ?? null);
        $response->setOperatorBrand($entry['operator_brand'] ?? null);
        $response->setOperatorCompany($entry['operator_company'] ?? null);
        $response->setTotalLengthMin($entry['total_length_min'] ?? null);
        $response->setTotalLengthMax($entry['total_length_max'] ?? null);
        $response->setWeight($entry['weight'] ?? null);
        $response->setSource($entry['source'] ?? null);

        return $response;
    }
}
