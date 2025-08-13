<?php

namespace App\Environment\Enums;

enum PushMode: string
{
    case ADDITIVE = 'additive';
    case REPLACE = 'replace';
}
