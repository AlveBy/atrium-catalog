<?php

declare(strict_types=1);

namespace Atrium\Catalog\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\ImageField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SlugField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Fields\TreeSelectField;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sections of the catalog, nested like Bitrix iblock sections.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $code
 */
class CatalogSection extends Model implements Administrable
{
    use InteractsWithAdmin;

    protected $table = 'catalog_sections';

    protected $fillable = ['parent_id', 'name', 'code', 'active', 'sort', 'picture', 'description'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort' => 'integer'];
    }

    /**
     * @return BelongsTo<self,$this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Product,$this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'section_id');
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-catalog::catalog.modules.sections');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'catalog_sections';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function getAdministrationFields(): array
    {
        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required(),
            SlugField::make('code')->label(__('atrium::fields.standard.code'))->from('name'),
            TreeSelectField::make('parent_id')->label(__('atrium::fields.standard.parent_section'))->model(self::class),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
            ImageField::make('picture')->label(__('atrium::fields.standard.picture'))->directory('catalog'),
            ContentField::make('description')->label(__('atrium::fields.standard.description')),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
