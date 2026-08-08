<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

final class StorefrontHomepageSectionProduct extends Pivot
{
    public $timestamps = false;

    protected $table = 'storefront_homepage_section_product';
}
