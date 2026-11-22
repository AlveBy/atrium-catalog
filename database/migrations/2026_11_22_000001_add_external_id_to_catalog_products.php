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
 * The external ID a supplier's file finds a product by when it is imported
 * (Bitrix calls it XML_ID). A shop that took it in the catalog's import
 * migration of the same day already has it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('catalog_products', 'external_id')) {
            return;
        }

        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('external_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropIndex(['external_id']);
            $table->dropColumn('external_id');
        });
    }
};
