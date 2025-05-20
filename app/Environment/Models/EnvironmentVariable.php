<?php

namespace App\Environment\Models;

use App\Project\Models\Project;
use Database\Factories\EnvironmentFactory;
use Database\Factories\EnvironmentVariableFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnvironmentVariable extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    
    protected $fillable = [
        'key',
        'value',
    ];
    
    protected $casts = [
        'value' => 'encrypted',
    ];
    
    public static function newFactory(): EnvironmentVariableFactory
    {
        return EnvironmentVariableFactory::new();
    }
    
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }
}
