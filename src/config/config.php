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

	/*
	|--------------------------------------------------------------------------
	| Tenant ID
	|--------------------------------------------------------------------------
	|
	| You'll also need to specify how to get the tenant id. This could be
	| done in any number of ways. You could get the tenant id from the logged
	| in user:
	|
	|     Auth::user()->company_id
	|
	| or maybe you've got it stored in your Session somewhere:
	|
	|     Session::get('company_id')
	|
	*/

	'tenant_id' => function ()
	{
		return Auth::user()->company_id;
	}

];