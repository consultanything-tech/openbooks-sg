<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Models\Company;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Before installation, force session & cache to file driver so web installer never tries to connect to an unconfigured database
        if (!file_exists(storage_path('installed'))) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Double-entry ledger: keep journal entries in sync with invoices/bills
        // created through ANY path (web, AI assistant, API, recurring, seeders).
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
        \App\Models\Bill::observe(\App\Observers\BillObserver::class);
        // Cash legs: every Transaction posts its bank movement to the ledger.
        \App\Models\Transaction::observe(\App\Observers\TransactionObserver::class);

        // API rate limiting: 60 requests per minute per token
        RateLimiter::for('api', function (Request $request) {
            $token = $request->bearerToken() ?: $request->ip();

            return Limit::perMinute(60)->by($token);
        });

        if (file_exists(storage_path('installed'))) {
            try {
                View::composer('*', function ($view) {
                    $company = Company::first() ?? new Company([
                        'name' => 'OpenBooks SG',
                        'currency_code' => 'SGD',
                        'currency_symbol' => 'S$',
                        'financial_year' => 'January - December',
                        'financial_year_start' => '01-01'
                    ]);
                    $view->with('company', $company);
                    $view->with('currencySymbol', $company->currency_symbol ?? 'S$');
                });

                Blade::directive('money', function ($expression) {
                    return "<?php echo (\$currencySymbol ?? 'S$') . number_format($expression, 2); ?>";
                });

                // Role-based Blade directive: @canEdit ... @endcanEdit
                // Content inside is hidden from VIEWER role users
                Blade::if('canEdit', function () {
                    return \Illuminate\Support\Facades\Auth::check()
                        && strtoupper(\Illuminate\Support\Facades\Auth::user()->role) !== 'VIEWER';
                });
            } catch (\Throwable $e) {
                // Ignore during initial migrations
            }
        }
    }
}
