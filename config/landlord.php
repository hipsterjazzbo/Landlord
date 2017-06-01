<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Column
    |--------------------------------------------------------------------------
    |
    | Every model that needs to be scoped by tenant (company, user, etc.)
    | should have one or more columns that reference the `id` of a tenant in the tenant
    | table.
    |
    | For example, if you are scoping by company, you should have a
    | `companies` table that stores all your companies, and your other tables
    | should each have a `company_id` column that references an `id` on the
    | `companies` table.
    |
    */
    'default_tenant_columns' => ['company_id'],

    /*
    |--------------------------------------------------------------------------
    | Tenant Morph Relation
    |--------------------------------------------------------------------------
    |
    | For cases of 1 model belongs to N tenants, then one single table control
    | all relations between models and tenants. Using `Many To Many Polymorphic Relations`.
    | https://laravel.com/docs/5.3/eloquent-relationships#many-to-many-polymorphic-relations
    |
    | For example:
    | clients
    |   id - integer
    |   name - string
    |
    | users
    |   id - integer
    |   name - string
    |
    | tenants
    |   id - integer
    |
    | tenant_has
    |   tenant_id - integer
    |   tenant_has_model_id - integer
    |   tenant_has_model_type - string
    */
    'default_morph_relation' => [
        'tenant_model'              => 'App\Tenant',
        'tenant_relations_model'    => 'App\TenantRelations'
    ],

    'default_belongs_to_tenant_type' => \HipsterJazzbo\Landlord\TenantManager::BELONGS_TO_TENANT_TYPE_TO_ONE,
    'alias_id_column' => 'alias_id',
];
