<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Binds the Laravel test case to the feature suite. Database refreshing stays
| per file (`uses(RefreshDatabase::class)`) so a test can opt out of it.
|
*/

uses(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| Search runs through `ilike`, which only PostgreSQL provides. The suite
| defaults to SQLite (see phpunit.xml), so the search tests skip unless the
| connection under test is PostgreSQL — point DB_CONNECTION at pgsql to
| exercise them for real.
|
*/

function onPostgres(): bool
{
    return DB::connection()->getDriverName() === 'pgsql';
}
