<?php

namespace App\Core\Models;

use App\Core\Enums\InquiryType;
use App\Core\Events\InquiryCreated;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property InquiryType $inquiry
 * @property string $message
 */
class Inquiry extends Model
{
    protected $fillable = [
        'name',
        'email',
        'inquiry',
        'message',
    ];

    protected $casts = [
        'inquiry' => InquiryType::class,
    ];

    /**
     * @var array<string, string>
     */
    protected $dispatchesEvents = [
        'created' => InquiryCreated::class,
    ];
}
