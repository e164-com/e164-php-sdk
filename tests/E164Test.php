<?php

namespace Vendor\E164\Tests;

use PHPUnit\Framework\TestCase;
use Vendor\E164\E164;
use Vendor\E164\Response;
use Vendor\E164\Exception\InvalidPhoneNumberException;
use Vendor\E164\Exception\ApiException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class E164Test extends TestCase
{
    private function createMockClient(string $body): ClientInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $client = $this->createMock(ClientInterface::class);
        $client->method('request')->willReturn($response);

        return $client;
    }

    public function testLookupReturnsResponseObject(): void
    {
        $json = json_encode([[
            'prefix' => '44113391',
            'calling_code' => '44',
            'iso3' => 'GBR',
            'tadig' => null,
            'mccmnc' => '234',
            'type' => 'GEOGRAPHIC',
            'location' => null,
            'operator_brand' => 'BT',
            'operator_company' => 'BT',
            'total_length_min' => '12',
            'total_length_max' => '12',
            'weight' => '11',
            'source' => 'e164.com',
        ]]);

        $client = $this->createMockClient($json);
        $e164 = new E164($client);
        $response = $e164->lookup('441133910781');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('44113391', $response->getPrefix());
        $this->assertSame('44', $response->getCallingCode());
        $this->assertSame('GBR', $response->getIso3());
        $this->assertNull($response->getTadig());
        $this->assertSame('234', $response->getMccmnc());
        $this->assertSame('GEOGRAPHIC', $response->getType());
        $this->assertNull($response->getLocation());
        $this->assertSame('BT', $response->getOperatorBrand());
        $this->assertSame('BT', $response->getOperatorCompany());
        $this->assertSame('12', $response->getTotalLengthMin());
        $this->assertSame('12', $response->getTotalLengthMax());
        $this->assertSame('11', $response->getWeight());
        $this->assertSame('e164.com', $response->getSource());
    }

    public function testLookupThrowsExceptionForInvalidNumber(): void
    {
        $client = $this->createMockClient('[]');
        $e164 = new E164($client);

        $this->expectException(InvalidPhoneNumberException::class);
        $this->expectExceptionMessage('Invalid phone number: 00000000000');

        $e164->lookup('00000000000');
    }

    public function testLookupThrowsExceptionForEmptyInput(): void
    {
        $e164 = new E164();

        $this->expectException(InvalidPhoneNumberException::class);
        $this->expectExceptionMessage('Invalid phone number: empty input');

        $e164->lookup('');
    }

    public function testLookupThrowsExceptionForNonNumericInput(): void
    {
        $e164 = new E164();

        $this->expectException(InvalidPhoneNumberException::class);
        $this->expectExceptionMessage('Invalid phone number: empty input');

        $e164->lookup('abcdef');
    }

    public function testLookupThrowsApiExceptionOnNetworkError(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $request = new Request('GET', 'https://e164.com/12345');
        $client->method('request')
            ->willThrowException(new RequestException('Network Error', $request));

        $e164 = new E164($client);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Error processing phone number data: Network Error');

        $e164->lookup('12345');
    }

    public function testLookupThrowsExceptionForMalformedJson(): void
    {
        $client = $this->createMockClient('not json');
        $e164 = new E164($client);

        $this->expectException(InvalidPhoneNumberException::class);

        $e164->lookup('12345');
    }

    public function testLookupStripsNonNumericCharacters(): void
    {
        $json = json_encode([[
            'prefix' => '44113391',
            'calling_code' => '44',
            'iso3' => 'GBR',
            'tadig' => null,
            'mccmnc' => '234',
            'type' => 'GEOGRAPHIC',
            'location' => null,
            'operator_brand' => 'BT',
            'operator_company' => 'BT',
            'total_length_min' => '12',
            'total_length_max' => '12',
            'weight' => '11',
            'source' => 'e164.com',
        ]]);

        $client = $this->createMockClient($json);
        $e164 = new E164($client);
        $response = $e164->lookup('+44-113-391-0781');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('44113391', $response->getPrefix());
    }

    public function testResponseDefaultsToNull(): void
    {
        $response = new Response();

        $this->assertNull($response->getPrefix());
        $this->assertNull($response->getCallingCode());
        $this->assertNull($response->getIso3());
        $this->assertNull($response->getTadig());
        $this->assertNull($response->getMccmnc());
        $this->assertNull($response->getType());
        $this->assertNull($response->getLocation());
        $this->assertNull($response->getOperatorBrand());
        $this->assertNull($response->getOperatorCompany());
        $this->assertNull($response->getTotalLengthMin());
        $this->assertNull($response->getTotalLengthMax());
        $this->assertNull($response->getWeight());
        $this->assertNull($response->getSource());
    }

    public function testConstructorAcceptsCustomClient(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $e164 = new E164($client);

        $this->assertInstanceOf(E164::class, $e164);
    }
}
