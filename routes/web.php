<?php

use Illuminate\Support\Facades\Route;
use ilsawn\LaravelIlsawn\Livewire\TranslationsTable;

Route::get('/', TranslationsTable::class)->name('ilsawn.index');
