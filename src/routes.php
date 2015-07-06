<?php

Route::post('laralytics', function () {
    return response()->json($_POST);
});
