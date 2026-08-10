<?php

namespace App\Models;

use Database\Factories\SubmissionVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionVersion extends Model
{
    /** @use HasFactory<SubmissionVersionFactory> */
    use HasFactory;

    /**
     * Nada é atribuível em massa: a versão é um retrato escrito pelo
     * servidor no momento do envio.
     */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'version' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
