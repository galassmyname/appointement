<?php

namespace App\Jobs;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendReminderEmail implements ShouldQueue
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
        $email = $this->rendezVous->client->email;

        Mail::to($email)->send(new \App\Mail\ReminderEmail($this->rendezVous));
    }
}
