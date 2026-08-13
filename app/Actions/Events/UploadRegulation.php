<?php

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadRegulation
{
    /**
     * Substitui o PDF do regulamento. O nome no disco é gerado pelo
     * Laravel; o nome original vira só metadado -- mesmo padrão de
     * AttachSubmissionFile (.claude/rules/security.md).
     */
    public function handle(Event $event, UploadedFile $upload): Event
    {
        return DB::transaction(function () use ($event, $upload) {
            $caminhoAntigo = $event->regulation_path;

            $event->regulation_path = $upload->store('regulamento', 'local');
            $event->regulation_original_name = $upload->getClientOriginalName();
            $event->regulation_updated_at = now();
            $event->save();

            if ($caminhoAntigo) {
                Storage::disk('local')->delete($caminhoAntigo);
            }

            return $event;
        });
    }
}
