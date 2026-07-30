<?php

declare(strict_types=1);

namespace E164;

use Composer\InstalledVersions;
use E164\Exception\ApiException;
use E164\Exception\AuthenticationException;
use E164\Exception\InvalidPhoneNumberException;
use E164\Exception\RateLimitException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use JsonException;
use OutOfBoundsException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Client for the e164.com phone number lookup API.
 */
final class E164
{
    /**
     * Reported in the User-Agent when the installed version cannot be
     * determined, e.g. when the SDK is loaded without Composer's runtime.
     */
    public const FALLBACK_VERSION = '3.0.0';

    private const DEFAULT_BASE_URL = 'https://e164.com/';
    private const PACKAGE_NAME = 'e164-com/e164-php-sdk';

    /** E.164 caps a full international number at 15 digits. */
    private const MAX_DIGITS = 15;

    private const DEFAULT_TIMEOUT = 10.0;
    private const DEFAULT_CONNECT_TIMEOUT = 5.0;

    private readonly ClientInterface $client;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly string $baseUrl;

    /**
     * @param ClientInterface|null        $client         Any PSR-18 client. Defaults to a Guzzle
     *                                                    client with sane timeouts. Injected clients
     *                                                    need no particular configuration — this SDK
     *                                                    builds absolute URLs and sets its own headers,
     *                                                    so a bare client works.
     * @param string|null                 $apiKey         Optional API key, sent as `X-API-Key`.
     * @param RequestFactoryInterface|null $requestFactory Any PSR-17 request factory.
     * @param string|null                 $baseUrl        Override the API base URL, e.g. for a staging host.
     */
    public function __construct(
        ?ClientInterface $client = null,
        private readonly ?string $apiKey = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?string $baseUrl = null,
    ) {
        $this->client = $client ?? new Client([
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
        ]);

        $this->requestFactory = $requestFactory ?? new HttpFactory();
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/') . '/';
    }

    /**
     * Looks up the best match for a phone number.
     *
     * @param string $number A phone number in any format; everything that is not
     *                       a digit is discarded, so "+44 113 391 0781" and
     *                       "441133910781" are equivalent.
     *
     * @throws InvalidPhoneNumberException When the number holds no digits, exceeds 15 digits, or has no record.
     * @throws AuthenticationException     When the API rejects the API key.
     * @throws RateLimitException          When the caller is being rate limited.
     * @throws ApiException                When the request fails or the response cannot be parsed.
     */
    public function lookup(string $number): LookupResult
    {
        return $this->lookupAll($number)[0];
    }

    /**
     * Looks up every record the API holds for a number, best match first.
     *
     * @return non-empty-list<LookupResult>
     *
     * @throws InvalidPhoneNumberException When the number holds no digits, exceeds 15 digits, or has no record.
     * @throws AuthenticationException     When the API rejects the API key.
     * @throws RateLimitException          When the caller is being rate limited.
     * @throws ApiException                When the request fails or the response cannot be parsed.
     */
    public function lookupAll(string $number): array
    {
        $digits = $this->normalise($number);
        $response = $this->send($digits);

        $records = $this->decode($response, $digits);

        if ($records === []) {
            throw new InvalidPhoneNumberException("Invalid phone number: $digits");
        }

        return array_map(
            static fn (array $record): LookupResult => LookupResult::fromArray($record),
            $records,
        );
    }

    /**
     * Strips every non-digit and rejects what cannot be an E.164 number.
     *
     * Discarding `+` rather than preserving it matters: it kept a stray plus in
     * the middle of an input from silently changing which number was queried.
     */
    private function normalise(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($digits === '') {
            throw new InvalidPhoneNumberException('Invalid phone number: no digits in input');
        }

        if (strlen($digits) > self::MAX_DIGITS) {
            throw new InvalidPhoneNumberException(sprintf(
                'Invalid phone number: %d digits exceeds the E.164 maximum of %d',
                strlen($digits),
                self::MAX_DIGITS,
            ));
        }

        return $digits;
    }

    /**
     * @param string $digits Already normalised to digits only.
     */
    private function send(string $digits): ResponseInterface
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->baseUrl . rawurlencode($digits))
            ->withHeader('User-Agent', 'e164-php-sdk/' . self::version())
            ->withHeader('Referer', $this->baseUrl)
            ->withHeader('Accept', 'application/json');

        if ($this->apiKey !== null) {
            $request = $request->withHeader('X-API-Key', $this->apiKey);
        }

        try {
            return $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ApiException(
                'Could not reach the e164.com API: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decode(ResponseInterface $response, string $digits): array
    {
        $status = $response->getStatusCode();

        $this->guardStatus($response, $status, $digits);

        try {
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiException(
                'The e164.com API returned a response that is not valid JSON: ' . $e->getMessage(),
                $status,
                $e,
            );
        }

        if (!is_array($data) || !array_is_list($data)) {
            throw new ApiException('The e164.com API returned an unexpected payload shape.', $status);
        }

        $records = [];

        foreach ($data as $record) {
            if (!is_array($record)) {
                throw new ApiException('The e164.com API returned an unexpected payload shape.', $status);
            }

            /** @var array<string, mixed> $record */
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Turns a non-success status into the most specific exception available.
     */
    private function guardStatus(ResponseInterface $response, int $status, string $digits): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }

        throw match (true) {
            $status === 401, $status === 403 => new AuthenticationException(
                $this->apiKey === null
                    ? "The e164.com API requires authentication for this request (HTTP $status)."
                    : "The e164.com API rejected the supplied API key (HTTP $status).",
                $status,
            ),
            $status === 429 => new RateLimitException(
                'Rate limit exceeded for the e164.com API.',
                self::retryAfter($response),
            ),
            // The API signals "no record" with 200 and an empty array; a 404 is
            // treated the same way defensively.
            $status === 404 => new InvalidPhoneNumberException("Invalid phone number: $digits"),
            default => new ApiException(
                "The e164.com API returned an unexpected HTTP $status response.",
                $status,
            ),
        };
    }

    /**
     * Reads a delay-seconds `Retry-After` header. A date-form value yields null,
     * since callers are better served picking their own backoff than parsing it.
     */
    private static function retryAfter(ResponseInterface $response): ?int
    {
        $header = trim($response->getHeaderLine('Retry-After'));

        return $header !== '' && ctype_digit($header) ? (int) $header : null;
    }

    /**
     * Resolves the installed package version so the User-Agent cannot drift out
     * of step with the release, as a hardcoded constant did.
     */
    private static function version(): string
    {
        if (!class_exists(InstalledVersions::class)) {
            return self::FALLBACK_VERSION;
        }

        try {
            return InstalledVersions::getPrettyVersion(self::PACKAGE_NAME) ?? self::FALLBACK_VERSION;
        } catch (OutOfBoundsException) {
            return self::FALLBACK_VERSION;
        }
    }
}
