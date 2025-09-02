<?php

use App\Core\Concerns\HasLabel;
use Tests\TestCase;

uses(TestCase::class);

enum ExampleLabel: string
{
    use HasLabel;

    case FOO = 'foo';
    case BAR = 'bar';

    public function label(): string
    {
        return match ($this) {
            self::FOO => 'Foo',
            self::BAR => 'Bar',
        };
    }
}

it('returns select options for enum', function () {
    expect(ExampleLabel::selectOptions())->toBe([
        'foo' => 'Foo',
        'bar' => 'Bar',
    ]);
});

it('returns label for each case', function () {
    expect(ExampleLabel::FOO->label())->toBe('Foo')
        ->and(ExampleLabel::BAR->label())->toBe('Bar');
});
