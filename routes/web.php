<?php

use ilsawn\LaravelIlsawn\Livewire\TranslationsTable;
use Illuminate\Support\Facades\Route;

Route::get('/', TranslationsTable::class)->name('ilsawn.index');
