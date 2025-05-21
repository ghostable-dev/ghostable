<?php

namespace App\Environment\Enums;

enum EnvLineType: string
{
    case ENV = 'env';
    case INVALID = 'invalid';
}