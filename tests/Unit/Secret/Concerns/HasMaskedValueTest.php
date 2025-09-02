<?php

use App\Secret\Concerns\HasMaskedValue;

it('displays masked value', function () {
    $obj = new class {
        use HasMaskedValue;
    };

    expect($obj->displayValue())->toBe('••••••••••');
});
