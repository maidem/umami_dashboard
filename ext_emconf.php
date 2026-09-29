<?php

$EM_CONF['umami_dashboard'] = [
    'title' => 'Maidem Umami Dashboard',
    'description' => 'TYPO3 dashboard widgets showing Umami web analytics (visitors, pageviews).',
    'category' => 'be',
    'author' => 'Maik Demuth',
    'author_email' => 'hi@maidem.de',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'php' => '8.4.0-0.0.0',
            'typo3' => '14.1.0-14.99.99',
            'dashboard' => '14.1.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
