<?php

namespace App\Models;

use Database\Factories\SubmissionFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubmissionFile extends Model
{
    /** @use HasFactory<SubmissionFileFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Nada é atribuível em massa: caminho, mime e tamanho vêm do arquivo que
     * o servidor gravou, nunca de campo do formulário.
     */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'version' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Tamanho legível para a tela. O byte cru não diz nada a ninguém. */
    public function humanSize(): string
    {
        if ($this->size < 1024) {
            return "{$this->size} B";
        }

        if ($this->size < 1024 * 1024) {
            return round($this->size / 1024).' KB';
        }

        return round($this->size / (1024 * 1024), 1).' MB';
    }
}
