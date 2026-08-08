<?php

namespace App\Domain\Content\Services;

use App\Infrastructure\Persistence\Eloquent\Models\ContentPage;
use App\Infrastructure\Persistence\Eloquent\Models\Faq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class PublicContentService
{
    public function findPage(string $slug, string $rootCategoryId): ?ContentPage
    {
        return ContentPage::query()
            ->publiclyAvailable()
            ->forStorefront($rootCategoryId)
            ->where('slug', $slug)
            ->with('media')
            ->first();
    }

    public function paginateFaqs(
        string $rootCategoryId,
        ?string $category,
        ?string $search,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        return Faq::query()
            ->select('faqs.*')
            ->join('faq_categories', 'faq_categories.id', '=', 'faqs.faq_category_id')
            ->whereNull('faq_categories.deleted_at')
            ->where('faq_categories.is_active', true)
            ->publiclyAvailable()
            ->forStorefront($rootCategoryId)
            ->when($category, fn (Builder $query, string $slug) => $query->where('faq_categories.slug', $slug))
            ->when($search, function (Builder $query, string $term) {
                $needle = '%'.mb_strtolower($term).'%';
                $query->where(fn (Builder $searchQuery) => $searchQuery
                    ->whereRaw('LOWER(faqs.question) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(faqs.answer) LIKE ?', [$needle]));
            })
            ->with('category:id,name,slug,description,display_order')
            ->orderBy('faq_categories.display_order')
            ->orderBy('faqs.display_order')
            ->orderBy('faqs.created_at')
            ->orderBy('faqs.id')
            ->paginate(perPage: $perPage, page: $page)
            ->withQueryString();
    }
}
