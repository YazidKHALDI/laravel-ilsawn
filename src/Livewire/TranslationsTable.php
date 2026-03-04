<?php

namespace ilsawn\LaravelIlsawn\Livewire;

use ilsawn\LaravelIlsawn\LaravelIlsawn;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read array<int, array<string, string>> $rows
 * @property-read string[] $locales
 */
#[Layout('ilsawn::layout')]
class TranslationsTable extends Component
{
    public string $search = '';

    public ?string $editingKey = null;

    /** @var array<string, string> */
    public array $editingValues = [];

    public ?string $message = null;

    public bool $needsGenerate = false;

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

        if ($this->search === '') {
            return $all;
        }

        $needle = strtolower($this->search);

        return array_values(array_filter(
            $all,
            fn (array $row) => str_contains(strtolower($row['key']), $needle)
        ));
    }

    /**
     * @return string[]
     */
    #[Computed]
    public function locales(): array
    {
        return (array) config('ilsawn.locales', ['en']);
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

        $this->editingKey    = $key;
        $this->editingValues = array_diff_key($row, ['key' => '']);
    }

    public function cancelEdit(): void
    {
        $this->editingKey    = null;
        $this->editingValues = [];
    }

    public function saveRow(): void
    {
        $ilsawn = app(LaravelIlsawn::class);
        $rows   = $ilsawn->loadCsv();

        $rows = array_map(function (array $row): array {
            if ($row['key'] === $this->editingKey) {
                return array_merge($row, $this->editingValues);
            }

            return $row;
        }, $rows);

        $ilsawn->saveCsv($rows);

        $this->editingKey    = null;
        $this->editingValues = [];
        $this->message       = 'Row saved.';
        $this->needsGenerate = true;
        unset($this->rows); // bust computed cache
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function generate(): void
    {
        Artisan::call('ilsawn:generate');
        $this->message       = 'JSON files generated.';
        $this->needsGenerate = false;
    }

    public function scan(): void
    {
        Artisan::call('ilsawn:generate', ['--scan' => true]);
        $this->message = 'Scan complete — new keys added to CSV.';
        unset($this->rows);
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        /** @phpstan-ignore argument.type */
        return view('ilsawn::livewire.translations-table');
    }
}
