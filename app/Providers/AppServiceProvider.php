<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyCertificate;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Menu;
use App\Policies\CatalogPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CompanyCertificatePolicy;
use App\Policies\CompanyPolicy;
use App\Policies\DocumentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        Paginator::useBootstrapFive();

        Gate::policy(Menu::class, CatalogPolicy::class);
        Gate::policy(DocumentFolder::class, CatalogPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(CompanyCertificate::class, CompanyCertificatePolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);

        // Sidebar standards are needed on every authenticated page.
        View::composer(['layouts.sidebar', 'profile.edit'], function ($view) {
            $view->with('sidebarMenus', Menu::ordered()->get());
        });

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
