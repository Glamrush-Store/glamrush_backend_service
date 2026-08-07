<?php

namespace App\Domain\Storefront\Enums;

enum HomepageSectionType: string
{
    case FeaturedProducts = 'featured_products';
    case SaleProducts = 'sale_products';
    case CategoryProducts = 'category_products';
    case CollectionProducts = 'collection_products';
    case NewestProducts = 'newest_products';
    case RandomCategories = 'random_categories';
    case ManualProducts = 'manual_products';
}
