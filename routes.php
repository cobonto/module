<?php

Route::group(
    [
        'middleware' => ['web', 'admin'],
        'prefix'=>'admin',
    ],
    function ()
    {
        \Module\Classes\Hook::execute('moduleAdminRoutes');
    });

Route::group(
    ['middleware' => ['web'],
    ],
    function ()
    {
        \Module\Classes\Hook::execute('moduleRoutes');
    });
