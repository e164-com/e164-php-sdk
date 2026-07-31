<?php

declare(strict_types=1);

namespace E164\Tests;

use E164\LookupResult;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LookupResultTest extends TestCase
{
    public function testEveryFieldDefaultsToNull(): void
    {
        $result = new LookupResult();

        $this->assertNull($result->getPrefix());
        $this->assertNull($result->getCallingCode());
        $this->assertNull($result->getIso3());
        $this->assertNull($result->getTadig());
        $this->assertNull($result->getMccmnc());
        $this->assertNull($result->getType());
        $this->assertNull($result->getLocation());
        $this->assertNull($result->getOperatorBrand());
        $this->assertNull($result->getOperatorCompany());
        $this->assertNull($result->getTotalLengthMin());
        $this->assertNull($result->getTotalLengthMax());
        $this->assertNull($result->getWeight());
        $this->assertNull($result->getSource());
        $this->assertSame([], $result->toArray());
    }

    public function testMissingFieldsAreNull(): void
    {
        $result = LookupResult::fromArray(['prefix' => '44113391']);

        $this->assertSame('44113391', $result->getPrefix());
        $this->assertNull($result->getCallingCode());
        $this->assertNull($result->getWeight());
    }

    public function testJsonNumbersAreCoercedToTheGetterType(): void
    {
        $result = LookupResult::fromArray([
            'calling_code' => 44,
            'total_length_min' => 12,
            'weight' => 11,
        ]);

        $this->assertSame(44, $result->getCallingCode());
        $this->assertSame(12, $result->getTotalLengthMin());
        $this->assertSame(11, $result->getWeight());
    }

    public function testNumericStringsAreAcceptedForIntegerFields(): void
    {
        $result = LookupResult::fromArray([
            'calling_code' => '44',
            'total_length_min' => '12',
            'total_length_max' => '14',
            'weight' => '11',
        ]);

        $this->assertSame(44, $result->getCallingCode());
        $this->assertSame(12, $result->getTotalLengthMin());
        $this->assertSame(14, $result->getTotalLengthMax());
        $this->assertSame(11, $result->getWeight());
    }

    public function testCallingCodeGetterAgreesWithTheRawPayload(): void
    {
        // Regression: getCallingCode() returned '44' while toArray() and
        // jsonSerialize() reported 44, so one field read as two types.
        $result = LookupResult::fromArray(['calling_code' => 44]);

        $this->assertSame(44, $result->getCallingCode());
        $this->assertSame(44, $result->get('calling_code'));
        $this->assertSame(44, $result->toArray()['calling_code']);
        $this->assertJsonStringEqualsJsonString(
            '{"calling_code":44}',
            (string) json_encode($result),
        );
    }

    public function testNonNumericStringForAnIntegerFieldIsNullNotZero(): void
    {
        $result = LookupResult::fromArray(['weight' => 'heavy']);

        $this->assertNull($result->getWeight());
        $this->assertSame('heavy', $result->get('weight'));
    }

    /**
     * A nested value used to raise an uncaught TypeError out of the setters.
     */
    public function testNonScalarFieldReadsAsNullAndSurvivesInTheRawPayload(): void
    {
        $result = LookupResult::fromArray([
            'prefix' => '44113391',
            'location' => ['city' => 'Leeds', 'region' => 'Yorkshire'],
        ]);

        $this->assertNull($result->getLocation());
        $this->assertSame('44113391', $result->getPrefix());
        $this->assertSame(['city' => 'Leeds', 'region' => 'Yorkshire'], $result->get('location'));
    }

    public function testUnknownFieldsStayReachable(): void
    {
        $result = LookupResult::fromArray([
            'prefix' => '44113391',
            'field_added_after_this_release' => 'kept',
        ]);

        $this->assertSame('kept', $result->get('field_added_after_this_release'));
        $this->assertTrue($result->has('field_added_after_this_release'));
        $this->assertFalse($result->has('never_sent'));
        $this->assertNull($result->get('never_sent'));
    }

    public function testHasDistinguishesAnExplicitNullFromAnAbsentField(): void
    {
        $result = LookupResult::fromArray(['tadig' => null]);

        $this->assertTrue($result->has('tadig'));
        $this->assertFalse($result->has('mccmnc'));
    }

    public function testToArrayReturnsThePayloadUntouched(): void
    {
        $payload = [
            'prefix' => '44113391',
            'calling_code' => 44,
            'total_length_min' => 12,
            'tadig' => null,
        ];

        $this->assertSame($payload, LookupResult::fromArray($payload)->toArray());
    }

    public function testJsonEncodesBackToTheOriginalPayload(): void
    {
        $payload = ['prefix' => '44113391', 'calling_code' => 44, 'weight' => 11];

        $this->assertJsonStringEqualsJsonString(
            json_encode($payload, JSON_THROW_ON_ERROR),
            json_encode(LookupResult::fromArray($payload), JSON_THROW_ON_ERROR),
        );
    }

    public function testResultIsImmutable(): void
    {
        $reflection = new ReflectionClass(LookupResult::class);

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith(
                'set',
                $method->getName(),
                'LookupResult must not expose setters.',
            );
        }

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                sprintf('Property $%s must be readonly.', $property->getName()),
            );
        }
    }
}
