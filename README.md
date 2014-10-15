Installation
------------
```
php composer.phar require "welltime/graylog" "*"
```

or add

```json
"welltime/graylog" : "*"
```

Usage
-----

```
<?php
return [
    ...
    'components' => [
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                'graylog' => [
                    'class' => 'welltime\graylog\GraylogTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['application'],
                    'host' => '127.0.0.1',
                    'port' => 12201,
                    'source' => 'hostname',
                    'addCategory' => true,                    
                    'addUserId' => true,
                    'addLoggerId' => true,                    
                    'addFile' => true,                    
                ],
            ],
        ],
    ],
    ...
];
```