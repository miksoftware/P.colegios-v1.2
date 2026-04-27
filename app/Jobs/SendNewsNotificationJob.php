<?php

namespace App\Jobs;

use App\Mail\NewsPublishedMail;
use App\Models\News;
use App\Models\School;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $newsId) {}

    public function handle(): void
    {
        $news = News::with('schools')->find($this->newsId);

        if (!$news || !$news->is_published) {
            return;
        }

        // Build the query for recipient users
        $query = User::query()->whereNotNull('email');

        if ($news->for_all_schools) {
            // All users that belong to at least one school
            $query->whereHas('schools');
        } else {
            // Only users from the selected schools
            $schoolIds = $news->schools->pluck('id');
            $query->whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds));
        }

        // Send one queued mail per recipient
        $query->each(function (User $user) use ($news) {
            Mail::to($user->email)->queue(new NewsPublishedMail($news));
        });
    }
}
