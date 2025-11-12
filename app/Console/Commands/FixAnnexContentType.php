<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Annex;

class FixAnnexContentType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'annex:fix-content-type {--code=* : Filtrar por codigo_anexo} {--text=* : Forzar IDs a text} {--image=* : Forzar IDs a image}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ajusta el campo content_type de anexos (image|text). Útil para corregir anexos existentes.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $codes = (array) $this->option('code');
        $forceText = (array) $this->option('text');
        $forceImage = (array) $this->option('image');

        if (empty($codes) && empty($forceText) && empty($forceImage)) {
            $this->warn('No se recibieron filtros ni IDs. Use --code=, --text= o --image=.');
        }

        // Forzar por IDs
        if (!empty($forceText)) {
            Annex::whereIn('id', $forceText)->update(['content_type' => 'text']);
            $this->info('Actualizados a text: ' . implode(',', $forceText));
        }
        if (!empty($forceImage)) {
            Annex::whereIn('id', $forceImage)->update(['content_type' => 'image']);
            $this->info('Actualizados a image: ' . implode(',', $forceImage));
        }

        // Forzar por códigos
        if (!empty($codes)) {
            $affected = Annex::whereIn('codigo_anexo', $codes)->update(['content_type' => 'text']);
            $this->info("Actualizados por código a 'text': {$affected}");
        }

        // Mostrar resumen de conteo
        $counts = Annex::selectRaw("content_type, count(*) as c")->groupBy('content_type')->pluck('c','content_type');
        $this->line('Conteo actual: ' . json_encode($counts));
        return self::SUCCESS;
    }
}
