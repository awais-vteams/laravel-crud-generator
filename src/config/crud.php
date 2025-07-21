<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stubs Path
    |--------------------------------------------------------------------------
    |
    | The stubs path directory to generate crud. You may configure your
    | stubs paths here, allowing you to customize the own stubs of the
    | model,controller or view. Or, you may simply stick with the CrudGenerator defaults!
    |
    | Example: 'stub_path' => resource_path('stubs/')
    | Default: "default"
    | Files:
    |       Controller.stub
    |       Model.stub
    |       Request.stub
    |       views/
    |           bootstrap/
    |               create.stub
    |               edit.stub
    |               form.stub
    |               form-field.stub
    |               index.stub
    |               show.stub
    |               view-field.stub
    */

    'stub_path' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Application Layout
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application layout. This value is used when creating
    | views for crud. Default will be the "layouts.app".
    |
    | layout = false or layout = null will not create the layout files.
    */

    'layout' => 'layouts.app',

    'model' => [
        'namespace' => '{{moduleName}}\App\Models',

        /*
         * Do not make these columns $fillable in Model or views
         */
        'unwantedColumns' => [
            'id',
            'uuid',
            'ulid',
            'password',
            'email_verified_at',
            'remember_token',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
    ],

    'controller' => [
        'namespace' => '{{moduleName}}\App\Http\Controllers',
        'apiNamespace' => '{{moduleName}}\App\Http\Controllers\Api',
    ],

    'resources' => [
        'namespace' => '{{moduleName}}\App\Http\Resources',
    ],

    'livewire' => [
        'namespace' => '{{moduleName}}\App\Livewire',
    ],

    'request' => [
        'namespace' => '{{moduleName}}\App\Http\Requests',
    ],
];
