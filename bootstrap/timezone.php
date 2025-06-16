<?php

const DT_FORMAT = 'M j, Y g:i A T';

if (! function_exists('timezone')) {
    function timezone()
    {
        return optional(Auth::user())->timezone ?? config('app.timezone');
    }
}

if (! function_exists('timezone_options')) {
    function timezone_options()
    {
        if (! $timezones = timezone_identifiers_list()) {
            return [timezone() => timezone()];
        }

        return array_combine($timezones, $timezones);
    }
}
