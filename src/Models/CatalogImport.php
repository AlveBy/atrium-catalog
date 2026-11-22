<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Models;

use Atrium\Admin\Models\AdminUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * One import of products from a file: the file as SourceFile wrote it, how
 * its columns map to the product's fields, how far it has got and what went
 * wrong on which row.
 *
 * new → checking → checked → importing → done; failed when the file could not
 * be read to the end, cancelled when an administrator stopped it. A check
 * writes no products; the import itself may start without one (the console
 * does that unless told to check).
 *
 * @property int $id
 * @property int|null $admin_user_id
 * @property string $status
 * @property string $original_name
 * @property string $file
 * @property string $format
 * @property string $encoding
 * @property string $delimiter
 * @property array<int,string> $headers
 * @property array<int,string|null>|null $mapping
 * @property array<string,mixed>|null $options
 * @property int $total
 * @property int $position
 * @property int $processed
 * @property int $created
 * @property int $updated
 * @property int $skipped
 * @property int $failed
 * @property int $sections
 * @property array<int,array{row:int,skipped:bool,messages:array<int,string>}>|null $errors
 * @property string|null $message
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 */
class CatalogImport extends Model
{
    public const NEW = 'new';

    public const CHECKING = 'checking';

    public const CHECKED = 'checked';

    public const IMPORTING = 'importing';

    public const DONE = 'done';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    /** The field a product is found by again. */
    public const KEYS = ['article', 'code', 'external_id'];

    public const MODES = ['both', 'create', 'update'];

    public const DEFAULTS = [
        'key' => 'article',
        'mode' => 'both',
        'create_sections' => false,
        'separator' => '/',
    ];

    protected $table = 'catalog_imports';

    protected $fillable = [
        'admin_user_id', 'status', 'original_name', 'file', 'format', 'encoding', 'delimiter', 'headers',
        'mapping', 'options', 'total',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'mapping' => 'array',
            'options' => 'array',
            'errors' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AdminUser,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    /**
     * Where the files of imports are kept.
     */
    public static function directory(): string
    {
        return rtrim(config('atrium-catalog.import.directory'), '/');
    }

    public function path(): string
    {
        return self::directory().'/'.$this->file;
    }

    public function option(string $name): mixed
    {
        return $this->options[$name] ?? self::DEFAULTS[$name];
    }

    public function isRunning(): bool
    {
        return in_array($this->status, [self::CHECKING, self::IMPORTING], true);
    }

    /**
     * Whether the mapping may still be changed and the check run again.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [self::NEW, self::CHECKED, self::FAILED], true);
    }

    /**
     * Note that a check has met this value: true the first time.
     */
    public function remember(string $kind, string $value): bool
    {
        return DB::table('catalog_import_keys')->insertOrIgnore([
            'import_id' => $this->id,
            'kind' => $kind,
            'hash' => hash('sha256', mb_strtolower($value)),
        ]) === 1;
    }

    public function seen(string $kind, string $value): bool
    {
        return DB::table('catalog_import_keys')
            ->where('import_id', $this->id)
            ->where('kind', $kind)
            ->where('hash', hash('sha256', mb_strtolower($value)))
            ->exists();
    }

    public function forgetSeen(): void
    {
        DB::table('catalog_import_keys')->where('import_id', $this->id)->delete();
    }

    /**
     * Start the check or the import from the first row.
     */
    public function begin(string $status): void
    {
        $this->forceFill([
            'status' => $status,
            'position' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'sections' => 0,
            'errors' => [],
            'message' => null,
            'started_at' => now(),
            'finished_at' => null,
        ])->save();

        $this->forgetSeen();
    }

    /**
     * A row that was not imported, and why. Only the first rows are listed
     * (atrium-catalog.import.errors): a file broken all through would
     * otherwise fill the database with the same reason.
     *
     * @param  array<int,string>  $messages
     */
    public function note(int $row, bool $skipped, array $messages): void
    {
        $skipped ? $this->skipped++ : $this->failed++;
        $errors = $this->errors ?? [];

        if (count($errors) < config('atrium-catalog.import.errors')) {
            $errors[] = ['row' => $row, 'skipped' => $skipped, 'messages' => $messages];
            $this->errors = $errors;
        }
    }

    /**
     * Stop for good, with a reason the administrator can read.
     */
    public function stop(string $status, ?string $message = null): void
    {
        $this->forceFill(['status' => $status, 'message' => $message, 'finished_at' => now()])->save();
        $this->forgetSeen();
    }

    public function deleteFile(): void
    {
        if (is_file($this->path())) {
            unlink($this->path());
        }
    }
}
