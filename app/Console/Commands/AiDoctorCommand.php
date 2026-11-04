<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class AiDoctorCommand extends Command
{
    protected $signature = 'ai:doctor {--model= : Modèle à vérifier au lieu de celui configuré}';

    protected $description = 'Diagnostique la configuration OpenRouter et la disponibilité des modèles gratuits';

    public function handle(): int
    {
        $key = (string) config('ai.key');
        $model = (string) ($this->option('model') ?: config('ai.model'));
        $fallback = (string) config('ai.fallback_model');
        $failures = 0;

        $this->components->twoColumnDetail('Clé API', $key !== '' ? 'renseignée' : '<fg=red>absente</>');
        $this->components->twoColumnDetail('Modèle principal', $model !== '' ? $model : '<fg=red>vide</>');
        $this->components->twoColumnDetail('Modèle de secours', $fallback !== '' ? $fallback : 'aucun');
        $this->components->twoColumnDetail('Timeout / budget', (int) config('ai.timeout').' s / '.(int) config('ai.total_budget').' s');
        $this->components->twoColumnDetail('JSON mode', config('ai.json_mode') ? 'activé' : 'désactivé');

        if ($key === '') {
            $failures++;
        }

        try {
            $catalog = Http::acceptJson()->connectTimeout(10)->timeout(30)->get('https://openrouter.ai/api/v1/models');
        } catch (ConnectionException $exception) {
            $this->components->error('Catalogue OpenRouter injoignable : '.($exception->getMessage() !== '' ? 'connexion impossible' : 'erreur réseau'));

            return self::FAILURE;
        }

        if (! $catalog->successful()) {
            $this->components->error('Catalogue OpenRouter indisponible (HTTP '.$catalog->status().').');

            return self::FAILURE;
        }

        $ids = collect($catalog->json('data', []))->pluck('id')->filter()->all();
        $freeIds = array_values(array_filter($ids, static fn (string $id): bool => str_ends_with($id, ':free') || $id === 'openrouter/free'));
        $this->components->twoColumnDetail('Modèles publiés', count($ids).' dont '.count($freeIds).' gratuits');

        foreach (['principal' => $model, 'secours' => $fallback] as $label => $candidate) {
            if ($candidate === '') {
                continue;
            }
            if (! in_array($candidate, $ids, true)) {
                $this->components->warn("Modèle $label introuvable dans le catalogue : $candidate");
                $failures++;
            } else {
                $this->components->twoColumnDetail("Modèle $label disponible", '<fg=green>oui</>');
            }
        }

        if ($key !== '') {
            $this->checkKey($key);
            if (! $this->probe($model, $key)) {
                $failures++;
            }
        }

        if ($failures > 0) {
            $this->newLine();
            $this->components->error('La configuration IA nécessite une correction (voir ci-dessus).');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Configuration IA cohérente : les assistants peuvent être utilisés.');

        return self::SUCCESS;
    }

    private function checkKey(string $key): void
    {
        try {
            $response = Http::withToken($key)->acceptJson()->connectTimeout(10)->timeout(30)
                ->get('https://openrouter.ai/api/v1/key');
        } catch (Throwable) {
            $this->components->warn('État de la clé non vérifiable (réseau).');

            return;
        }

        if (! $response->successful()) {
            $this->components->warn('Clé refusée par OpenRouter (HTTP '.$response->status().').');

            return;
        }

        $data = (array) $response->json('data', []);
        $limit = $data['limit'] ?? null;
        $usage = $data['usage'] ?? null;
        $remaining = $data['limit_remaining'] ?? null;
        $this->components->twoColumnDetail('Clé OpenRouter', '<fg=green>acceptée</>');
        if ($limit === null) {
            $this->components->twoColumnDetail('Quota', 'illimité (modèles payants) ou non plafonné');
        } else {
            $this->components->twoColumnDetail('Quota', 'usage '.$usage.' / limite '.$limit.', restant '.($remaining ?? 'n/a'));
        }
        if (isset($data['rate_limit']) && is_array($data['rate_limit'])) {
            $rate = $data['rate_limit'];
            $this->components->twoColumnDetail('Limite de débit', ($rate['requests'] ?? '?').' requêtes / '.($rate['interval'] ?? '?'));
        }
    }

    private function probe(string $model, string $key): bool
    {
        if ($model === '') {
            return false;
        }

        try {
            $response = Http::withToken($key)->acceptJson()
                ->connectTimeout((int) config('ai.connect_timeout'))
                ->timeout(min(45, (int) config('ai.timeout')))
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'max_tokens' => 32,
                    'messages' => [['role' => 'user', 'content' => 'Réponds uniquement {"ok":true}.']],
                ]);
        } catch (ConnectionException) {
            $this->components->warn('Test de génération : délai réseau dépassé.');

            return false;
        }

        if ($response->successful()) {
            $this->components->twoColumnDetail('Test de génération', '<fg=green>HTTP 200</>');

            return true;
        }

        $status = $response->status();
        $this->components->warn('Test de génération : HTTP '.$status.' — '.$this->explain($status));

        return false;
    }

    private function explain(int $status): string
    {
        return match ($status) {
            400 => 'demande refusée (modèle ou paramètre non supporté)',
            401 => 'clé invalide',
            402 => 'crédit épuisé',
            403 => 'modèle interdit pour ce compte',
            404 => 'modèle inexistant',
            429 => 'quota ou débit dépassé (attendre le renouvellement)',
            default => $status >= 500 ? 'panne temporaire du fournisseur' : 'erreur inattendue',
        };
    }
}
