<?php

Route::post('laralytics', function () {

    

    return response()->json(Laralytics::payload());
});
