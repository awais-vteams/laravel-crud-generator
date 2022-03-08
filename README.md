![Laravel Crud Generator](https://banners.beyondco.de/Laravel%20CRUD.png?theme=dark&packageManager=composer+require&packageName=ibex%2Fcrud-generator&pattern=architect&style=style_1&description=Laravel+CRUD+Generator&md=1&showWatermark=0&fontSize=100px&images=gift)


![Packagist](https://img.shields.io/badge/Packagist-v1.3.2-green.svg?style=flat-square)
![Licence](https://img.shields.io/badge/Licence-MIT-green.svg?style=flat-square)
![StyleCI](https://img.shields.io/badge/StyleCI-pass-green.svg?style=flat-square)


This Laravel Generator package provides and generate Controller, Model (with eloquent relations) and Views in **Bootstrap** for your development of your applications with single command.

- Will create **Model** with Eloquent relations
- Will create **Controller** with all resources
- Will create **views** in Bootstrap

## Requirements
    Laravel >= 5.5
    PHP >= 7.1

## Installation
1 - Install
```
composer require ibex/crud-generator --dev
```
2- Publish the default package's config
```
php artisan vendor:publish --tag=crud
```

## Usage
```
php artisan make:crud {table_name}

php artisan make:crud banks
```

Add a route in `web.php`
```
Route::resource('banks', 'BankController');
```
Route name in plural slug case.

#### Options
 - Custom Route
```
php artisan make:crud {table_name} --route={route_name}
```

## Example

*Model*
![Model](https://i.imgur.com/zTSoYvJ.png)


*Controller*
![Controller](https://i.imgur.com/G1ytmcL.png)


*Listing*
![Listing](https://i.imgur.com/UH5XGuw.png)


*Form*
![Form](https://i.imgur.com/poRiZRO.png)


## Author

M Awais // [Email Me](mailto:asargodha@gmail.com)

[Buy me a Coffee](https://ko-fi.com/C0C8VT1M)

[![ko-fi](https://www.ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/C0C8VT1M)
