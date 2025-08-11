<?php

namespace App\Environment\Variable\Enums;

enum VariableGroup: string
{
    case App = 'app';
    case Database = 'database';
    case Cache = 'cache';
    case Queue = 'queue';
    case Mail = 'mail';
    // case Services = 'services';
    case Aws = 'AWS';
    case Pusher = 'pusher';
    case Logging = 'logging';
    case Other = 'other';
}
