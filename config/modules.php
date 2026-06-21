<?php

return [
    'lang' => 'ru',
    'stubs' => [
        'config'          => 'Configs/{OBJECTS}.php',
        'controller'      => 'Controllers/{MODEL}Controller.php',
        'lang'            => 'Langs/{LANG}.json',
        'lang-fields'     => 'Langs/{LANG}/fields.php',
        'migration'       => 'Migrations/{DATETIME}_{TABLE}.php',
        'model'           => 'Models/{MODEL}.php',
        'module'          => 'module.php',
        'repository'      => 'Repositories/{MODEL}Repository.php',
        'requests-store'  => 'Requests/{MODEL}StoreRequest.php',
        'requests-update' => 'Requests/{MODEL}UpdateRequest.php',
        'routes'          => 'Routes/web.php',
        'service'         => 'Services/{MODEL}Service.php',
        'views-create'    => 'Views/create.blade.php',
        'views-delete'    => 'Views/delete.blade.php',
        'views-edit'      => 'Views/edit.blade.php',
        'views-index'     => 'Views/index.blade.php',
        'views-show'      => 'Views/show.blade.php',
    ],
    'parent_preset' => 'parent',
    'presets' => [
        'parent' => ['module'],
    ]
];
