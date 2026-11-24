<?php

return [
    'batch_size' => (int) env('REPORT_BATCH_SIZE', 50),
    'download_ttl_minutes' => (int) env('REPORT_DOWNLOAD_TTL_MINUTES', 30),
];
