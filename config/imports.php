<?php

return [

    // Imports with more data rows than this run on the queue (needs `php artisan queue:work`);
    // smaller ones run straight away in the request.
    'queue_threshold' => (int) env('IMPORT_QUEUE_THRESHOLD', 500),

    'max_rows' => (int) env('IMPORT_MAX_ROWS', 20000),

    'max_file_kb' => (int) env('IMPORT_MAX_FILE_KB', 20480),

];
