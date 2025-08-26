<?php

return [
    'personal_limits' => [
        'projects' => 1,
        'environments_per_project' => 4,
    ],
    // Defaults for organization organizations (null means unlimited)
    'org_defaults' => [
        'projects' => null,
        'environments_per_project' => null,
    ],
    // Alias to maintain backwards compatibility
    'org_limits' => [
        'projects' => null,
        'environments_per_project' => null,
    ],

    'personal_features' => [
        'audits' => false,
        'integrations' => false,
        'advanced_permissions' => false,
    ],

    'org_features' => [
        'audits' => true,
        'integrations' => true,
        'advanced_permissions' => true,
    ],
];
