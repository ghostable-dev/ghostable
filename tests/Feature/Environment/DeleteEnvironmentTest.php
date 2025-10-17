<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Livewire\EnvironmentGeneralSettings;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);