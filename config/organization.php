<?php

return [

    'session_key' => 'current_organization_id',

    /*
    |--------------------------------------------------------------------------
    | Pivot role → permission abilities (org-scoped checks)
    |--------------------------------------------------------------------------
    */
    'role_permissions' => [
        'org_admin' => [
            'category.view', 'category.create', 'category.update', 'category.delete',
            'question.view', 'question.create', 'question.update', 'question.delete',
            'exam.view', 'exam.create', 'exam.update', 'exam.delete', 'exam.publish',
            'member.view', 'member.manage',
            'attempt.view_all',
            'settings.org',
        ],
        'editor' => [
            'category.view', 'category.create', 'category.update', 'category.delete',
            'question.view', 'question.create', 'question.update', 'question.delete',
            'exam.view',
        ],
        'viewer' => [
            'exam.view', 'attempt.take', 'attempt.view_own',
        ],
    ],
];
