<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFeatureRun;
use App\Models\Evenement;
use App\Models\EventBlueprint;
use App\Services\Ai\Events\EventBlueprintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventBlueprintController extends Controller
{
    public function index(Request $request): View
    {
        $blueprints = $request->user()->eventBlueprints()->latest('updated_at')->paginate(10);

        return view('admin.ai-blueprints.index', compact('blueprints'));
    }

    public function create(): View
    {
        return view('admin.ai-blueprints.create', ['proposal' => null, 'input' => []]);
    }

    public function generate(Request $request, EventBlueprintService $service): View|RedirectResponse
    {
        $input = $request->validate([
            'theme' => 'required|string|min:3|max:180',
            'objective' => 'required|string|min:10|max:600',
            'target_audience' => 'required|string|min:3|max:240',
            'type' => ['required', Rule::in(array_keys(Evenement::TYPES))],
            'duration' => 'required|string|max:60',
            'participants' => 'required|integer|min:1|max:500',
            'tone' => 'required|string|max:80',
        ]);
        $started = microtime(true);
        $run = AiFeatureRun::create([
            'user_id' => $request->user()->id,
            'feature' => 'event_blueprint',
            'audience' => 'admin',
            'module' => 'evenements',
            'provider' => 'openrouter',
            'model' => config('ai.model'),
            'locale' => app()->getLocale(),
            'status' => 'sent',
            'input_hash' => hash('sha256', json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        ]);
        try {
            $proposal = $service->generate($input);
            $run->update(['status' => 'completed', 'duration_ms' => (int) round((microtime(true) - $started) * 1000)]);
        } catch (\RuntimeException $exception) {
            $run->update([
                'status' => 'provider_error',
                'error_code' => 'provider_unavailable',
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);

            return back()->withInput()->with('error', $exception->getMessage());
        }

        return view('admin.ai-blueprints.create', compact('proposal', 'input'));
    }

    public function store(Request $request): RedirectResponse
    {
        $proposal = $this->validatedProposal($request);
        $blueprint = $request->user()->eventBlueprints()->create(['title' => $proposal['title'], 'status' => 'draft', 'proposal_data' => $proposal]);

        return redirect()->route('ai.admin.blueprints.edit', $blueprint)->with('success', 'Le brouillon du blueprint a été enregistré.');
    }

    public function edit(Request $request, EventBlueprint $blueprint): View
    {
        $this->ownedBy($request, $blueprint);

        return view('admin.ai-blueprints.edit', compact('blueprint'));
    }

    public function update(Request $request, EventBlueprint $blueprint): RedirectResponse
    {
        $this->ownedBy($request, $blueprint);
        $proposal = $this->validatedProposal($request);
        $blueprint->update(['title' => $proposal['title'], 'proposal_data' => $proposal]);

        return back()->with('success', 'Le brouillon du blueprint a été mis à jour.');
    }

    public function destroy(Request $request, EventBlueprint $blueprint): RedirectResponse
    {
        $this->ownedBy($request, $blueprint);
        $blueprint->delete();

        return redirect()->route('ai.admin.blueprints.index')->with('success', 'Le brouillon a été supprimé.');
    }

    public function prefill(Request $request, EventBlueprint $blueprint): RedirectResponse
    {
        $this->ownedBy($request, $blueprint);
        $proposal = $blueprint->proposal_data;
        $description = $proposal['description'];
        if (! empty($proposal['programme'])) {
            $description .= "\n\nProgramme proposé :\n- ".implode("\n- ", $proposal['programme']);
        }

        return redirect()->route('admin.evenements.create', array_filter([
            'titre' => $proposal['title'],
            'description' => $description,
            'type' => $proposal['type'],
            'lieu' => $proposal['suggested_venue'] ?? null,
            'ville' => $proposal['suggested_city'] ?? null,
            'capacite' => $proposal['recommended_capacity'] ?? null,
            // A planning budget is not an attendee price. Leave the form's price at free until reviewed.
            'prix' => 0,
            'blueprint' => $blueprint->id,
        ], static fn ($value) => $value !== null));
    }

    private function validatedProposal(Request $request): array
    {
        $listFields = ['objectives', 'programme', 'activities', 'equipment', 'organisation'];
        foreach ($listFields as $field) {
            $value = $request->input($field, []);
            if (is_array($value)) {
                $request->merge([$field => collect($value)
                    ->flatMap(fn ($item) => preg_split('/\R/u', (string) $item) ?: [])
                    ->map(fn ($item) => trim($item))
                    ->filter()
                    ->values()
                    ->all()]);
            }
        }

        return $request->validate([
            'title' => 'required|string|min:10|max:180',
            'description' => 'required|string|min:30|max:2500',
            'type' => ['required', Rule::in(array_keys(Evenement::TYPES))],
            'objectives' => 'required|array|min:1|max:6', 'objectives.*' => 'required|string|min:3|max:240',
            'target_audience' => 'required|string|min:3|max:300',
            'suggested_venue' => 'nullable|string|max:180', 'suggested_city' => 'nullable|string|max:100',
            'suggested_start' => 'nullable|string|max:60', 'suggested_end' => 'nullable|string|max:60',
            'recommended_capacity' => 'nullable|integer|min:1|max:500', 'estimated_budget' => 'nullable|numeric|min:0|max:1000000',
            'programme' => 'required|array|min:1|max:8', 'programme.*' => 'required|string|min:3|max:240',
            'activities' => 'required|array|max:8', 'activities.*' => 'required|string|min:3|max:240',
            'equipment' => 'required|array|max:12', 'equipment.*' => 'required|string|min:2|max:180',
            'organisation' => 'required|array|max:8', 'organisation.*' => 'required|string|min:3|max:240',
        ]);
    }

    private function ownedBy(Request $request, EventBlueprint $blueprint): void
    {
        abort_unless($blueprint->user_id === $request->user()->id, 404);
    }
}
