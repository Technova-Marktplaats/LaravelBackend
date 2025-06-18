<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\Watchlist;
use App\Models\Notification;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckVerlopenReservaties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservaties:check-verlopen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Controleert op verlopen reservaties en stuurt notificaties naar watchlist gebruikers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Controleren op verlopen reservaties...');
        Log::info('CheckVerlopenReservaties command gestart');
        
        // Vind alle bevestigde reservaties die zijn verlopen
        $verlopenReservaties = Reservation::with(['item'])
            ->where('status', 'confirmed')
            ->where('end_date', '<', Carbon::now())
            ->get();
        
        if ($verlopenReservaties->isEmpty()) {
            $this->info('Geen verlopen reservaties gevonden.');
            Log::info('Geen verlopen reservaties gevonden');
            return;
        }
        
        $this->info("Gevonden {$verlopenReservaties->count()} verlopen reservatie(s).");
        
        $verzondenNotificaties = 0;
        $itemsGeupdatet = 0;
        
        foreach ($verlopenReservaties as $reservatie) {
            // Zet item weer op beschikbaar
            $item = $reservatie->item;
            if (!$item->available) {
                $item->update(['available' => true]);
                $itemsGeupdatet++;
                $this->info("Item '{$item->title}' weer beschikbaar gesteld");
                Log::info("Item {$item->id} ({$item->title}) weer beschikbaar gesteld na verlopen reservatie");
            }
            
            // Vind alle gebruikers die dit item op hun watchlist hebben
            $watchlistGebruikers = Watchlist::with(['user'])
                ->where('item_id', $reservatie->item_id)
                ->get();
            
            if ($watchlistGebruikers->isNotEmpty()) {
                $this->info("Item '{$reservatie->item->title}' staat op {$watchlistGebruikers->count()} watchlist(s).");
                
                foreach ($watchlistGebruikers as $watchlistItem) {
                    // Controleer of er al een notificatie is verstuurd voor deze combinatie
                    $bestaandeNotificatie = Notification::where('user_id', $watchlistItem->user_id)
                        ->where('item_id', $reservatie->item_id)
                        ->where('message', 'LIKE', '%weer beschikbaar%')
                        ->where('created_at', '>=', $reservatie->end_date)
                        ->exists();
                    
                    if (!$bestaandeNotificatie) {
                        // Maak notificatie aan
                        Notification::create([
                            'user_id' => $watchlistItem->user_id,
                            'item_id' => $reservatie->item_id,
                            'message' => "Het item '{$reservatie->item->title}' is weer beschikbaar voor uitlening!",
                            'is_read' => false
                        ]);
                        
                        $verzondenNotificaties++;
                        $this->info("Notificatie verstuurd naar gebruiker {$watchlistItem->user->name}");
                    } else {
                        $this->info("Notificatie al verstuurd naar gebruiker {$watchlistItem->user->name}");
                    }
                }
            }
            
            // Update reservatie status naar 'expired' (we moeten eerst de migratie aanpassen)
            // Voor nu laten we de status op 'confirmed' maar kunnen we later een 'expired' status toevoegen
        }
        
        $this->info("Totaal {$verzondenNotificaties} notificatie(s) verstuurd.");
        $this->info("Totaal {$itemsGeupdatet} item(s) weer beschikbaar gesteld.");
        Log::info("CheckVerlopenReservaties command voltooid - {$verzondenNotificaties} notificaties verstuurd, {$itemsGeupdatet} items weer beschikbaar gesteld");
        $this->info('Controle voltooid!');
    }
}
