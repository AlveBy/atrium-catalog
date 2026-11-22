<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\DateField;
use Atrium\Admin\Fields\DateTimeField;
use Atrium\Admin\Fields\Field;
use Atrium\Admin\Fields\GalleryField;
use Atrium\Admin\Fields\ImageField;
use Atrium\Admin\Fields\MultiSelectField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\RepeatableField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Catalog\Models\CatalogImport;
use Atrium\Catalog\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * One row of the file: finds the product by the import's key, turns the cells
 * into what the form would send and saves it through ProductWriter — or, in a
 * check, only asks whether it would save.
 *
 * An empty cell leaves the field as it is. A row the mode does not allow
 * (a new product in "update only", a known one in "create only") is skipped;
 * a row that cannot be saved fails, with the reasons. Neither stops the rest.
 *
 * A check counts the way the import will: a key met again further down the
 * file updates the product its first row creates.
 */
final class RowImporter
{
    private readonly bool $check;

    private readonly ProductWriter $writer;

    private readonly Sections $sections;

    private readonly Images $images;

    /** @var array<string,array<int,array{value:mixed,label:mixed}>> field => options */
    private array $options = [];

    public function __construct(private readonly CatalogImport $import, private readonly Targets $targets, Hosts $hosts)
    {
        $this->check = $import->status === CatalogImport::CHECKING;
        $this->writer = new ProductWriter;
        $this->sections = new Sections($import, $this->check);
        $this->images = new Images($hosts, $this->check);
    }

    /**
     * @param  array<int,string>  $cells
     */
    public function import(int $number, array $cells): void
    {
        try {
            $this->row($number, $cells);
        } catch (RowProblem $problem) {
            $this->import->note($number, false, [$problem->getMessage()]);
        } catch (ValidationException $invalid) {
            $this->import->note($number, false, Arr::flatten($invalid->errors()));
        } catch (QueryException $failure) {
            report($failure);
            $this->import->note($number, false, [__('atrium-catalog::import.problems.database')]);
        }

        $this->import->processed++;
    }

    /**
     * @param  array<int,string>  $cells
     */
    private function row(int $number, array $cells): void
    {
        $values = [];

        foreach ($this->import->mapping as $index => $key) {
            $cell = $cells[$index] ?? '';

            if ($key !== null && $cell !== '') {
                $values[$key] = $cell;
            }
        }

        $key = $this->import->option('key');
        $value = $values[$key] ?? throw RowProblem::because('no_key', ['field' => $this->targets->get($key)['label']]);
        $mode = $this->import->option('mode');

        $product = Product::query()->where($key, $value)->orderBy('id')->first();
        $exists = $product !== null || ($this->check && $this->import->seen('product', $value));

        if ($exists && $mode === 'create') {
            $this->import->note($number, true, [__('atrium-catalog::import.problems.exists', ['key' => $value])]);

            return;
        }

        if (! $exists && $mode === 'update') {
            $this->import->note($number, true, [__('atrium-catalog::import.problems.missing', ['key' => $value])]);

            return;
        }

        $data = $this->data($values);

        if ($this->check) {
            $errors = $this->writer->check($data, $exists, $product);

            if ($errors !== []) {
                $this->import->note($number, false, $errors);

                return;
            }

            $exists ? $this->import->updated++ : $this->import->created++;
            $this->import->remember('product', $value);

            return;
        }

        $this->writer->write($data, $product);
        $exists ? $this->import->updated++ : $this->import->created++;
    }

    /**
     * What the form would send for these cells.
     *
     * @param  array<string,string>  $values  target => cell
     * @return array<string,mixed>
     */
    private function data(array $values): array
    {
        $data = [];

        foreach ($values as $key => $cell) {
            if ($key === Targets::SECTION) {
                $data['section_id'] = $this->sections->find($cell);

                continue;
            }

            $target = $this->targets->get($key);
            $field = $target['field'];
            $value = $this->value($field, $cell);

            if ($target['locale'] === null) {
                $data[$field->getName()] = $value;
            } else {
                $data[$field->getName()][$target['locale']] = $value;
            }
        }

        return $data;
    }

    private function value(Field $field, string $cell): mixed
    {
        return match (true) {
            $field instanceof CheckboxField => Cells::bool($cell) ?? throw RowProblem::because('not_bool', ['field' => $field->getLabel(), 'value' => $cell]),
            $field instanceof NumberField => Cells::number($cell),
            $field instanceof DateTimeField => Cells::date($cell, true),
            $field instanceof DateField => Cells::date($cell, false),
            $field instanceof MultiSelectField => array_map(fn (string $item): mixed => $this->option($field, $item), Cells::items($cell)),
            $field instanceof SelectField, $field instanceof BelongsToField => $this->option($field, $cell),
            $field instanceof RepeatableField => Cells::items($cell),
            $field instanceof GalleryField => array_map($this->images->one(...), Cells::addresses($cell)),
            $field instanceof ImageField => $this->images->one($cell),
            default => $cell,
        };
    }

    /**
     * The option a cell names: by its value ("12", "red") or, case and
     * punctuation aside, by its value or label ("Red", "Красный").
     */
    private function option(SelectField|BelongsToField $field, string $cell): mixed
    {
        $options = $this->options[$field->getName()] ??= $field instanceof BelongsToField ? $field->getOptions() : $field->toArray()['options'];
        $label = Cells::normalize($cell);
        $found = [];

        foreach ($options as $option) {
            if ((string) $option['value'] === $cell) {
                return $option['value'];
            }

            if (Cells::normalize((string) $option['value']) === $label || Cells::normalize((string) $option['label']) === $label) {
                $found[] = $option['value'];
            }
        }

        return match (count($found)) {
            0 => throw RowProblem::because('not_option', ['field' => $field->getLabel(), 'value' => $cell]),
            1 => $found[0],
            default => throw RowProblem::because('ambiguous_option', ['field' => $field->getLabel(), 'value' => $cell]),
        };
    }
}
