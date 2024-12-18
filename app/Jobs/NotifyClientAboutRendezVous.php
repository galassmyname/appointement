<?php

namespace App\Jobs;

use App\Models\RendezVous;
use App\Notifications\RendezVousNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyClientAboutRendezVous implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rendezVous;

    /**
     * Create a new job instance.
     *
     * @param RendezVous $rendezVous
     */
    public function __construct(RendezVous $rendezVous)
    {
        $this->rendezVous = $rendezVous;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Envoyer une notification par email
        $this->rendezVous->client->notify(
            new RendezVousNotification($this->rendezVous)
        );
    }
}
