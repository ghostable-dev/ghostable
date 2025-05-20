<?php

namespace App\Core\Concerns;

use App\Core\Attributes\On;
use ReflectionClass;

trait HandlesModelEventsWithAttributes
{
    protected static function bootHandlesModelEventsWithAttributes()
    {
        $reflect = new ReflectionClass(static::class);

        foreach ($reflect->getMethods() as $method) {
            foreach ($method->getAttributes(On::class) as $attribute) {
                $event = $attribute->newInstance()->event;

                if (method_exists(static::class, $event)) {
                    static::$event([new static, $method->name]);
                } else {
                    static::registerModelEvent($event, [new static, $method->name]);
                }
            }
        }
    }
}
