<?php

namespace App\Messaging\Enums;

enum MessageStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case SUPPRESSED = 'suppressed';
    case FAILED = 'failed';
}
