<?php

namespace App\Livewire\Admin;

use App\Models\BoardSubmission;
use App\Support\Scoring;
use Livewire\Component;

class Reviews extends Component
{
    public string $note = '';
    public ?int $previewSubmissionId = null;

    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    public function approve(int $submissionId): void
    {
        $this->authorizeAdmin();
        $submission = BoardSubmission::with('board.submissions')->findOrFail($submissionId);
        abort_unless($submission->status === BoardSubmission::PENDING, 409);
        abort_if($submission->lineCount() === 0, 409);
        // Squares may have changed since sending in: the reviewer judges the claim, not the live board.

        $award = $submission->award();
        $updated = BoardSubmission::whereKey($submissionId)->where('status', BoardSubmission::PENDING)->update([
            'status' => BoardSubmission::APPROVED,
            'reviewed_at' => now(),
            'review_note' => null,
            'points' => $award['points'],
            'base_points' => $award['base'],
            'multiplier' => $award['multiplier'],
        ]);
        abort_unless($updated === 1, 409);

        $this->dispatch('admin-saved', message: 'Bingo approvato · +'.Scoring::format($award['points']).' pt');
    }

    public function reject(int $submissionId): void
    {
        $this->authorizeAdmin();
        $this->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $updated = BoardSubmission::whereKey($submissionId)->where('status', BoardSubmission::PENDING)->update([
            'status' => BoardSubmission::REJECTED,
            'reviewed_at' => now(),
            'review_note' => $this->note ?: null,
        ]);
        abort_unless($updated === 1, 409);
        $this->note = '';
        $this->dispatch('admin-saved', message: 'Bingo rifiutato');
    }

    public function preview(int $submissionId): void
    {
        $this->authorizeAdmin();
        $this->previewSubmissionId = $submissionId;
    }

    public function closePreview(): void
    {
        $this->previewSubmissionId = null;
    }

    public function render()
    {
        $submissions = BoardSubmission::with(['board.user', 'board.cells', 'board.submissions'])
            ->where('status', BoardSubmission::PENDING)
            ->oldest('submitted_at')
            ->get();

        return view('livewire.admin.reviews', [
            'submissions' => $submissions,
            'previewSubmission' => $this->previewSubmissionId
                ? BoardSubmission::with(['board.user', 'board.cells', 'board.submissions'])->find($this->previewSubmissionId)
                : null,
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(strtolower((string) auth()->user()?->email) === strtolower((string) config('services.google.admin_email')), 403);
    }
}
