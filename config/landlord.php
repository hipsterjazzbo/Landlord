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
    | Query using table name
    |--------------------------------------------------------------------------
    |
    | When building the tenant query filter, it´s common to use the table name
    | with the column, using the dot notation. At some situations it´s better
    | to use only the column name.
    |
    | If you are using a database as MongoDB, set this option to false. Default 
    | is true.
    |
    */

    'query_with_table_name' => true,

];
