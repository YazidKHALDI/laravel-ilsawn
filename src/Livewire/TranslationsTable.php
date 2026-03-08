<?php

namespace ilsawn\LaravelIlsawn\Livewire;

use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use ilsawn\LaravelIlsawn\LaravelIlsawn;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read array<int, array<string, string>> $rows
 * @property-read string[] $locales
 * @property-read bool $aiAvailable
 */
#[Layout('ilsawn::layout')]
class TranslationsTable extends Component
{
    public string $search = '';

    public bool $onlyMissing = false;

    public ?string $editingKey = null;

    /** @var array<string, string> */
    public array $editingValues = [];

    public bool $needsGenerate = false;

    public int $pendingScanCount = 0;

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    /**
     * @return array<int, array<string, string>>
     */
    #[Computed]
    public function rows(): array
    {
        $all = app(LaravelIlsawn::class)->loadCsv();

        $locales = $this->locales;

        if ($this->search !== '') {
            $needle = strtolower($this->search);
            $all = array_values(array_filter(
                $all,
                fn (array $row) => str_contains(strtolower($row['key']), $needle)
            ));
        }

        if ($this->onlyMissing) {
            $all = array_values(array_filter(
                $all,
                fn (array $row) => collect($locales)->some(fn (string $locale) => ($row[$locale] ?? '') === '')
            ));
        }

        return $all;
    }

    /**
     * @return string[]
     */
    #[Computed]
    public function locales(): array
    {
        return (array) config('ilsawn.locales', ['en']);
    }

    #[Computed]
    public function aiAvailable(): bool
    {
        return class_exists('Laravel\Ai\Enums\Lab');
    }

    // -------------------------------------------------------------------------
    // Inline editing
    // -------------------------------------------------------------------------

    public function startEdit(string $key): void
    {
        $row = collect($this->rows)->firstWhere('key', $key);

        if ($row === null) {
            return;
        }

        $this->editingKey = $key;
        $this->editingValues = array_diff_key($row, ['key' => '']);
    }

    public function cancelEdit(): void
    {
        $this->editingKey = null;
        $this->editingValues = [];
    }

    public function saveRow(): void
    {
        $ilsawn = app(LaravelIlsawn::class);
        $rows = $ilsawn->loadCsv();

        $rows = array_map(function (array $row): array {
            if ($row['key'] === $this->editingKey) {
                return array_merge($row, $this->editingValues);
            }

            return $row;
        }, $rows);

        $ilsawn->saveCsv($rows);

        $this->editingKey = null;
        $this->editingValues = [];
        $this->needsGenerate = true;
        unset($this->rows); // bust computed cache
        $this->flash('Row saved.');
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function generate(): void
    {
        Artisan::call('ilsawn:generate');
        $this->needsGenerate = false;
        $this->flash('JSON files generated.');
    }

    public function copyKeyAsTranslation(string $locale): void
    {
        if ($this->editingKey === null) {
            return;
        }

        $this->editingValues[$locale] = $this->editingKey;
    }

    public function autoTranslate(string $locale): void
    {
        if (! $this->aiAvailable) {
            return;
        }

        $sourceLocale = (string) config('ilsawn.default_locale', 'en');
        $sourceText = $this->editingValues[$sourceLocale] ?? '';

        if (empty($sourceText)) {
            $this->flash('Add a source translation first.', 'warning');

            return;
        }

        try {
            /** @phpstan-ignore function.notFound */
            $result = \Laravel\Ai\agent(
                instructions: 'You are a professional translator. Translate the given text accurately. Return only the translated text, nothing else — no quotes, no explanation.',
            )->prompt("Translate to locale \"{$locale}\": {$sourceText}");

            $this->editingValues[$locale] = (string) $result;
        } catch (\Throwable $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function checkPendingKeys(): void
    {
        $ilsawn = app(LaravelIlsawn::class);
        $csvData = $ilsawn->loadCsv();

        ['missing' => $missing] = $ilsawn->scanForNewKeys($csvData);

        $this->pendingScanCount = count($missing);
    }

    public function scan(): void
    {
        Artisan::call('ilsawn:generate', ['--scan' => true]);
        unset($this->rows);
        $this->pendingScanCount = 0;
        $this->flash('Scan complete — new keys added to CSV.');
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        /** @phpstan-ignore argument.type */
        return view('ilsawn::livewire.translations-table');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function flash(string $message, string $type = 'success'): void
    {
        $this->dispatch('flash', message: $message, type: $type);
    }
}
