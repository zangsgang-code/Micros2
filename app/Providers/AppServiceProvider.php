<?php

namespace App\Providers;

use App\Models\Course;
use App\Services\InboxUnreadCountService;
use App\Services\SidebarAttentionService;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Send mail through Brevo's HTTP API (port 443), because Railway
        // blocks outbound SMTP on most plans.
        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn('brevo+api', 'default', config('services.brevo.key'))
            );
        });

        View::composer('components.navbar', function ($view): void {
            $view->with(
                'publicCourseCategories',
                Course::where('is_published', true)
                    ->where('is_approved', true)
                    ->whereNotNull('category')
                    ->distinct()
                    ->orderBy('category')
                    ->pluck('category')
                    ->all()
            );
        });

        // Authenticated pages show a notification bell and a student/faculty
        // inbox envelope, so every view needs both unread counts.
        //
        // NOTE: these are cached on the REQUEST, not in a `static` inside the
        // closure. The closure is registered once at boot and reused, so a
        // static would freeze the count for the life of the worker process —
        // invisible under artisan serve / PHP-FPM, but under Octane, Swoole or
        // RoadRunner every later visitor would see the first request's number,
        // possibly another user's.
        View::composer('*', function ($view) {
            $request = request();

            if (! $request->attributes->has('upskill.unread')) {
                $counts = [
                    'notifications' => 0,
                    'inbox' => 0,
                    'admin_course_reviews' => 0,
                    'admin_completion_reviews' => 0,
                    'faculty_learner_progress' => false,
                    'faculty_my_courses' => false,
                ];

                if (Auth::check()) {
                    $user = Auth::user();

                    $counts['notifications'] = app(UserNotificationService::class)->unreadCount($user);
                    $counts['inbox'] = app(InboxUnreadCountService::class)->countFor($user);
                    $counts = array_merge($counts, app(SidebarAttentionService::class)->forUser($user));
                }

                $request->attributes->set('upskill.unread', $counts);
            }

            $counts = $request->attributes->get('upskill.unread');

            $view->with('unreadNotificationCount', $counts['notifications']);
            $view->with('unreadInboxCount', $counts['inbox']);
            $view->with('adminPendingCourseReviewCount', $counts['admin_course_reviews']);
            $view->with('adminPendingCompletionReviewCount', $counts['admin_completion_reviews']);
            $view->with('facultyLearnerProgressNeedsAttention', $counts['faculty_learner_progress']);
            $view->with('facultyMyCoursesNeedsAttention', $counts['faculty_my_courses']);
        });
    }
}
