<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('accounts:anonymize-expired-students')]
#[Description('Anonymize student accounts whose 30-day deletion period has expired')]
class AnonymizeExpiredStudentAccounts extends Command
{
    public function handle(): int
    {
        $anonymized = 0;
        $cutoff = now()->subDays(30);

        User::query()
            ->where('role_id', User::ROLE_STUDENT)
            ->whereNotNull('account_deletion_requested_at')
            ->where('account_deletion_requested_at', '<=', $cutoff)
            ->whereNull('account_anonymized_at')
            ->chunkById(100, function ($users) use (&$anonymized): void {
                foreach ($users as $candidate) {
                    DB::transaction(function () use ($candidate, &$anonymized): void {
                        $user = User::query()->lockForUpdate()->find($candidate->id);

                        if (! $user
                            || ! $user->isStudent()
                            || ! $user->account_deletion_requested_at
                            || $user->account_anonymized_at
                            || $user->account_deletion_requested_at->gt(now()->subDays(30))) {
                            return;
                        }

                        $email = $user->email;
                        DB::table('password_reset_tokens')->where('email', $email)->delete();
                        DB::table('sessions')->where('user_id', $user->id)->delete();
                        $user->notifications()->delete();

                        $suffix = 0;
                        do {
                            $token = $user->id.($suffix > 0 ? '-'.$suffix : '');
                            $email = 'deleted-student-'.$token.'@deleted.invalid';
                            $username = 'deleted-student-'.$token;
                            $userCode = 'DELETED-'.$token;
                            $exists = User::query()
                                ->where('id', '!=', $user->id)
                                ->where(function ($query) use ($email, $username, $userCode): void {
                                    $query->where('email', $email)
                                        ->orWhere('username', $username)
                                        ->orWhere('student_id', $userCode)
                                        ->orWhere('user_code', $userCode);
                                })
                                ->exists();
                            $suffix++;
                        } while ($exists);

                        $user->forceFill([
                            'first_name' => 'Deleted',
                            'middle_name' => null,
                            'last_name' => 'Student',
                            'suffix' => null,
                            'username' => $username,
                            'email' => $email,
                            'email_verified_at' => null,
                            'password' => Str::random(64),
                            'remember_token' => null,
                            'student_id' => $userCode,
                            'user_code' => $userCode,
                            'phone' => null,
                            'location' => null,
                            'address' => null,
                            'avatar_url' => null,
                            'signature_image' => null,
                            'signature_text' => null,
                            'signature_font' => null,
                            'date_of_birth' => null,
                            'gender' => null,
                            'education' => null,
                            'school' => null,
                            'about' => null,
                            'bio' => null,
                            'skills_have' => null,
                            'skills_want' => null,
                            'career_goal' => null,
                            'main_goal' => null,
                            'language' => null,
                            'timezone' => null,
                            'pathway_id' => null,
                            'profile_completed' => false,
                            'admin_page_access' => null,
                            'notification_preferences' => null,
                            'notifications_read_at' => null,
                            'announcements_read_at' => null,
                            'role_label' => null,
                            'is_active' => false,
                            'account_anonymized_at' => now(),
                        ])->save();

                        $anonymized++;
                    });
                }
            });

        $this->info("Anonymized {$anonymized} expired student account(s).");

        return self::SUCCESS;
    }
}
