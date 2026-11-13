<?php

return [
    'provider' => strtolower(trim((string) env('DETECTION_PROVIDER', 'local'))),

    'inference' => [
        'url' => env('INFERENCE_URL', 'http://127.0.0.1:5001'),
        'token' => env('INFERENCE_TOKEN', ''),
        'autostart' => filter_var(env('INFERENCE_AUTOSTART', true), FILTER_VALIDATE_BOOL),
        'classes' => [
            'caterpillar',
            'leafhopper',
            'planthopper',
            'rice_bug',
            'stem_borer',
            'weevil',
        ],
        'connect_timeout' => (int) env('INFERENCE_CONNECT_TIMEOUT', 2),
        'timeout' => (int) env('INFERENCE_TIMEOUT', 60),
    ],

    'roboflow' => [
        'api_key' => env('ROBOFLOW_API_KEY', ''),
        'api_url' => env('ROBOFLOW_API_URL', 'https://api.roboflow.com'),
        'workflows' => [
            'https://serverless.roboflow.com/karinas-workspace-vscjh/workflows/rice-pest-plan-uvsgi',
            'https://serverless.roboflow.com/karinas-workspace-vscjh/workflows/rice-pest-dmnia-fvhbu-1-rfdetr-small-t1-vrice-pest-dmnia-fvhbu-1-rfdetr-small-t1-logic',
            'https://serverless.roboflow.com/karinas-workspace-vscjh/workflows/rice-pest-bb-fbkyn-stm72-1-rfdetr-small-t1-vrice-pest-bb-fbkyn-stm72-1-rfdetr-small-t1-logic',
            'https://serverless.roboflow.com/karinas-workspace-vscjh/workflows/rice-pest-apd1y-emine-1-rfdetr-small-t1-vrice-pest-apd1y-emine-1-rfdetr-small-t1-logic',
        ],
        'connect_timeout' => (int) env('ROBOFLOW_CONNECT_TIMEOUT', 10),
        'timeout' => (int) env('ROBOFLOW_TIMEOUT', 45),
        'ca_bundle' => env('ROBOFLOW_CA_BUNDLE', base_path('cacert.pem')),
    ],

    'model_dir' => env('MODEL_DIR', base_path('../models')),
    'upload_dir' => env('UPLOAD_DIR', public_path('static/uploads')),
    'image_max_bytes' => 12 * 1024 * 1024,
];
