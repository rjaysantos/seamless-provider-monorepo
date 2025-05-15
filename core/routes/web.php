<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('Hello World', 200);
});

foreach (glob(base_path('providers/*/web.php')) as $file) {
    require $file;
}
