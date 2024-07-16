
# Laravel CRUD Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ztech243/artisan-crud.svg?style=flat-square)](https://packagist.org/packages/ztech243/artisan-crud)
[![Total Downloads](https://img.shields.io/packagist/dt/ztech243/artisan-crud.svg?style=flat-square)](https://packagist.org/packages/ztech243/artisan-crud)

A Laravel package to generate CRUD operations quickly and easily.

## Installation

You can install the package via composer:

```bash
composer require ztech243/laravel-crud
```

Optionally, you can publish the stubs for customization:

```bash
php artisan vendor:publish --provider="Ztech243\CrudGenerator\Providers\CrudGeneratorServiceProvider"
```

## Usage

This package provides two commands to generate CRUD operations:

### API CRUD

Generate CRUD operations for APIs:

```bash
php artisan make:crud-api {ModelName} [--migration] [--model]
```

- `{ModelName}`: The name of the model.
- `--migration` (optional): Include this flag to generate a migration.
- `--model` (optional): Include this flag to generate a model.

Example:

```bash
php artisan make:crud-api User --migration --model
```

### Blade CRUD

Generate CRUD operations with Blade views:

```bash
php artisan make:crud-blade {ModelName} [--migration] [--model]
```

- `{ModelName}`: The name of the model.
- `--migration` (optional): Include this flag to generate a migration.
- `--model` (optional): Include this flag to generate a model.

Example:

```bash
php artisan make:crud-blade User --migration --model
```

## Customization

You can customize the stubs to fit your application's needs. After publishing the stubs, you can find them in the `resources/stubs/vendor/crud-generator` directory.

### Stubs

- `api-controller.stub`
- `api-request.stub`
- `api-resource.stub`
- `blade-controller.stub`
- `blade-request.stub`
- `blade-view.stub`
- `model.stub`
- `migration.stub`

## Example

### Generating CRUD API for a User Model

1. Generate the CRUD API with a migration and model:

    ```bash
    php artisan make:crud-api User --migration --model
    ```

2. Check the generated files:

    - `app/Http/Controllers/Api/UserController.php`
    - `app/Http/Requests/StoreUserRequest.php`
    - `app/Http/Requests/UpdateUserRequest.php`
    - `app/Http/Resources/UserResource.php`
    - `app/Models/User.php`
    - `database/migrations/xxxx_xx_xx_xxxxxx_create_users_table.php`

3. Routes are added automatically to `routes/api.php`.

### Generating CRUD with Blade Views for a User Model

1. Generate the CRUD with Blade views, migration, and model:

    ```bash
    php artisan make:crud-blade User --migration --model
    ```

2. Check the generated files:

    - `app/Http/Controllers/UserController.php`
    - `app/Http/Requests/StoreUserRequest.php`
    - `app/Http/Requests/UpdateUserRequest.php`
    - `resources/views/users/index.blade.php`
    - `resources/views/users/create.blade.php`
    - `resources/views/users/edit.blade.php`
    - `resources/views/users/show.blade.php`
    - `app/Models/User.php`
    - `database/migrations/xxxx_xx_xx_xxxxxx_create_users_table.php`

3. Routes are added automatically to `routes/web.php`.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss what you would like to change.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
