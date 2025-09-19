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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry whereInquiry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inquiry whereUpdatedAt($value)
 *
 * @mixin \Eloquent
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
