# Landlord for Laravel

Single database multi-tenancy package for Laravel

[![Build Status](https://travis-ci.org/bissolli/laravel-landlord.svg?branch=master)](https://travis-ci.org/bissolli/laravel-landlord)
[![Latest Stable Version](https://poser.pugx.org/bissolli/laravel-landlord/v/stable)](https://packagist.org/packages/bissolli/laravel-landlord)
[![Total Downloads](https://poser.pugx.org/bissolli/laravel-landlord/downloads)](https://packagist.org/packages/bissolli/laravel-landlord)
[![License](https://poser.pugx.org/bissolli/laravel-landlord/license)](https://packagist.org/packages/bissolli/laravel-landlord)

> Based on [HipsterJazzbo/Landlord](https://github.com/HipsterJazzbo/Landlord).

> **Upgrading from Landlord v1?** Make sure to read the [change log](CHANGELOG.md) to see what needs updating.

## Installation

To get started, require this package:

```bash
composer require bissolli/laravel-landlord
```

### Laravel

Add the ServiceProvider in `config/app.php`:

```php
    'providers' => [
        ...
        Bissolli\Landlord\LandlordServiceProvider::class,
    ],
```

Register the Facade if you’d like:

```php
    'aliases' => [
        ...
        'Landlord'   => Bissolli\Landlord\Facades\Landlord::class,
    ],
```

You could also publish the config file:

```bash
php artisan vendor:publish --provider="Bissolli\Landlord\LandlordServiceProvider"
```

and set your `default_tenant_columns` setting, if you have an app-wide default. LandLord will use this setting to scope models that don’t have a `$tenantColumns` property set.

### Lumen

You'll need to set the service provider in your `bootstrap/app.php`:

```php
$app->register(Bissolli\Landlord\LandlordServiceProvider::class);
```

And make sure you've un-commented `$app->withEloquent()`.

## Usage

This package assumes that you have at least one column on all of your Tenant scoped tables that references which tenant each row belongs to.

For example, you might have a `companies` table, and a bunch of other tables that have a `company_id` column.

### Adding and Removing Tenants

> **IMPORTANT NOTE:** Landlord is stateless. This means that when you call `addTenant()`, it will only scope the *current request*.
> 
> Make sure that you are adding your tenants in such a way that it happens on every request, and before you need Models scoped, like in a middleware or as part of a stateless authentication method like OAuth.

You can tell Landlord to automatically scope by a given Tenant by calling `addTenant()`, either from the `Landlord` facade, or by injecting an instance of `TenantManager()`.

You can pass in either a tenant column and id:

```php
Landlord::addTenant('tenant_id', 1);
```

Or an instance of a Tenant model:

```php
$tenant = Tenant::find(1);

Landlord::addTenant($tenant);
```

If you pass a Model instance, Landlord will use Eloquent’s `getForeignKey()` method to decide the tenant column name.

You can add as many tenants as you need to, however Landlord will only allow **one** of each type of tenant at a time.

To remove a tenant and stop scoping by it, simply call `removeTenant()`:

```php
Landlord::removeTenant('tenant_id');

// Or you can again pass a Model instance:
$tenant = Tenant::find(1);

Landlord::removeTenant($tenant);
```

You can also check whether Landlord currently is scoping by a given tenant:

```php
// As you would expect by now, $tenant can be either a string column name or a Model instance
Landlord::hasTenant($tenant);
```

And if for some reason you need to, you can retrieve Landlord's tenants:

```php
// $tenants is a Laravel Collection object, in the format 'tenant_id' => 1
$tenants = Landlord::getTenants();
```

### Setting up your Models

To set up a model to be scoped automatically, simply use the `BelongsToTenants` trait:

```php

use Illuminate\Database\Eloquent\Model;
use Bissolli\Landlord\BelongsToTenants;

class ExampleModel extends Model
{
    use BelongsToTenants;
}
```

If you’d like to override the tenants that apply to a particular model, you can set the `$tenantColumns` property:

```php

use Illuminate\Database\Eloquent\Model;
use Bissolli\Landlord\BelongsToTenants;

class ExampleModel extends Model
{
    use BelongsToTenants;
    
    public $tenantColumns = ['tenant_id'];
}
```

### Creating new Tenant scoped Models

When you create a new instance of a Model which uses `BelongsToTenants`, Landlord will automatically add any applicable Tenant ids, if they are not already set:

```php
// 'tenant_id' will automatically be set by Landlord
$model = ExampleModel::create(['name' => 'whatever']);
```

### Querying Tenant scoped Models

After you've added tenants, all queries against a Model which uses `BelongsToTenant` will be scoped automatically:

```php
// This will only include Models belonging to the current tenant(s)
ExampleModel::all();

// This will fail with a ModelNotFoundForTenantException if it belongs to the wrong tenant
ExampleModel::find(2);
```

> **Note:** When you are developing a multi tenanted application, it can be confusing sometimes why you keep getting `ModelNotFound` exceptions for rows that DO exist, because they belong to the wrong tenant.
>
> Landlord will catch those exceptions, and re-throw them as `ModelNotFoundForTenantException`, to help you out :)

If you need to query across all tenants, you can use `allTenants()`:

```php
// Will include results from ALL tenants, just for this query
ExampleModel::allTenants()->get()
```

Under the hood, Landlord uses Laravel's [anonymous global scopes](https://laravel.com/docs/5.3/eloquent#global-scopes). This means that if you are scoping by multiple tenants simultaneously, and you want to exclude one of the for a single query, you can do so:

```php
// Will not scope by 'tenant_id', but will continue to scope by any other tenants that have been set
ExampleModel::withoutGlobalScope('tenant_id')->get();
```


## Contributing

If you find an issue, or have a better way to do something, feel free to open an issue or a pull request.
