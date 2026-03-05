<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AduanMasyarakat;
use App\Models\PengajuanProposal;
use App\Models\PengajuanSurat;
use App\Observers\AduanMasyarakatObserver;
use App\Observers\PengajuanProposalObserver;
use App\Observers\PengajuanSuratObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        require_once app_path('Helpers/GlobalHelper.php');
        require_once app_path('Helpers/dateid_helper.php');

        // Register Push Notification Observers
        AduanMasyarakat::observe(AduanMasyarakatObserver::class);
        PengajuanProposal::observe(PengajuanProposalObserver::class);
        PengajuanSurat::observe(PengajuanSuratObserver::class);
    }
}
