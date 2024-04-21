![Laravel Crud Generator](https://banners.beyondco.de/Laravel%20CRUD.png?theme=dark&packageManager=composer+require&packageName=ibex%2Fcrud-generator&pattern=architect&style=style_1&description=Laravel+CRUD+Generator&md=1&showWatermark=0&fontSize=100px&images=gift)


![Packagist](https://img.shields.io/badge/Packagist-v2-green.svg?style=flat-square)
![Licence](https://img.shields.io/badge/Licence-MIT-green.svg?style=flat-square)
![StyleCI](https://img.shields.io/badge/StyleCI-pass-green.svg?style=flat-square)


This Laravel CRUD Generator v2.x package provides and generates Controller, Model (with eloquent relations), and Views in **Bootstrap**/**Tailwind CSS** for the development of your applications with a single command. This new `v2.x` will have stack options like `bootstrap`, `tailwind`, `livewire`(Livewire views will be generated in **Tailwind** CSS), and `API` only.

- Will create **Model** with Eloquent relations
- Will create **Controller** with all resources
- Will create **API Controllers** with all requests
- Will create **Component** with all resources for Livewire
- Will create **views** in Bootstrap/Tailwind

This is the best crud generator for a blank Laravel project installation too. This will auto install the starter kit [laravel/breeze](https://github.com/laravel/breeze) or [laravel/ui](https://github.com/laravel/ui) (for bootstrap 5) for blank Laravel installation.

## Requirements
    Laravel >= 10.x
    PHP >= 8.1

## Installation
1 - Install
```
composer require ibex/crud-generator --dev
```
2- Publish the default package's config (optional)
```
php artisan vendor:publish --tag=crud
```


**For older Laravel(<10.x) versions please use [v1.x](https://github.com/awais-vteams/laravel-crud-generator/tree/v1.6)**
```
composer require ibex/crud-generator:1.6 --dev
```

## Usage
```
php artisan make:crud {table_name}

php artisan make:crud banks
```

Add a route in `web.php`
```
Route::resource('banks', BankController::class);
```

For `Livewire` add routes below
```
Route::get('/banks', \App\Livewire\Banks\Index::class)->name('banks.index');
Route::get('/banks/create', \App\Livewire\Banks\Create::class)->name('banks.create');
Route::get('/banks/show/{bank}', \App\Livewire\Banks\Show::class)->name('banks.show');
Route::get('/banks/update/{bank}', \App\Livewire\Banks\Edit::class)->name('banks.edit');
```

For `api` add routes below
```
Route::apiResource('banks', BankController::class);
```

Route name in plural slug case.

#### Options
- Tech Stack

  <img width="535" alt="image" src="https://github.com/awais-vteams/laravel-crud-generator/assets/10154558/c1e2e2a6-7fcd-4c4a-a393-56d8fe6eb231">
```
php artisan make:crud {table_name} {bootstrap,tailwind,livewire,api}

php artisan make:crud banks bootstrap  //This will create views in Bootstrap 5 using Blade
php artisan make:crud banks tailwind   //This will create views in Tailwind css using Blade
php artisan make:crud banks livewire   //This will create views in Tailwind css with Livewire components
php artisan make:crud banks api        //This will create API only controllers
```
 - Custom Route
```
php artisan make:crud {table_name} --route={route_name}
```


## Examples

*Model*
<img width="100%" alt="image" src="https://github.com/awais-vteams/laravel-crud-generator/assets/10154558/6b3c3dc1-a983-4893-a45c-94dbb8da50fc">


*Controller*
<img width="100%" alt="image" src="https://github.com/awais-vteams/laravel-crud-generator/assets/10154558/6a7948ed-90b7-46f9-a8b3-abb56fe0fb71">

*Livewire component*
<img width="100%" alt="image" src="https://github.com/awais-vteams/laravel-crud-generator/assets/10154558/e4c3bca5-f27a-41a8-a5bd-00c51b156235">

*API only controller*

<img width="500" alt="image" src="https://github.com/awais-vteams/laravel-crud-generator/assets/10154558/a42329a8-58e7-49ef-8e21-b6227555542b">


*Tailwind CSS*
<img width="100%" alt="image" src="https://github.com/awais-vteams/laravel-crud-generator/assets/10154558/b5ca686a-5a3b-4c60-849c-e757d16dc1a0">


*Bootstrap*
![Listing](https://i.imgur.com/UH5XGuw.png)


*Tailwind Form*

<img width="756" alt="image" src="https://github.com/awais-vteams/laravel-crud-generator/assets/10154558/b7d437ac-5d2b-4673-80ab-c2f7eb88e835">

*Bootstrap Form*
![Form](https://i.imgur.com/poRiZRO.png)


## Author

M Awais // [Email Me](mailto:asargodha@gmail.com)

[Buy me a Coffee](https://ko-fi.com/C0C8VT1M)

[![ko-fi](https://www.ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/C0C8VT1M)
