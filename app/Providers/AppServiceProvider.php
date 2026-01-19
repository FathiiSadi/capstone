<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        // Auto-assign default department to new Instructors
        \App\Models\Instructor::created(function ($instructor) {
            $defaultDept = \App\Models\Department::firstOrCreate(
                ['name' => 'Computer Science'],
                ['code' => 'CS']
            );
            $instructor->departments()->syncWithoutDetaching([$defaultDept->id]);
        });
    }
}
