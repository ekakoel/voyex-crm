<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Fail Open
    |--------------------------------------------------------------------------
    |
    | Jika true, modul dianggap aktif saat tabel belum ada atau data modul
    | belum dibuat. Set false setelah migrasi stabil di semua environment
    | agar modul wajib terdaftar dan aktif.
    |
    */
    'fail_open' => env('MODULE_FAIL_OPEN', false),
];
