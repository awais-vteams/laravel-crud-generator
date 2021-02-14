![Laravel Crud Generator with Column Filters](https://banners.beyondco.de/Laravel%20Crud%20Generator%20with%20Column%20Filters!.png?theme=dark&packageManager=composer+require&packageName=dscheff%2Fcrud-generator&pattern=graphPaper&style=style_1&description=Column+Filters+in+your+Index+View+&md=1&showWatermark=0&fontSize=75px&images=filter)

![Packagist](https://img.shields.io/badge/Packagist-v1.3.7-green.svg?style=flat-square)
![Licence](https://img.shields.io/badge/Licence-MIT-green.svg?style=flat-square)
![StyleCI](https://img.shields.io/badge/StyleCI-pass-green.svg?style=flat-square)

This package is a fork of https://packagist.org/packages/ibex/crud-generator, and adds column filters to the index view,
as well as a handful of other tweaks.

This Laravel Generator package provides and generates
Controller, Model (with eloquent relations) and Views 
in **Bootstrap** for your development of your applications with single command.

@todo - Add related models to the filtering

@todo - Adding subview generation for views that integrate mutiple models, with 
Ajax/modal/inline forms

- Will create **Model** with Eloquent relations
- Will create **Controller** with all resources
- Will create **views** in Bootstrap 4
- Will add **Column Filters** to your index action/view 

## Requirements
    Laravel >= 5.5
    PHP >= 7.1

## Installation
1 - Install
```
composer require dscheff/crud-generator --dev
```
2- Publish the default package's config
```
php artisan vendor:publish --tag=crud
php artisan vendor:publish --tag=public --force
```

Note that you will need to add the following to your app layout, just before your closing body tag:

```
@stack('scripts-body')
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

- Custom Display Name for Views
```
php artisan make:crud {table_name} --title={displayed_model_name}
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

Daniel Scheff, on the back of the great work of M Awais (https://packagist.org/packages/ibex/crud-generator)