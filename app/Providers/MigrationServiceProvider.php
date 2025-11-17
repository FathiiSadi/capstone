<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {

        Blueprint::macro('common', function () {
            // Creation/Update Timestamps and IDs
            $this->timestamp('created_at')->nullable();
            $this->unsignedBigInteger('created_by')->nullable();
            $this->timestamp('updated_at')->nullable();
            $this->unsignedBigInteger('updated_by')->nullable();

            // Soft Deletes (optional)
            $this->timestamp('deleted_at')->nullable();
            $this->unsignedBigInteger('deleted_by')->nullable();

            // Tracking Data
            $this->ipAddress()->nullable();
            $this->string('user_agent')->nullable();
        });

        Blueprint::macro('dropCommon', function () {
            $this->dropColumn([
                'created_at', 'created_by',
                'updated_at', 'updated_by',
                'deleted_at', 'deleted_by',
                'ip_address', 'user_agent'
            ]);
        });
    }
}
