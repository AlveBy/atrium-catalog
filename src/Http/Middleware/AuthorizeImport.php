<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Http\Middleware;

use Atrium\Admin\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An import writes many products at once: only administrators with "Import"
 * in the products' row of their role run it (a superadmin always). The core
 * opens routes outside its resources to every administrator, hence this check.
 */
final class AuthorizeImport
{
    public const PERMISSION = 'catalog_products.import';

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::allows($request->user(config('atrium.auth.guard', 'admin')))) {
            abort(403, __('atrium::permissions.forbidden'));
        }

        return $next($request);
    }

    public static function allows(mixed $user): bool
    {
        return $user instanceof AdminUser && $user->hasAdminPermission(self::PERMISSION);
    }
}
