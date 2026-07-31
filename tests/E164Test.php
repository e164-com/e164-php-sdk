<?php

declare(strict_types=1);

namespace E164\Tests;

use E164\E164;
use E164\Exception\ApiException;
use E164\Exception\AuthenticationException;
use E164\Exception\E164Exception;
use E164\Exception\InvalidPhoneNumberException;
use E164\Exception\NumberNotFoundException;
use E164\Exception\RateLimitException;
use E164\LookupResult;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class E164Test extends TestCase
{
    /**
     * Payload shape as returned by the live API: several fields arrive as JSON
     * numbers, not strings.
     */
    private const LIVE_PAYLOAD = [[
        'prefix' => '44113391',
        'calling_code' => 44,
        'iso3' => 'GBR',
        'tadig' => null,
        'mccmnc' => null,
        'type' => 'GEOGRAPHIC',
        'location' => null,
        'operator_brand' => 'BT',
        'operator_company' => 'BT',
        'total_length_min' => 12,
        'total_length_max' => 12,
        'weight' => 11,
        'source' => 'e164',
    ]];

    /** @var list<RequestInterface> */
    private array $requests = [];

    /**
     * A Guzzle client with no `base_uri` and no default headers, which is what
     * an injected client normally looks like. The SDK must work against it.
     */
    private function mockClient(Response|ConnectException ...$queue): Client
    {
        $stack = HandlerStack::create(new MockHandler(array_values($queue)));
        $stack->push(fn (callable $handler): callable =>
            function (RequestInterface $request, array $options) use ($handler) {
                $this->requests[] = $request;

                return $handler($request, $options);
            });

        return new Client(['handler' => $stack]);
    }

    private function jsonResponse(mixed $payload, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function lastRequest(): RequestInterface
    {
        $last = end($this->requests);
        $this->assertNotFalse($last, 'No HTTP request was sent.');

        return $last;
    }

    // --- Happy path ------------------------------------------------------

    public function testLookupParsesLivePayloadShapeIncludingNumericFields(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)));
        $result = $sdk->lookup('441133910781');

        $this->assertInstanceOf(LookupResult::class, $result);
        $this->assertSame('44113391', $result->getPrefix());
        $this->assertSame(44, $result->getCallingCode());
        $this->assertSame('GBR', $result->getIso3());
        $this->assertNull($result->getTadig());
        $this->assertNull($result->getMccmnc());
        $this->assertSame('GEOGRAPHIC', $result->getType());
        $this->assertNull($result->getLocation());
        $this->assertSame('BT', $result->getOperatorBrand());
        $this->assertSame('BT', $result->getOperatorCompany());
        $this->assertSame(12, $result->getTotalLengthMin());
        $this->assertSame(12, $result->getTotalLengthMax());
        $this->assertSame(11, $result->getWeight());
        $this->assertSame('e164', $result->getSource());
    }

    public function testLookupAllReturnsEveryRecordBestMatchFirst(): void
    {
        $payload = [
            ['prefix' => '44113391', 'weight' => 11],
            ['prefix' => '44113', 'weight' => 5],
        ];

        $sdk = new E164($this->mockClient($this->jsonResponse($payload)));
        $results = $sdk->lookupAll('441133910781');

        $this->assertCount(2, $results);
        $this->assertSame('44113391', $results[0]->getPrefix());
        $this->assertSame('44113', $results[1]->getPrefix());
    }

    public function testLookupReturnsFirstRecordWhenSeveralMatch(): void
    {
        $payload = [['prefix' => '44113391'], ['prefix' => '44113']];

        $sdk = new E164($this->mockClient($this->jsonResponse($payload)));

        $this->assertSame('44113391', $sdk->lookup('441133910781')->getPrefix());
    }

    // --- Request construction (regressions for the base_uri + header bugs)

    public function testBuildsAbsoluteUrlSoInjectedClientsNeedNoBaseUri(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)));
        $sdk->lookup('441133910781');

        $this->assertSame('https://e164.com/441133910781', (string) $this->lastRequest()->getUri());
    }

    public function testApiKeyIsSentEvenWhenAClientIsInjected(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)), 'SECRET-KEY');
        $sdk->lookup('441133910781');

        $this->assertSame('SECRET-KEY', $this->lastRequest()->getHeaderLine('X-API-Key'));
    }

    public function testNoApiKeyHeaderWhenNoKeyConfigured(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)));
        $sdk->lookup('441133910781');

        $this->assertFalse($this->lastRequest()->hasHeader('X-API-Key'));
    }

    public function testSdkHeadersAreSentEvenWhenAClientIsInjected(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)));
        $sdk->lookup('441133910781');

        $request = $this->lastRequest();

        $this->assertStringStartsWith('e164-php-sdk/', $request->getHeaderLine('User-Agent'));
        $this->assertSame('https://e164.com/', $request->getHeaderLine('Referer'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function testUserAgentCarriesAResolvedVersionRatherThanAHardcodedOne(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)));
        $sdk->lookup('441133910781');

        $userAgent = $this->lastRequest()->getHeaderLine('User-Agent');

        $this->assertMatchesRegularExpression('#^e164-php-sdk/\S+$#', $userAgent);
        $this->assertNotSame('e164-php-sdk/1.0', $userAgent);
    }

    public function testBaseUrlCanBeOverridden(): void
    {
        $sdk = new E164(
            $this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)),
            null,
            null,
            'https://staging.e164.com',
        );
        $sdk->lookup('441133910781');

        $request = $this->lastRequest();

        $this->assertSame('https://staging.e164.com/441133910781', (string) $request->getUri());
        $this->assertSame('https://staging.e164.com/', $request->getHeaderLine('Referer'));
    }

    public function testWorksWithAnyPsr18ClientNotJustGuzzle(): void
    {
        $client = new class implements ClientInterface {
            public ?RequestInterface $seen = null;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->seen = $request;

                return new Response(200, [], '[{"prefix":"44113391"}]');
            }
        };

        $sdk = new E164($client, null, new HttpFactory());

        $this->assertSame('44113391', $sdk->lookup('441133910781')->getPrefix());
        $this->assertInstanceOf(RequestInterface::class, $client->seen);
        $this->assertSame('https://e164.com/441133910781', (string) $client->seen->getUri());
    }

    public function testDefaultClientAppliesTimeouts(): void
    {
        $sdk = new E164();

        $property = new \ReflectionProperty(E164::class, 'client');
        $client = $property->getValue($sdk);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertNotNull($client->getConfig('timeout'), 'Default client must set a request timeout.');
        $this->assertNotNull($client->getConfig('connect_timeout'), 'Default client must set a connect timeout.');
    }

    // --- Input normalisation ---------------------------------------------

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function numberFormatProvider(): iterable
    {
        yield 'digits only' => ['441133910781', '441133910781'];
        yield 'leading plus' => ['+441133910781', '441133910781'];
        yield 'formatted' => ['+44-113-391-0781', '441133910781'];
        yield 'spaces' => ['+44 113 391 0781', '441133910781'];
        yield 'parentheses' => ['+44 (113) 391 0781', '441133910781'];
        yield 'stray plus mid-number' => ['44+1133910781', '441133910781'];
        yield 'letters interleaved' => ['44a11b3391c0781', '441133910781'];
    }

    #[DataProvider('numberFormatProvider')]
    public function testStripsEveryNonDigitBeforeQuerying(string $input, string $expectedPath): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)));
        $sdk->lookup($input);

        $this->assertSame("https://e164.com/$expectedPath", (string) $this->lastRequest()->getUri());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function digitlessInputProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'letters' => ['abcdef'];
        yield 'plus only' => ['+'];
        yield 'punctuation' => ['()- '];
    }

    #[DataProvider('digitlessInputProvider')]
    public function testRejectsInputWithNoDigitsWithoutCallingTheApi(string $input): void
    {
        $sdk = new E164($this->mockClient());

        $this->expectException(InvalidPhoneNumberException::class);
        $this->expectExceptionMessage('Invalid phone number: no digits in input');

        try {
            $sdk->lookup($input);
        } finally {
            $this->assertSame([], $this->requests, 'No request should be sent for digitless input.');
        }
    }

    public function testRejectsNumbersLongerThanTheE164Maximum(): void
    {
        $sdk = new E164($this->mockClient());

        $this->expectException(InvalidPhoneNumberException::class);
        $this->expectExceptionMessage('16 digits exceeds the E.164 maximum of 15');

        $sdk->lookup('4411339107810123');
    }

    public function testAcceptsExactlyFifteenDigits(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)));
        $sdk->lookup('441133910781012');

        $this->assertSame('https://e164.com/441133910781012', (string) $this->lastRequest()->getUri());
    }

    // --- Not found -------------------------------------------------------

    public function testEmptyResultSetMeansTheNumberIsUnknown(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse([])));

        $this->expectException(NumberNotFoundException::class);
        $this->expectExceptionMessage('No record found for phone number: 999999999999');

        $sdk->lookup('999999999999');
    }

    public function testNotFoundStatusIsTreatedAsAnUnknownNumber(): void
    {
        $sdk = new E164($this->mockClient(new Response(404, [], 'not found')));

        $this->expectException(NumberNotFoundException::class);
        $this->expectExceptionMessage('No record found for phone number: 999999999999');

        $sdk->lookup('999999999999');
    }

    public function testUnknownNumberCarriesTheNumberItLookedUp(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse([])));

        try {
            $sdk->lookup('+44 (999) 999 9999');
            $this->fail('Expected NumberNotFoundException');
        } catch (NumberNotFoundException $e) {
            $this->assertSame('449999999999', $e->getPhoneNumber());
        }
    }

    public function testUnknownNumberIsStillCaughtAsAnInvalidPhoneNumber(): void
    {
        // BC: 3.0 threw InvalidPhoneNumberException for a number with no record.
        // NumberNotFoundException extends it, so existing catch blocks still fire.
        $sdk = new E164($this->mockClient($this->jsonResponse([])));

        $this->expectException(InvalidPhoneNumberException::class);

        $sdk->lookup('999999999999');
    }

    public function testUnknownNumberIsDistinguishableFromMalformedInput(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse([])));

        try {
            $sdk->lookup('abc');
            $this->fail('Expected InvalidPhoneNumberException');
        } catch (NumberNotFoundException $e) {
            $this->fail('Malformed input must not be reported as a missing record');
        } catch (InvalidPhoneNumberException $e) {
            $this->assertStringContainsString('no digits in input', $e->getMessage());
        }
    }

    // --- Leading zero ----------------------------------------------------

    /**
     * @param string $number A number still carrying a trunk or access prefix.
     */
    #[DataProvider('leadingZeroNumbers')]
    public function testLeadingZeroIsRejectedWithoutARequest(string $number): void
    {
        // A client that fails the test if it is ever called.
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $sdk = new E164($client);

        $this->expectException(InvalidPhoneNumberException::class);
        $this->expectExceptionMessage('country codes never start with 0');

        $sdk->lookup($number);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function leadingZeroNumbers(): array
    {
        return [
            'international access code' => ['0044113910781'],
            'with a plus' => ['+0044113910781'],
            'spaced' => ['00 44 113 391 0781'],
            'UK national trunk prefix' => ['0113 391 0781'],
            'bare zero' => ['0'],
        ];
    }

    public function testLeadingZeroIsNotReportedAsAMissingRecord(): void
    {
        // Regression: forwarded to the API these return no records, which used
        // to surface as "no record found" -- pointing at the wrong problem.
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $sdk = new E164($client);

        try {
            $sdk->lookup('0044113910781');
            $this->fail('Expected InvalidPhoneNumberException');
        } catch (NumberNotFoundException $e) {
            $this->fail('A trunk prefix is a format error, not a missing record');
        } catch (InvalidPhoneNumberException $e) {
            $this->addToAssertionCount(1);
        }
    }

    public function testLeadingZeroIsReportedBeforeTheLengthLimit(): void
    {
        // '00' plus a valid 14-digit number is 16 digits. Reporting the length
        // first named the wrong fix: dropping the access code leaves a number
        // that is well within the limit.
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $sdk = new E164($client);

        $this->expectException(InvalidPhoneNumberException::class);
        $this->expectExceptionMessage('country codes never start with 0');

        $sdk->lookup('0012345678901234');
    }

    public function testTheReplacementSuggestedForALeadingZeroIsItselfAcceptable(): void
    {
        // Regression: the message suggested '+44113910781' -- a digit short of
        // the real number -- so a caller who followed the advice hit a second
        // error, which is the confusion this guard exists to remove.
        $sdk = new E164($this->mockClient($this->jsonResponse(self::LIVE_PAYLOAD)));

        $suggestion = null;

        try {
            $sdk->lookup('00441133910781');
            $this->fail('Expected InvalidPhoneNumberException');
        } catch (InvalidPhoneNumberException $e) {
            if (preg_match("/should be '(\+?\d+)'/", $e->getMessage(), $matches) === 1) {
                $suggestion = $matches[1];
            }
        }

        $this->assertNotNull($suggestion, 'The message must suggest a replacement number.');
        $this->assertSame(
            '441133910781',
            preg_replace('/\D+/', '', $suggestion),
            'The suggested replacement must be the number the docs use as the worked example.',
        );

        // Following the advice must produce a lookup, not another exception.
        $this->assertInstanceOf(LookupResult::class, $sdk->lookup($suggestion));
    }

    // --- Failure mapping -------------------------------------------------

    public function testMalformedJsonIsAnApiFailureNotAnInvalidNumber(): void
    {
        $sdk = new E164($this->mockClient(new Response(200, [], '<html>gateway error</html>')));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not valid JSON');

        $sdk->lookup('441133910781');
    }

    public function testUnexpectedPayloadShapeIsAnApiFailure(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(['prefix' => '44113391'])));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('unexpected payload shape');

        $sdk->lookup('441133910781');
    }

    public function testNonObjectRecordIsAnApiFailure(): void
    {
        $sdk = new E164($this->mockClient($this->jsonResponse(['44113391'])));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('unexpected payload shape');

        $sdk->lookup('441133910781');
    }

    public function testServerErrorPreservesTheStatusCode(): void
    {
        $sdk = new E164($this->mockClient(new Response(503, [], 'unavailable')));

        try {
            $sdk->lookup('441133910781');
            $this->fail('Expected an ApiException.');
        } catch (ApiException $e) {
            $this->assertSame(503, $e->getStatusCode());
            $this->assertSame(503, $e->getCode());
        }
    }

    public function testRateLimitExposesRetryAfter(): void
    {
        $sdk = new E164($this->mockClient(new Response(429, ['Retry-After' => '30'], 'slow down')));

        try {
            $sdk->lookup('441133910781');
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertSame(30, $e->getRetryAfter());
        }
    }

    public function testRateLimitWithoutUsableRetryAfterYieldsNull(): void
    {
        $sdk = new E164($this->mockClient(
            new Response(429, ['Retry-After' => 'Wed, 21 Oct 2026 07:28:00 GMT'], 'slow down'),
        ));

        try {
            $sdk->lookup('441133910781');
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertNull($e->getRetryAfter());
        }
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function authFailureProvider(): iterable
    {
        yield 'unauthorized' => [401];
        yield 'forbidden' => [403];
    }

    #[DataProvider('authFailureProvider')]
    public function testAuthFailuresThrowAuthenticationException(int $status): void
    {
        $sdk = new E164($this->mockClient(new Response($status, [], 'denied')), 'bad-key');

        try {
            $sdk->lookup('441133910781');
            $this->fail('Expected an AuthenticationException.');
        } catch (AuthenticationException $e) {
            $this->assertSame($status, $e->getStatusCode());
            $this->assertStringContainsString('rejected the supplied API key', $e->getMessage());
        }
    }

    public function testAuthFailureWithoutAKeySaysAuthenticationIsRequired(): void
    {
        $sdk = new E164($this->mockClient(new Response(401, [], 'denied')));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('requires authentication');

        $sdk->lookup('441133910781');
    }

    public function testTransportFailureHasNoStatusCode(): void
    {
        $sdk = new E164($this->mockClient(
            new ConnectException('Could not resolve host', new Request('GET', 'https://e164.com/441133910781')),
        ));

        try {
            $sdk->lookup('441133910781');
            $this->fail('Expected an ApiException.');
        } catch (ApiException $e) {
            $this->assertNull($e->getStatusCode(), 'A transport failure has no HTTP status.');
            $this->assertStringContainsString('Could not reach the e164.com API', $e->getMessage());
        }
    }

    // --- Exception hierarchy ---------------------------------------------

    public function testEverySdkExceptionIsCatchableAsE164Exception(): void
    {
        $this->assertInstanceOf(E164Exception::class, new ApiException('x'));
        $this->assertInstanceOf(E164Exception::class, new InvalidPhoneNumberException('x'));
        $this->assertInstanceOf(E164Exception::class, new AuthenticationException('x'));
        $this->assertInstanceOf(E164Exception::class, new RateLimitException('x'));
    }

    public function testRateLimitAndAuthAreCatchableAsApiException(): void
    {
        $this->assertInstanceOf(ApiException::class, new AuthenticationException('x'));
        $this->assertInstanceOf(ApiException::class, new RateLimitException('x'));
    }

    public function testExceptionsRemainCatchableAsRuntimeException(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new ApiException('x'));
        $this->assertInstanceOf(\RuntimeException::class, new InvalidPhoneNumberException('x'));
    }
}
