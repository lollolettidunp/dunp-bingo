<?php

namespace App\Livewire;

use App\Models\BoardSubmission;
use App\Models\User;
use App\Support\Scoring;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

class Leaderboard extends Component
{
    public const RANGES = [
        'week' => 'Settimana',
        'month' => 'Mese',
        'all' => 'Sempre',
    ];

    #[Url(except: 'all')]
    public string $range = 'all';

    public function mount(): void
    {
        if (! array_key_exists($this->range, self::RANGES)) {
            $this->range = 'all';
        }
    }

    public function setRange(string $range): void
    {
        abort_unless(array_key_exists($range, self::RANGES), 422);
        $this->range = $range;
    }

    public function render()
    {
        [$from, $to] = $this->period();

        $users = User::query()
            ->where('is_enabled', true)
            ->withSum(['submissions as points_sum' => fn (Builder $query) => $this->approvedIn($query, $from, $to)], 'points')
            ->withMax(['submissions as last_approved_at' => fn (Builder $query) => $this->approvedIn($query, $from, $to)], 'reviewed_at')
            ->get()
            ->map(function (User $user) {
                // The legacy carry-over is an all-time total: it would be a lie inside a week.
                $score = (float) $user->points_sum + ($this->range === 'all' ? $user->starting_score : 0);

                return tap($user)->setAttribute('score', $score)->setAttribute('score_label', Scoring::format($score));
            })
            ->sortBy([['score', 'desc'], ['name', 'asc']]);

        return view('livewire.leaderboard', [
            'users' => $users,
            'range' => $this->range,
            'ranges' => self::RANGES,
            'periodLabel' => $this->periodLabel($from, $to),
        ]);
    }

    /** @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable} */
    private function period(): array
    {
        $today = CarbonImmutable::today(config('app.timezone'));

        return match ($this->range) {
            'week' => [$today->startOfWeek(), $today->endOfWeek()],
            'month' => [$today->startOfMonth(), $today->endOfMonth()],
            default => [null, null],
        };
    }

    /** Approved claims only, filtered on the day they were played, not reviewed. */
    private function approvedIn(Builder $query, ?CarbonImmutable $from, ?CarbonImmutable $to): Builder
    {
        return $query
            ->where('board_submissions.status', BoardSubmission::APPROVED)
            ->when($from, fn (Builder $scoped) => $scoped->whereBetween('boards.played_on', [$from->toDateString(), $to->toDateString()]));
    }

    private function periodLabel(?CarbonImmutable $from, ?CarbonImmutable $to): string
    {
        return match ($this->range) {
            'week' => 'Dal '.$from->format('d/m').' al '.$to->format('d/m'),
            'month' => ucfirst($from->locale('it')->translatedFormat('F Y')),
            default => 'Tutti i punti di sempre',
        };
    }
}
