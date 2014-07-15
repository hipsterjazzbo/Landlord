<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Tenant Column
	|--------------------------------------------------------------------------
	|
	| Every model that needs to be scoped by tenant (company, user, etc.)
	| should have a column that references the `id` of tenant in the tenant
	| table.
	|
	| For example, if you are scoping by company, you should have a
	| `companies` table that stores all your companies, and your other tables
	| should each have a `company_id` column that references an `id` on the
	| `companies` table.
	|
	*/

	'tenant_column' => 'company_id',

];