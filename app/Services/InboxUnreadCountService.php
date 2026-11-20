<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class InboxUnreadCountService
{
    public function countFor(User $user): int
    {
        if ($user->isAdmin()) {
            return Complaint::query()
                ->where(function (Builder $query): void {
                    $query->whereNull('admin_read_at')
                        ->orWhereRaw('COALESCE(last_reply_at, created_at) > admin_read_at');
                })
                ->count();
        }

        if (! $user->isStudent() && ! $user->isFaculty()) {
            return 0;
        }

        return Complaint::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $query): void {
                $query->whereNull('student_read_at')
                    ->orWhereRaw('COALESCE(last_reply_at, created_at) > student_read_at');
            })
            ->count();
    }
}
