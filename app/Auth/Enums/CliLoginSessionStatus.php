<?php

namespace App\Auth\Enums;

enum CliLoginSessionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Expired = 'expired';
    case VerificationRequired = 'verification_required';
}
