<?php

const DT_FORMAT = 'M j, Y g:i A T';

if (! function_exists('timezone')) {
    function timezone()
    {
        return optional(Auth::user())->timezone ?? config('app.timezone');
    }
}

if (! function_exists('timezone_options')) {
    function timezone_options(): array
    {
        return [
            'Pacific/Honolulu' => 'Hawaii',
            'America/Anchorage' => 'Alaska',
            'America/Los_Angeles' => 'Pacific US (Los Angeles)',
            'America/Denver' => 'Mountain US (Denver)',
            'America/Chicago' => 'Central US (Chicago)',
            'America/New_York' => 'Eastern US (New York)',
            'Europe/London' => 'United Kingdom (London)',
            'Europe/Paris' => 'Central Europe (Paris)',
            'Africa/Johannesburg' => 'South Africa (Johannesburg)',
            'Asia/Dubai' => 'Gulf (Dubai)',
            'Asia/Kolkata' => 'India (Kolkata)',
            'Asia/Shanghai' => 'China (Shanghai)',
            'Asia/Tokyo' => 'Japan (Tokyo)',
            'Australia/Sydney' => 'Australia (Sydney)',
            'Pacific/Auckland' => 'New Zealand (Auckland)',
            'UTC' => 'UTC',
        ];
    }
}
