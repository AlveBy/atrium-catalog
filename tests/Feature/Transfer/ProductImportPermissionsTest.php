<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Admin\Auth\Permissions;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Models\DataImport;
use Atrium\Catalog\Models\Product;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Who may import the products and export their list: the catalog_products.import
 * and catalog_products.export permissions, each one alone.
 */
final class ProductImportPermissionsTest extends ProductTransferTestCase
{
    private function editor(array $permissions, string $email = 'editor@example.com'): AdminUser
    {
        $user = AdminUser::query()->create(['name' => 'Editor', 'email' => $email, 'password' => 'secret-password', 'is_active' => true]);
        $user->roles()->attach(AdminRole::query()->create(['name' => 'Role of '.$email, 'permissions' => $permissions]));

        return $user->fresh();
    }

    private function pending(): DataImport
    {
        return DataImport::query()->create([
            'module' => 'catalog_products',
            'status' => DataImport::NEW,
            'format' => 'csv',
            'encoding' => 'UTF-8',
            'delimiter' => ';',
            'headers' => ['Article', 'Name'],
            'mapping' => ['article', 'name'],
            'total' => 1,
            'original_name' => 'price.csv',
            'file' => 'none.csv',
        ]);
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function routes(): array
    {
        return [
            'the list' => ['get', ''],
            'the upload' => ['post', ''],
            'the mapping screen' => ['get', '/{id}'],
            'the check' => ['put', '/{id}'],
            'the import' => ['post', '/{id}/run'],
            'the progress' => ['post', '/{id}/step'],
            'the stop' => ['post', '/{id}/cancel'],
        ];
    }

    public function test_the_catalog_offers_both_permissions_in_the_role_form(): void
    {
        $all = $this->app->make(Permissions::class)->all();

        $this->assertContains('catalog_products.import', $all);
        $this->assertContains('catalog_products.export', $all);
    }

    #[DataProvider('routes')]
    public function test_every_screen_of_the_import_is_closed_without_its_permission(string $method, string $path): void
    {
        $import = $this->pending();
        $other = $this->editor(['catalog_products.view', 'catalog_products.create', 'catalog_products.update', 'catalog_products.export', 'news.import'], 'no@example.com');

        $this->actingAs($other, 'admin')->{$method}('/admin/catalog_products/import'.str_replace('{id}', (string) $import->id, $path))->assertForbidden();

        $this->assertSame(DataImport::NEW, $import->refresh()->status);
        $this->assertSame(1, DataImport::query()->count());
    }

    #[DataProvider('routes')]
    public function test_the_import_permission_alone_opens_every_screen_of_the_import(string $method, string $path): void
    {
        $import = $this->pending();
        $only = $this->editor(['catalog_products.import'], 'yes@example.com');

        $status = $this->actingAs($only, 'admin')
            ->{$method}('/admin/catalog_products/import'.str_replace('{id}', (string) $import->id, $path), ['file' => UploadedFile::fake()->createWithContent('a.csv', "Article;Name\nA-1;Parka\n")])
            ->status();

        $this->assertNotSame(403, $status);
    }

    public function test_the_export_is_closed_without_its_permission(): void
    {
        Product::query()->create(['article' => 'A-1', 'name' => 'Parka', 'price' => 100]);
        $other = $this->editor(['catalog_products.view', 'catalog_products.import', 'news.export'], 'no@example.com');

        $this->actingAs($other, 'admin')->get('/admin/catalog_products/export')->assertForbidden();
        $this->actingAs($other, 'admin')->get('/admin/catalog_products/export?format=xlsx')->assertForbidden();
    }

    public function test_the_export_permission_alone_gives_the_file_and_not_the_import(): void
    {
        Product::query()->create(['article' => 'A-1', 'name' => 'Parka', 'price' => 100]);
        $only = $this->editor(['catalog_products.export'], 'yes@example.com');

        $response = $this->actingAs($only, 'admin')->get('/admin/catalog_products/export');

        $response->assertOk();
        $this->assertStringContainsString('A-1', $response->streamedContent());
        $this->actingAs($only, 'admin')->get('/admin/catalog_products/import')->assertForbidden();
    }

    #[DataProvider('abilities')]
    public function test_the_list_offers_the_buttons_the_role_allows(array $permissions, array $expected): void
    {
        $user = $this->editor(['catalog_products.view', ...$permissions]);

        $this->actingAs($user, 'admin')->get('/admin/catalog_products')
            ->assertOk()
            ->assertInertia(static fn (AssertableInertia $page) => $page->where('transfer', $expected));
    }

    /**
     * @return array<string,array{array<int,string>,array{import:bool,export:bool}}>
     */
    public static function abilities(): array
    {
        return [
            'neither' => [[], ['import' => false, 'export' => false]],
            'only the import' => [['catalog_products.import'], ['import' => true, 'export' => false]],
            'only the export' => [['catalog_products.export'], ['import' => false, 'export' => true]],
            'both' => [['catalog_products.import', 'catalog_products.export'], ['import' => true, 'export' => true]],
            'of another module' => [['news.import', 'news.export'], ['import' => false, 'export' => false]],
        ];
    }

    public function test_the_superadmin_has_both_buttons(): void
    {
        $this->actingAs($this->admin(), 'admin')->get('/admin/catalog_products')
            ->assertInertia(static fn (AssertableInertia $page) => $page->where('transfer', ['import' => true, 'export' => true]));
    }

    public function test_a_guest_is_sent_to_the_login(): void
    {
        $this->get('/admin/catalog_products/import')->assertRedirect();
        $this->get('/admin/catalog_products/export')->assertRedirect();
        $this->post('/admin/catalog_products/import')->assertRedirect();
        $this->assertSame(0, DataImport::query()->count());
    }
}
