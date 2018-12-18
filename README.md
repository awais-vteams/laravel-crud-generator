# Laravel Crud Generator
This Laravel Generator package provides and generate Controller, Model (with eloquent relations) and Views in Bootstrap for your development of your applications with single command.

- Will create **Model** with Eloquent relations
- Will create **Controller** with all resources
- Will create **views** in Bootstrap

## Requirements
    Laravel >= 5.6
    PHP >= 7.1

## Installation
1 - Install
```
composer require ibex/crud-generator --dev
```
2- Publish the default package's config
```
php artisan vendor:publish --provider="Ibex\CrudGenerator\CrudServiceProvider"
```

## Usage
```
php artisan make:crud {table_name}

php artisan make:crud users
```
## Author

M Awais // [Email Me](mailto:asargodha@gmail.com)
