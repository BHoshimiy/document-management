<?php

declare(strict_types=1);
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
