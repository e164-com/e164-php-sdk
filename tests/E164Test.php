<?php

namespace Vendor\E164\Tests;

use PHPUnit\Framework\TestCase;
use Vendor\E164\E164;
use Vendor\E164\Response;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class E164Test extends TestCase
{
    public function testLookupReturnsResponseObject()
    {
        $e164 = new E164();
        $number = '441133910781'; // Example UK number
        $response = $e164->lookup($number);

        $this->assertInstanceOf(Response::class, $response);

        // Test some expected values
        $this->assertEquals('44113391', $response->getPrefix());
        $this->assertEquals('44', $response->getCallingCode());
        $this->assertEquals('GBR', $response->getIso3());
        $this->assertEquals(null, $response->getTadig());
        $this->assertEquals('234', $response->getMccmnc());
        $this->assertEquals('GEOGRAPHIC', $response->getType());
        $this->assertEquals(null, $response->getLocation());
        $this->assertEquals('BT', $response->getOperatorBrand());
        $this->assertEquals('BT', $response->getOperatorCompany());
        $this->assertEquals('12', $response->getTotalLengthMin());
        $this->assertEquals('12', $response->getTotalLengthMax());
        $this->assertEquals('11', $response->getWeight());
        $this->assertEquals('e164.com', $response->getSource());
    }
    
    public function testLookupThrowsExceptionForInvalidNumber()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid phone number: 00000000000'); // More specific message check
        
        $e164 = new E164();
        $invalidNumber = '00000000000'; // A number that should trigger an invalid response
        $e164->lookup($invalidNumber);
    }
    
    public function testLookupThrowsExceptionForProcessingError()
    {
        // 1. Create a mock Guzzle Client that implements ClientInterface
        $mockClient = $this->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Ensure the mock is properly typed
        $this->assertInstanceOf(ClientInterface::class, $mockClient);

        // 2. Configure the mock's 'request' method to throw an exception
        $request = new Request('GET', 'https://e164.com/12345'); // Dummy request
        $guzzleException = new RequestException("Network Error", $request);
        // Mock the 'request' method instead of 'get'
        $mockClient->method('request')
                   ->with('GET', 'https://e164.com/12345') // Ensure it expects the correct arguments
                   ->willThrowException($guzzleException);

        // 3. Expect the specific Exception from the E164 class
        $this->expectException(Exception::class);
        // Adjust the expected message to match the actual exception format
        $this->expectExceptionMessage('Error processing phone number data: Network Error');

        // 4. Instantiate E164 with the mock client
        $e164 = new E164($mockClient instanceof ClientInterface ? $mockClient : null);

        // 5. Call the lookup method
        $e164->lookup('12345'); // Use a number that would be valid otherwise
    }
}