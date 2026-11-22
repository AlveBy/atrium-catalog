<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use Atrium\Admin\Fields\Field;
use Atrium\Admin\Http\Controllers\AdminController;
use Atrium\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Saves an imported row exactly as the product form and the API save one:
 * the same validation, the same custom fields, SEO and languages, the same
 * hooks after the save. A row is a form that a file filled in.
 *
 * A row that updates a product is partial, like the API's PATCH: only the
 * columns of the file are validated and written, the rest stays as it was.
 */
final class ProductWriter extends AdminController
{
    public function __construct()
    {
        $this->model = Product::class;
    }

    /**
     * The fields of the product form, the SEO and custom ones included.
     *
     * @return array<int,Field>
     */
    public function fields(): array
    {
        return $this->allAdminFields();
    }

    /**
     * The names of the fields that hold a value per content language.
     *
     * @return array<int,string>
     */
    public function translatable(): array
    {
        return $this->translatableAttributes();
    }

    /**
     * What would stop the row from saving; nothing is written.
     *
     * @param  array<string,mixed>  $data
     * @return array<int,string>
     */
    public function check(array $data, bool $exists, ?Product $product): array
    {
        $context = $exists ? 'edit' : 'create';
        $request = Request::create('/', 'POST', $data);
        $this->normalizeTranslatableInput($request);

        return Validator::make($request->all(), $this->resolveRules($request, $context, $product), [], $this->validationAttributes($context))
            ->errors()
            ->all();
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function write(array $data, ?Product $product): Model
    {
        return $this->saveItem(Request::create('/', 'POST', $data), $product === null ? 'create' : 'edit', $product ?? new Product);
    }

    protected function resolveRules(Request $request, string $context, ?Model $record): array
    {
        $rules = parent::resolveRules($request, $context, $record);

        if ($context === 'edit') {
            $rules = array_filter($rules, static fn ($rule, $name): bool => $request->has($name), ARRAY_FILTER_USE_BOTH);
        }

        return $rules;
    }
}
