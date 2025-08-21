<?php

return [
    'personal_limits' => [
        'projects' => 1,
        'environments_per_project' => 4,
    ],
    // Defaults for organization teams (null means unlimited)
    'org_defaults' => [
        'projects' => null,
        'environments_per_project' => null,
    ],
    // Alias to maintain backwards compatibility
    'org_limits' => [
        'projects' => null,
        'environments_per_project' => null,
    ],
];
