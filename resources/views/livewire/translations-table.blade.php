<div class="min-h-screen p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Ilsawn Translations</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage your CSV translations</p>
        </div>
        <div class="flex items-center gap-2">
            <button
                wire:click="scan"
                wire:loading.attr="disabled"
                class="px-4 py-2 text-sm font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="scan">Scan for new keys</span>
                <span wire:loading wire:target="scan">Scanning…</span>
            </button>
            <div class="relative">
                <button
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="generate">Generate JSON</span>
                    <span wire:loading wire:target="generate">Generating…</span>
                </button>
                @if($needsGenerate)
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full"></span>
                @endif
            </div>
        </div>
    </div>

    {{-- Flash message --}}
    @if($message)
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition.opacity
            class="mb-4 px-4 py-2 text-sm text-green-800 bg-green-100 border border-green-200 rounded-lg"
        >
            {{ $message }}
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search translation keys…"
            class="w-full max-w-sm px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 w-64">Key</th>
                    @foreach($this->locales as $locale)
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">{{ $locale }}</th>
                    @endforeach
                    <th class="px-4 py-3 w-24"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($this->rows as $row)
                    @if($editingKey === $row['key'])
                        {{-- Editing row --}}
                        <tr class="bg-indigo-50">
                            <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $row['key'] }}</td>
                            @foreach($this->locales as $locale)
                                <td class="px-4 py-2">
                                    <input
                                        wire:model="editingValues.{{ $locale }}"
                                        type="text"
                                        class="w-full px-2 py-1 text-sm border border-indigo-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                </td>
                            @endforeach
                            <td class="px-4 py-2">
                                <div class="flex gap-1">
                                    <button
                                        wire:click="saveRow"
                                        class="px-2 py-1 text-xs font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700"
                                    >Save</button>
                                    <button
                                        wire:click="cancelEdit"
                                        class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded hover:bg-gray-200"
                                    >Cancel</button>
                                </div>
                            </td>
                        </tr>
                    @else
                        {{-- Display row --}}
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700 break-all">{{ $row['key'] }}</td>
                            @foreach($this->locales as $locale)
                                @php $value = $row[$locale] ?? ''; @endphp
                                <td class="px-4 py-3 {{ $value === '' ? 'bg-amber-50' : '' }}">
                                    @if($value !== '')
                                        <span class="text-gray-800">{{ $value }}</span>
                                    @else
                                        <span class="text-amber-400 text-xs font-medium">missing</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-4 py-3">
                                <button
                                    wire:click="startEdit('{{ $row['key'] }}')"
                                    class="px-2 py-1 text-xs font-medium text-gray-600 border border-gray-200 rounded hover:bg-gray-100"
                                >Edit</button>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ count($this->locales) + 2 }}" class="px-4 py-8 text-center text-gray-400 text-sm">
                            @if($search)
                                No keys match <strong>{{ $search }}</strong>
                            @else
                                No translations yet. Run <code class="bg-gray-100 px-1 rounded">ilsawn:generate --scan</code> to get started.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Row count --}}
    @if(count($this->rows) > 0)
        <p class="mt-3 text-xs text-gray-400">
            {{ count($this->rows) }} {{ Str::plural('key', count($this->rows)) }}
            @if($search) matching <em>{{ $search }}</em> @endif
        </p>
    @endif

</div>
