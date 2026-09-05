<?php

use Ifds\HttpAdapter\Support\ExtractsTolerantFields;

function extractor(): object
{
    return new class
    {
        use ExtractsTolerantFields;

        public function str(array $p, array $k, string $d = ''): string
        {
            return $this->extractString($p, $k, $d);
        }

        public function nullableStr(array $p, array $k): ?string
        {
            return $this->extractNullableString($p, $k);
        }

        public function int(array $p, array $k, int $d = 0): int
        {
            return $this->extractInt($p, $k, $d);
        }

        public function nullableInt(array $p, array $k): ?int
        {
            return $this->extractNullableInt($p, $k);
        }

        public function bool(array $p, array $k, bool $d = false): bool
        {
            return $this->extractBool($p, $k, $d);
        }

        public function collection(array $p, array $k = ['data', 'Data', 'items']): array
        {
            return $this->extractCollection($p, $k);
        }
    };
}

it('returns the first present key regardless of casing', function () {
    expect(extractor()->str(['Message' => 'hello'], ['message', 'Message']))->toBe('hello');
});

it('returns the default when no candidate key is present', function () {
    expect(extractor()->str([], ['message', 'Message'], 'fallback'))->toBe('fallback');
});

it('skips null values and continues to the next candidate key', function () {
    expect(extractor()->str(['message' => null, 'Message' => 'real'], ['message', 'Message']))->toBe('real');
});

it('casts extracted integers', function () {
    expect(extractor()->int(['count' => '42'], ['count']))->toBe(42)
        ->and(extractor()->int([], ['count'], 7))->toBe(7);
});

it('coerces truthy and falsy representations to bool', function () {
    expect(extractor()->bool(['isSuccess' => 'true'], ['isSuccess', 'IsSuccess']))->toBeTrue()
        ->and(extractor()->bool(['IsSuccess' => false], ['isSuccess', 'IsSuccess']))->toBeFalse()
        ->and(extractor()->bool([], ['isSuccess'], true))->toBeTrue();
});

it('returns null from nullable extractors when absent', function () {
    expect(extractor()->nullableStr([], ['x']))->toBeNull()
        ->and(extractor()->nullableInt([], ['x']))->toBeNull();
});

it('extracts a collection from any candidate container key', function () {
    expect(extractor()->collection(['Data' => [1, 2, 3]]))->toBe([1, 2, 3])
        ->and(extractor()->collection(['items' => ['a']]))->toBe(['a']);
});

it('returns an empty array when the collection key is missing or not an array', function () {
    expect(extractor()->collection([]))->toBe([])
        ->and(extractor()->collection(['data' => 'not-an-array']))->toBe([]);
});
