<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderEmail;
use App\Models\RendezVous;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendScheduledReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Envoyer les rappels pour les rendez-vous à venir';

    public function handle()
    {
        $rendezVous = RendezVous::where('statut', 'valide')
            ->where('jour', Carbon::today())
            ->get();

        foreach ($rendezVous as $rdv) {
            $heureDebut = Carbon::parse($rdv->heureDebut, $rdv->jour);
            if ($heureDebut->diffInMinutes(now()) === 60) {
                dispatch(new SendReminderEmail($rdv));
            }
        }
    }
}
