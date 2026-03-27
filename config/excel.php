<?php

return [
    'exports' => [
        'chunk_size'             => 1000,
        'pre_calculate_formulas' => false,
        'csv'                    => [
            'delimiter'              => ',',
            'enclosure'              => '"',
            'line_ending'            => PHP_EOL,
            'use_bom'                => false,
            'include_separator_line' => false,
            'excel_compatibility'    => false,
        ],
    ],

    'imports' => [
        'read_only' => true,
        'ignore_empty' => true,
        'heading_row' => [
            'formatter' => 'slug',
        ],
    ],

    'extension_detector' => [
        'xlsx'     => 'Xlsx',
        'xlsm'     => 'Xlsx',
        'xltx'     => 'Xlsx',
        'xltm'     => 'Xlsx',
        'xls'      => 'Xls',
        'xlt'      => 'Xls',
        'ods'      => 'Ods',
        'ots'      => 'Ods',
        'slk'      => 'Slk',
        'xml'      => 'Xml',
        'gnumeric' => 'Gnumeric',
        'htm'      => 'Html',
        'html'     => 'Html',
        'csv'      => 'Csv',
        'txt'      => 'Csv',
        'pdf'      => 'Dompdf',
    ],

    'value_binder' => [
        'default' => Maatwebsite\Excel\DefaultValueBinder::class,
    ],

    'cache' => [
        'driver' => 'memory',
        'batch' => [
            'memory_limit' => 60000,
        ],
        'illuminate' => [
            'store' => null,
        ],
    ],

    'transactions' => [
        'handler' => 'db',
    ],

    'temporary_files' => [
        'local_path'          => storage_path('framework/laravel-excel'),
        'remote_disk'         => null,
        'remote_prefix'       => null,
        'force_resync_remote' => null,
    ],
];
