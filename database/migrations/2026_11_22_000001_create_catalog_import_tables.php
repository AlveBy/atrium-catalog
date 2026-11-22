<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Importing products from CSV and Excel files: the external ID a supplier's
 * file finds a product by (Bitrix calls it XML_ID), one row per import with
 * its mapping, progress and report, the keys a check has seen (a check writes
 * no products, so it remembers them to tell a repeat in the file from a new
 * product), and the images already downloaded, so the next import of the same
 * file does not fetch them again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('external_id')->nullable()->index();
        });

        Schema::create('catalog_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->index();
            $table->string('status', 16)->default('new')->index();
            $table->string('original_name');
            $table->string('file');
            $table->string('format', 8);
            $table->string('encoding', 16);
            $table->string('delimiter', 4);
            $table->json('headers');
            $table->json('mapping')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedBigInteger('position')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('sections')->default(0);
            $table->json('errors')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_import_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_id');
            $table->string('kind', 8);
            $table->string('hash', 64);
            $table->unique(['import_id', 'kind', 'hash']);
        });

        Schema::create('catalog_import_media', function (Blueprint $table): void {
            $table->id();
            $table->string('hash', 64)->unique();
            $table->text('url');
            $table->string('path', 2048);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_import_media');
        Schema::dropIfExists('catalog_import_keys');
        Schema::dropIfExists('catalog_imports');

        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropIndex(['external_id']);
            $table->dropColumn('external_id');
        });
    }
};
