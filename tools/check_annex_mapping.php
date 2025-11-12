<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Annex;
use App\Models\CompanyAnnexSubmission;
use App\Models\Program;
use App\Models\Company;

// Usage: php tools/check_annex_mapping.php [company_id] [program_id]
$companyId = isset($argv[1]) ? (int)$argv[1] : 1;
$programId = isset($argv[2]) ? (int)$argv[2] : 1;

$program = Program::find($programId);
$company = Company::find($companyId);
if (!$program || !$company) {
    fwrite(STDERR, "Program or Company not found.\n");
    exit(1);
}

$annexIds = DB::table('program_annexes')->where('program_id', $programId)->pluck('annex_id')->toArray();
$annexes = Annex::whereIn('id', $annexIds)->get();

// Helper similar al controlador
$derivePlaceholder = function (?Annex $annex) {
    if (!$annex) return null;
    if (!empty($annex->placeholder)) return $annex->placeholder;
    if (!empty($annex->codigo_anexo) && preg_match('/(\d+)/', $annex->codigo_anexo, $m)) {
        return 'Anexo ' . $m[1];
    }
    if (!empty($annex->nombre) && preg_match('/(\d+)/', $annex->nombre, $m)) {
        return 'Anexo ' . $m[1];
    }
    return null;
};

$existing = CompanyAnnexSubmission::where('company_id', $companyId)
    ->where('program_id', $programId)
    ->whereIn('status', ['Pendiente', 'Aprobado'])
    ->get()
    ->groupBy('annex_id');

$placeholderToAnnex = [];

foreach ($annexes as $ax) {
    $ph = $derivePlaceholder($ax);
    $group = $existing->get($ax->id) ?? collect();
    $sorted = $group->sortByDesc(function ($s) { return $s->updated_at ?? $s->created_at ?? now(); });

    $textSub = $sorted->first(function ($s) { return !empty($s->content_text); });
    $imageSub = $sorted->first(function ($s) {
        if (empty($s->file_path) || empty($s->mime_type)) return false;
        if (!str_starts_with($s->mime_type, 'image/')) return false;
        try { $p = Storage::disk('public')->path($s->file_path); } catch (Throwable $t) { $p = storage_path('app/public/' . ltrim($s->file_path, '/')); }
        return file_exists($p);
    });

    $collision = (isset($placeholderToAnnex[$ph]) && $placeholderToAnnex[$ph] !== $ax->id) ? 'YES' : 'NO';
    $placeholderToAnnex[$ph] = $ax->id;

    echo "Annex: {$ax->id} | Nombre: {$ax->nombre} | Codigo: {$ax->codigo_anexo} | Placeholder: " . ($ph ?? 'NULL') . " | PlaceholderCollision: {$collision}\n";
    if ($textSub) {
        echo "  -> TEXT submission_id={$textSub->id} updated_at=" . ($textSub->updated_at ?? $textSub->created_at) . " size=" . strlen((string)$textSub->content_text) . " bytes\n";
    }
    if ($imageSub) {
        try { $p = Storage::disk('public')->path($imageSub->file_path); } catch (Throwable $t) { $p = storage_path('app/public/' . ltrim($imageSub->file_path, '/')); }
        echo "  -> IMAGE submission_id={$imageSub->id} file='{$imageSub->file_name}' mime={$imageSub->mime_type} path={$p}\n";
    }
    if (!$textSub && !$imageSub) {
        echo "  -> NO USABLE CONTENT\n";
    }
}

