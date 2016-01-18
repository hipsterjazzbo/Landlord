# Landlord for Laravel 5.2

![Landlord for Laravel 5.2](readme-header.jpg)

![Build Status](https://travis-ci.org/HipsterJazzbo/Landlord.svg?branch=master)

A single database multi-tenancy package for Laravel 5.2+. (Formerly https://github.com/AuraEQ/laravel-multi-tenant)

## Installation

To get started, require this package:

```bash
composer require hipsterjazzbo/landlord
```

Add the ServiceProvider to the providers array in `config/app.php`:

```php
HipsterJazzbo\Landlord\LandlordServiceProvider::class,
```

You could also publish the config file:

```bash
php artisan vendor:publish --provider="HipsterJazzbo\Landlord\LandlordServiceProvider"
```

and set up your `tenant_column` setting, if you have an app-wide default.

## Usage

First off, this package assumes that you have at least one column on all of your tenant-scoped tables that references which tenant each row belongs to.

For example, you might have a `companies` table, and all your other tables might have a `company_id` column (with a foreign key, right?).

Next, you'll have to call `Landlord::addTenant($tenantColumn, $tenantId)`. It doesn't matter where, **as long as it happens on every request**. This is important; if you only set the tenant in your login method for example, that won't run for subsequent requests and queries will no longer be scoped. You almost certainly will want to do this in a middleware.

Some examples of good places to call `Landlord::addTenant($tenantColumn, $tenantId)` might be:

- In a global Middleware
- In an oauth system, wherever you're checking the token on each request
- In the constructor of a base controller

Once you've got that all worked out, simply `use` the trait in all your models that you'd like to scope by tenant:

```php
<?php

use Illuminate\Database\Eloquent\Model;
use HipsterJazzbo\Landlord\Traits\BelongsToTenant;

class ExampleModel extends Model
{
    use BelongsToTenant;
}
```

Henceforth, all operations against that model will be scoped automatically.

You can also set a `$tenantColumns` property on the model to override the tenants applicable to that model.

```php
$models = Model::all(); // Only the Models with the correct tenant id

$model = Model::find(1); // Will fail if the Model with `id` 1 belongs to a different tenant

$newModel = Model::create(); // Will have the tenant id added automatically
```

If you need to run queries across all tenants, you can do it easily:

```php
$allModels = Model::allTenants()->get(); //You can run any fluent query builder methods here, and they will not be scoped by tenant
```

When you are developing a multi tenanted application, it can be confusing sometimes why you keep getting `ModelNotFound` exceptions.

Laravel Multi Tenant will catch those exceptions, and re-throw them as `TenantModelNotFoundException`, to help you out :)

## Contributing

Please! This is not yet a complete solution, but there's no point in all of us re-inventing this wheel over and over. If you find an issue, or have a better way to do something, open an issue or a pull request.
