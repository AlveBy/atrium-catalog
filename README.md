# Atrium Catalog

A product catalog for [Atrium](https://github.com/AlveBy/atrium): nested sections and products, shaped like a 1C-Bitrix iblock. The standard fields sit on the Bitrix tabs (General, Preview, Detail, SEO, Sections) and the properties come after them. The model uses the same traits the Bitrix migrator writes: slug history, SEO, tabs and attached files.

## Install

```bash
composer require alveby/atrium-catalog
php artisan atrium:module:install alveby/atrium-catalog
```

Add `--demo` to the second command to fill the catalog with a small electronics shop. Two modules appear in the admin menu:

- **Products**: name, code, activity dates, sort, article number, price, label, colours, release date, in stock, features, related products, pictures, texts, specifications, the box contents, photos and a manual.
- **Catalog sections**: a tree of sections with a picture and a description.

## Works with

- [`alveby/atrium-brands`](https://github.com/AlveBy/atrium/tree/main/packages/brands): a product gets a "Brand" field and a brand column in the list.
- [`alveby/atrium-stores`](https://github.com/AlveBy/atrium/tree/main/packages/stores): a product gets a "Where to buy" field that lists the stores.

Both links are soft. Without those modules the fields are not in the form, `$product->brand` is null and `$product->stores` is empty. No query touches their tables. `brand_id` and the `catalog_product_store` pivot table belong to the catalog, so a module installed later finds the links where they were left.

## On the site

With the public theme (`alveby/atrium-theme`), sections and products have their own pages under `/catalog`: `/catalog/computers/laptops/aero-14`. To move them, list the models in `config/atrium.php` under `public_urls`.

## Tests

From the repository root:

```bash
vendor/bin/phpunit packages/catalog/tests
```

They cover the whole demo shop (catalog, brands, stores and news) in the admin and on the site, `atrium:install --demo`, and the catalog without brands and stores.

## Contributing

This repository is a read-only copy of `packages/catalog` in [AlveBy/atrium](https://github.com/AlveBy/atrium). Issues and pull requests go there.

## License

MIT
