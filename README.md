Laravel Multi Tenant
====================

A general purpose multi-tenancy package for Laravel 4.2+. Accidentally derived from the work of @tonydew.

**Note:** This package is in Beta. Use at your own risk.

## Installation

First off, this package assumes that you have a column on all of your tenant-scoped tables that references which tenant each row belongs to.

For example, you might have a `companies` table, and all your other tables might have a `company_id` column (with a foreign key, right?).

To get started, require this package in your composer.json and run composer update:

```json
"AuraIsHere/LaravelMultiTenant": "dev-master"
```

After updating composer, add the ServiceProvider to the providers array in app/config/app.php

```php
'AuraIsHere\LaravelMultiTenant\LaravelMultiTenantServiceProvider',
```

You should also publish the config file:

```bash
php artisan config:publish aura-is-here/laravel-multi-tenant
```

and set up your `tenant_column` and `tenant_id` callback.

## Usage

Simply `use` the trait in all your models that you'd like to scope by tenant:

```php
<?php

use AuraIsHere\LaravelMultiTenant\ScopedByTenant;

class Model extends Eloquent {

    use ScopedByTenant;
}
```

Henceforth, all operations against that model will be scoped automatically.

```php
$models = Model::all(); // Only the Models with the correct tenant id

$model = Model::find(1); // Will fail if the Model with `id` 1 belongs to a differant tenant

$newModel = Model::create(); // Will have the tenant id added automatically
```

If you need to run queries across all tenants, you can do it easily:

```php
$allModels = Model::allTenants()->get(); //You can run any fluent query builder methods here, and they will not be scoped by tenant
```

When you are developing a multi tenanted application, it can be confusing sometimes why you keep getting `ModelNotFound` exceptions.

Laravel Multi Tenant will catch those exceptions, and re-throw them as `ModelNotFoundForTenant`, to help you out :)
