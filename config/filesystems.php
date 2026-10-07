<?php

return ['default' => 'private', 'disks' => ['private' => ['driver' => 'local', 'root' => storage_path('app/private/documents'), 'visibility' => 'private', 'throw' => true], 'local' => ['driver' => 'local', 'root' => storage_path('app/private'), 'throw' => true], 'public' => ['driver' => 'local', 'root' => storage_path('app/public'), 'url' => env('APP_URL').'/storage', 'visibility' => 'public']], 'links' => [public_path('storage') => storage_path('app/public')]];
