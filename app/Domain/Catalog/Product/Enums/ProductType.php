<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Enums;

enum ProductType: string
{
    case SIMPLE = 'simple';
    case VARIABLE = 'variable';
    case DIGITAL = 'digital';
    case SERVICE = 'service';

    public function isSimpleProduct(): bool
    {
        return $this === self::SIMPLE;
    }

    public function isVariableProduct(): bool
    {
        return $this === self::VARIABLE;
    }
}
