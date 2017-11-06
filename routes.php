<?php

Route::group(
    [
        'middleware' => ['web', 'admin'],
        'prefix'=>config('app.admin_url'),
        'as'=>'admin.',
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
