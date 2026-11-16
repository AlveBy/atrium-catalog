<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shaped like an iblock the Bitrix migrator moves: sections, standard
 * fields, properties. brand_id and the stores pivot point at other modules
 * and have no foreign keys: the catalog works without them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->index();
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(500);
            $table->string('picture')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable()->index();
            $table->boolean('active')->default(true);
            $table->dateTime('active_from')->nullable();
            $table->dateTime('active_to')->nullable();
            $table->integer('sort')->default(500);
            $table->string('preview_picture')->nullable();
            $table->text('preview_text')->nullable();
            $table->string('preview_text_type', 4)->default('html');
            $table->string('detail_picture')->nullable();
            $table->text('detail_text')->nullable();
            $table->string('detail_text_type', 4)->default('html');
            $table->string('article')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->foreignId('brand_id')->nullable()->index();
            $table->string('label')->nullable();
            $table->json('colors')->nullable();
            $table->date('released_on')->nullable();
            $table->string('in_stock', 1)->nullable();
            $table->json('features')->nullable();
            $table->text('package')->nullable();
            $table->text('specs')->nullable();
            $table->json('photos')->nullable();
            $table->string('manual')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_product_related', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->index();
            $table->foreignId('related_id')->index();
            $table->integer('sort')->default(0);
        });

        Schema::create('catalog_product_store', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->index();
            $table->foreignId('store_id')->index();
        });
    }

    public function down(): void
    {
        foreach (['catalog_product_store', 'catalog_product_related', 'catalog_products', 'catalog_sections'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
