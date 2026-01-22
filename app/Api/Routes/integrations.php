<?php

use App\Api\Integrations\V1\Http\Controllers\ShowOrganization;
use Illuminate\Support\Facades\Route;

Route::get('organization', ShowOrganization::class);
