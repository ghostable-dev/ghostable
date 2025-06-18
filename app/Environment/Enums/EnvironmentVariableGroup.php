<?php

namespace App\Environment\Enums;

enum EnvironmentVariableGroup: string
{
    case App = 'app';
    case Database = 'database';
    case Cache = 'cache';
    case Queue = 'queue';
    case Mail = 'mail';
    //case Services = 'services';
    case Aws = 'aws';
    case Pusher = 'pusher';
    case Logging = 'logging';
    case Other = 'other';
}