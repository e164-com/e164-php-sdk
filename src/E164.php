<?php

namespace Vendor\E164;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class E164
{
    /** @var ClientInterface */
    private $client;

    /**
     * Constructor.
     *
     * @param ClientInterface|null $client Optional Guzzle client instance.
     */
    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?: new Client([
            'headers' => [
                'User-Agent' => 'MyCustomAgent/1.0 (Not a browser)',
                'Referer' => 'https://www.e164.com/',
            ]
        ]);
    }

    /**
     * Looks up information about a phone number using the e164.com API.
     *
     * @param string $number The phone number to look up.
     * @return Response The response from the API.
     * @throws Exception When the provided number is invalid or not found.
     */
    public function lookup(string $number): Response
    {
        try {
            $number = filter_var($number, FILTER_SANITIZE_NUMBER_INT);
            $url = "https://e164.com/$number";

            $response = $this->client->request('GET', $url)->getBody()->getContents();
            $data = json_decode($response, true);
            
            if (empty($data) || !isset($data[0])) {
                throw new Exception("Invalid phone number: $number");
            }

            $response_object = new Response();
            $data = $data[0];

            $response_object->setPrefix($data['prefix'] ?? null);
            $response_object->setCallingCode($data['calling_code'] ?? null);
            $response_object->setIso3($data['iso3'] ?? null);
            $response_object->setTadig($data['tadig'] ?? null);
            $response_object->setMccmnc($data['mccmnc'] ?? null);
            $response_object->setType($data['type'] ?? null);
            $response_object->setLocation($data['location'] ?? null);
            $response_object->setOperatorBrand($data['operator_brand'] ?? null);
            $response_object->setOperatorCompany($data['operator_company'] ?? null);
            $response_object->setTotalLengthMin($data['total_length_min'] ?? null);
            $response_object->setTotalLengthMax($data['total_length_max'] ?? null);
            $response_object->setWeight($data['weight'] ?? null);
            $response_object->setSource($data['source'] ?? null);

            return $response_object;
        } catch (Exception $e) {
            // Check if this is the specific invalid phone number exception
            if (strpos($e->getMessage(), "Invalid phone number:") === 0) {
                throw $e; // Rethrow the original exception
            }
            throw new Exception("Error processing phone number data: " . $e->getMessage());
        }
    }
}