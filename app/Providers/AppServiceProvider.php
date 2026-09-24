<?php

namespace App\Providers;

use App\Models\Bill;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Observers\BillObserver;
use App\Observers\InvoiceObserver;
use App\Observers\TransactionObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Before installation, force session & cache to file driver so web installer never tries to connect to an unconfigured database
        if (! app()->environment('testing') && ! file_exists(storage_path('installed'))) {
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
        Invoice::observe(InvoiceObserver::class);
        Bill::observe(BillObserver::class);
        // Cash legs: every Transaction posts its bank movement to the ledger.
        Transaction::observe(TransactionObserver::class);

        // API rate limiting: 60 requests per minute per token
        RateLimiter::for('api', function (Request $request) {
            $token = $request->bearerToken() ?: $request->ip();

            return Limit::perMinute(60)->by($token);
        });

        // Shared company context for all views. Registered unconditionally so
        // views never break on fresh clones or in tests; the database lookup
        // is deferred to render time and falls back to safe defaults when the
        // schema is not available yet (e.g. during installation).
        View::composer('*', function ($view) {
            try {
                $company = Company::first();
            } catch (\Throwable) {
                $company = null;
            }

            $company ??= new Company([
                'name' => 'OpenBooks SG',
                'currency_code' => 'SGD',
                'currency_symbol' => 'S$',
                'financial_year' => 'January - December',
                'financial_year_start' => '01-01',
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
            return Auth::check()
                && strtoupper(Auth::user()->role) !== 'VIEWER';
        });
    }
}
