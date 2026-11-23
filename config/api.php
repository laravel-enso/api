<?php

return [
    'debug' => (bool) env('API_DEBUG', false),
    'errorNotificationUserIds' => array_values(array_filter(
        array_map('trim', explode(',', env('API_ERROR_NOTIFICATION_USER_IDS', ''))),
        fn ($id) => $id !== '',
    )),
];
