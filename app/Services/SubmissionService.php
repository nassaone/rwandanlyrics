<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\Song;
use App\Models\Submission;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

class SubmissionService
{
    /**
     * Approve a submission. A new song becomes a DRAFT with rights "pending": an editor must still
     * set rights to Authorized and publish it. A correction adds a new lyrics revision (old text is kept).
     */
    public function approve(Submission $submission, User $reviewer, ?int $artistId, ?string $notes): ?Song
    {
        return DB::transaction(function () use ($submission, $reviewer, $artistId, $notes) {
            $song = null;

            if ($submission->type === SubmissionType::NewSong) {
                $song = Song::forceCreate([
                    'title' => $submission->title,
                    'status' => 'draft',
                    'rights_status' => 'pending',
                    'verification_status' => 'unverified',
                    'language_id' => $submission->language_id,
                    'rights_holder' => $submission->rights_holder,
                    'rights_source' => $submission->rights_source,
                    'rights_notes' => 'Created from submission #'.$submission->id,
                ]);
                $song->artists()->attach($artistId, ['role' => 'primary']);
                $this->addRevision($song, $submission->lyrics, $submission->user_id, 'Submission #'.$submission->id);
                $submission->result_song_id = $song->id;
            } elseif ($submission->song) {
                $this->addRevision($submission->song, $submission->lyrics, $submission->user_id, 'Correction, submission #'.$submission->id);
                $song = $submission->song;
                $submission->result_song_id = $song->id;
            }

            $this->finish($submission, $reviewer, SubmissionStatus::Approved, $notes);
            AuditLogger::record('submission.approved', $submission, ['song_id' => $song?->id]);

            return $song;
        });
    }

    public function reject(Submission $submission, User $reviewer, string $notes): void
    {
        $this->finish($submission, $reviewer, SubmissionStatus::Rejected, $notes);
        AuditLogger::record('submission.rejected', $submission);
    }

    private function finish(Submission $submission, User $reviewer, SubmissionStatus $status, ?string $notes): void
    {
        $submission->forceFill([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ])->save();
    }

    private function addRevision(Song $song, string $input, ?int $userId, string $note): void
    {
        $body = rtrim(str_replace(["\r\n", "\r"], "\n", $input));
        $current = $song->lyrics()->where('is_current', true)->first();

        if ($current && $current->body === $body) {
            return;
        }

        $current?->update(['is_current' => false]);
        $revision = ((int) $song->lyrics()->max('revision_number')) + 1;
        $song->lyrics()->create([
            'body' => $body, 'revision_number' => $revision, 'is_current' => true,
            'note' => $note, 'created_by' => $userId,
        ]);
    }
}
