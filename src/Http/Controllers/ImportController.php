<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Http\Controllers;

use Atrium\Catalog\Import\ImportFailed;
use Atrium\Catalog\Import\ImportRunner;
use Atrium\Catalog\Import\ProcessImport;
use Atrium\Catalog\Import\Rows;
use Atrium\Catalog\Import\Settings;
use Atrium\Catalog\Import\SourceFile;
use Atrium\Catalog\Import\Targets;
use Atrium\Catalog\Models\CatalogImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The import screens: upload a file, map its columns, check, import, read
 * the report. The progress page asks for the state every second or two; with
 * no queue worker it moves the import a step forward each time it asks.
 */
final class ImportController
{
    private const PREVIEW = 5;

    public function __construct(private readonly Targets $targets) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Catalog/Import/Index', [
            'imports' => CatalogImport::query()->with('user:id,name')->latest('id')->limit(20)->get()
                ->map(fn (CatalogImport $import): array => $this->state($import)),
            'maxSize' => config('atrium-catalog.import.max_size'),
            'strings' => trans('atrium-catalog::import'),
        ]);
    }

    public function store(Request $request, SourceFile $source): RedirectResponse
    {
        $request->validate(
            ['file' => ['required', 'file', 'max:'.config('atrium-catalog.import.max_size')]],
            [],
            ['file' => __('atrium-catalog::import.upload.file')],
        );

        File::ensureDirectoryExists(CatalogImport::directory());
        $file = Str::uuid().'.csv';
        $target = CatalogImport::directory().'/'.$file;

        try {
            $read = $source->normalize($request->file('file')->getRealPath(), $target);
        } catch (ImportFailed $failed) {
            File::delete($target);

            throw ValidationException::withMessages(['file' => $failed->getMessage()]);
        }

        $previous = CatalogImport::query()->whereNotNull('mapping')->latest('id')->limit(20)->get()
            ->first(static fn (CatalogImport $import): bool => $import->headers === $read['headers']);

        $import = CatalogImport::query()->create([
            ...$read,
            'admin_user_id' => $request->user(config('atrium.auth.guard', 'admin'))->id,
            'status' => CatalogImport::NEW,
            'original_name' => mb_substr($request->file('file')->getClientOriginalName(), 0, 255),
            'file' => $file,
            'mapping' => $previous->mapping ?? $this->targets->guess($read['headers']),
            'options' => $previous?->options,
        ]);

        return redirect()->route('admin.catalog-import.show', $import);
    }

    public function show(CatalogImport $import): Response
    {
        return Inertia::render('Admin/Catalog/Import/Show', [
            'import' => $this->state($import),
            'preview' => is_file($import->path()) ? Rows::preview($import->path(), self::PREVIEW) : [],
            'targets' => $this->targets->options(),
            'keys' => array_map(fn (string $key): array => ['key' => $key, 'label' => $this->targets->get($key)['label'] ?? $key], CatalogImport::KEYS),
            'modes' => CatalogImport::MODES,
            'strings' => trans('atrium-catalog::import'),
        ]);
    }

    /**
     * Save the mapping and the options and start the check.
     */
    public function check(Request $request, CatalogImport $import): RedirectResponse
    {
        abort_unless($import->isOpen(), 409);

        $settings = Settings::validate($request->all(), count($import->headers), $this->targets);
        $import->forceFill($settings)->save();
        $import->begin(CatalogImport::CHECKING);
        ProcessImport::start($import);

        return redirect()->route('admin.catalog-import.show', $import);
    }

    /**
     * Import what the check has seen.
     */
    public function run(CatalogImport $import): RedirectResponse
    {
        abort_unless($import->status === CatalogImport::CHECKED, 409);

        $import->begin(CatalogImport::IMPORTING);
        ProcessImport::start($import);

        return redirect()->route('admin.catalog-import.show', $import);
    }

    /**
     * Where the import stands; without a queue worker, a step forward first.
     */
    public function step(CatalogImport $import, ImportRunner $runner): JsonResponse
    {
        if (! ProcessImport::queued()) {
            $runner->step($import, config('atrium-catalog.import.request_seconds'));
        }

        return response()->json($this->state($import->refresh()));
    }

    public function cancel(CatalogImport $import): RedirectResponse
    {
        // Wait for a step that is under way: it would otherwise finish the import after this.
        Cache::lock('atrium-catalog-import:'.$import->id, 10)->block(config('atrium-catalog.import.seconds') + 5, static function () use ($import): void {
            $import->refresh();

            if ($import->status !== CatalogImport::DONE) {
                $import->stop(CatalogImport::CANCELLED);
                $import->deleteFile();
            }
        });

        return redirect()->route('admin.catalog-import.show', $import);
    }

    /**
     * @return array<string,mixed>
     */
    private function state(CatalogImport $import): array
    {
        return [
            ...$import->only([
                'id', 'status', 'original_name', 'format', 'encoding', 'delimiter', 'headers', 'mapping', 'total',
                'processed', 'created', 'updated', 'skipped', 'failed', 'sections', 'errors', 'message',
            ]),
            'options' => [...CatalogImport::DEFAULTS, ...($import->options ?? [])],
            'user' => $import->user?->name,
            'created_at' => $import->created_at->toIso8601String(),
            'started_at' => $import->started_at?->toIso8601String(),
            'finished_at' => $import->finished_at?->toIso8601String(),
        ];
    }
}
