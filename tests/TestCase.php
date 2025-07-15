<?php

namespace Tests;

use App\Core\Concerns\CreatesAccountData;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesAccountData;
}
