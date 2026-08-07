<?php

namespace App\Actions\Teams;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTeam
{
    /**
     * Cria a equipe e já vincula o criador como líder.
     *
     * As duas escritas vão na mesma transação: equipe sem líder é um estado
     * que nenhuma tela sabe exibir.
     *
     * @param  array{name: string, description?: string|null, track_id?: int|null}  $data
     */
    public function handle(Event $event, User $leader, array $data): Team
    {
        return DB::transaction(function () use ($event, $leader, $data) {
            $team = new Team($data);

            $team->event_id = $event->id;
            $team->leader_id = $leader->id;
            $team->slug = $this->uniqueSlug($event, $data['name']);
            $team->invite_code = $this->uniqueInviteCode();
            $team->status = TeamStatus::Draft;
            $team->save();

            $membership = new TeamMember;
            $membership->event_id = $event->id;
            $membership->team_id = $team->id;
            $membership->user_id = $leader->id;
            $membership->role = TeamMemberRole::Leader;
            $membership->status = TeamMemberStatus::Active;
            $membership->joined_at = now();
            $membership->save();

            return $team;
        });
    }

    private function uniqueSlug(Event $event, string $name): string
    {
        $base = Str::slug($name) ?: 'equipe';
        $slug = $base;
        $suffix = 2;

        while (Team::withTrashed()->forEvent($event)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Código de convite legível: sem 0/O e 1/I, que as pessoas confundem ao
     * digitar do que está escrito no quadro. Aleatório, nunca derivado do id.
     */
    private function uniqueInviteCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (Team::withTrashed()->where('invite_code', $code)->exists());

        return $code;
    }
}
