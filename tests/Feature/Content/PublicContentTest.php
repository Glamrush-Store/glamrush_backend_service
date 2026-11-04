<?php

use App\Infrastructure\Persistence\Eloquent\Models\ContactSubmission;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Mail\Contact\ContactSubmissionReceivedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    RateLimiter::clear('');
    Mail::fake();
    config()->set('contact.notification_address', 'support@glamrush.test');

    Schema::create('categories', function ($table) {
        $table->ulid('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->ulid('parent_id')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('content_pages', function ($table) {
        $table->ulid('id')->primary();
        $table->string('slug')->unique();
        $table->string('title');
        $table->string('navigation_title')->nullable();
        $table->text('excerpt')->nullable();
        $table->text('content');
        $table->string('page_type');
        $table->json('settings')->nullable();
        $table->string('meta_title')->nullable();
        $table->string('meta_description')->nullable();
        $table->boolean('is_published')->default(false);
        $table->timestamp('published_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->boolean('applies_to_all_storefronts')->default(true);
        $table->integer('display_order')->default(0);
        $table->unsignedBigInteger('created_by_admin_id')->nullable();
        $table->unsignedBigInteger('updated_by_admin_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('content_page_storefronts', function ($table) {
        $table->ulid('content_page_id');
        $table->ulid('category_id');
        $table->timestamps();
    });
    Schema::create('faq_categories', function ($table) {
        $table->ulid('id')->primary();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->integer('display_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('faqs', function ($table) {
        $table->ulid('id')->primary();
        $table->ulid('faq_category_id');
        $table->string('question');
        $table->text('answer');
        $table->integer('display_order')->default(0);
        $table->boolean('is_published')->default(false);
        $table->timestamp('published_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->boolean('applies_to_all_storefronts')->default(true);
        $table->unsignedBigInteger('created_by_admin_id')->nullable();
        $table->unsignedBigInteger('updated_by_admin_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('faq_storefronts', function ($table) {
        $table->ulid('faq_id');
        $table->ulid('category_id');
        $table->timestamps();
    });
    Schema::create('media', function ($table) {
        $table->id();
        $table->string('model_type');
        $table->ulid('model_id');
        $table->integer('order_column')->nullable();
    });
});

it('returns a published global page without administrative fields', function () {
    contentStorefront('fragrances');
    contentPage(['slug' => 'about', 'title' => 'About Glamrush', 'created_by_admin_id' => 99]);

    $this->getJson('/api/v1/storefronts/fragrances/pages/about')
        ->assertOk()
        ->assertJsonPath('data.title', 'About Glamrush')
        ->assertJsonPath('data.settings', null)
        ->assertJsonMissingPath('data.created_by_admin_id')
        ->assertJsonMissingPath('data.is_published')
        ->assertJsonMissingPath('data.deleted_at');
});

it('enforces publication lifecycle and storefront targeting without disclosing configuration', function () {
    $fragrances = contentStorefront('fragrances');
    $skincare = contentStorefront('skincare');
    $draft = contentPage(['slug' => 'draft', 'is_published' => false]);
    $scheduled = contentPage(['slug' => 'scheduled', 'published_at' => now()->addHour()]);
    $expired = contentPage(['slug' => 'expired', 'expires_at' => now()->subMinute()]);
    $specific = contentPage(['slug' => 'specific', 'applies_to_all_storefronts' => false]);
    DB::table('content_page_storefronts')->insert(['content_page_id' => $specific, 'category_id' => $skincare, 'created_at' => now(), 'updated_at' => now()]);

    foreach (['draft', 'scheduled', 'expired', 'specific', 'missing'] as $slug) {
        $this->getJson("/api/v1/storefronts/fragrances/pages/{$slug}")->assertNotFound()->assertJsonPath('message', 'Page not found.');
    }
    expect($fragrances)->not->toBe($skincare)->and($draft)->not->toBe($scheduled)->and($expired)->not->toBe($specific);
});

it('rejects unknown child inactive and deleted storefronts', function () {
    $root = contentStorefront('fragrances');
    contentStorefront('child-shop', ['parent_id' => $root]);
    contentStorefront('inactive-shop', ['is_active' => false]);
    contentStorefront('deleted-shop', ['deleted_at' => now()]);
    contentPage(['slug' => 'about']);

    foreach (['unknown', 'child-shop', 'inactive-shop', 'deleted-shop'] as $storefront) {
        $this->getJson("/api/v1/storefronts/{$storefront}/pages/about")->assertNotFound()->assertJsonPath('message', 'Storefront not found.');
    }
});

it('allowlists public contact settings and removes unsafe URLs and unknown keys', function () {
    contentStorefront('fragrances');
    contentPage([
        'slug' => 'contact', 'page_type' => 'contact',
        'settings' => json_encode([
            'email' => 'hello@glamrush.test', 'phone' => '+2348000000000', 'smtp_password' => 'secret',
            'map_url' => 'http://unsafe.test/map',
            'social_links' => [
                ['platform' => 'instagram', 'url' => 'https://instagram.com/glamrush'],
                ['platform' => 'unknown', 'url' => 'https://example.com'],
                ['platform' => 'x', 'url' => 'javascript:alert(1)'],
            ],
        ]),
    ]);

    $this->getJson('/api/v1/storefronts/fragrances/pages/contact')
        ->assertOk()
        ->assertJsonPath('data.settings.email', 'hello@glamrush.test')
        ->assertJsonPath('data.settings.map_url', null)
        ->assertJsonCount(1, 'data.settings.social_links')
        ->assertJsonMissingPath('data.settings.smtp_password');
});

it('returns eligible FAQs grouped and deterministically ordered with filtering', function () {
    $fragrances = contentStorefront('fragrances');
    $skincare = contentStorefront('skincare');
    $shipping = faqCategory('Shipping', 20);
    $orders = faqCategory('Orders', 10);
    faqEntry($shipping, 'Where is my parcel?', 20);
    faqEntry($orders, 'Can I change my order?', 20);
    faqEntry($orders, 'How do I track delivery?', 10);
    $cross = faqEntry($shipping, 'Skincare only?', 5, ['applies_to_all_storefronts' => false]);
    DB::table('faq_storefronts')->insert(['faq_id' => $cross, 'category_id' => $skincare, 'created_at' => now(), 'updated_at' => now()]);

    $response = $this->getJson('/api/v1/storefronts/fragrances/faqs?search=order&per_page=10')->assertOk();
    $response->assertJsonPath('data.0.slug', 'orders')->assertJsonPath('data.0.faqs.0.question', 'Can I change my order?');

    $this->getJson('/api/v1/storefronts/fragrances/faqs?category=shipping')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonCount(1, 'data.0.faqs')
        ->assertJsonMissingPath('data.0.faqs.0.is_published');
    expect($fragrances)->not->toBe($skincare);
});

it('excludes FAQ drafts expired entries and inactive categories', function () {
    contentStorefront('fragrances');
    $active = faqCategory('Active', 10);
    $inactive = faqCategory('Inactive', 20);
    DB::table('faq_categories')->where('id', $inactive)->update(['is_active' => false]);
    faqEntry($active, 'Published question?', 10);
    faqEntry($active, 'Draft question?', 20, ['is_published' => false]);
    faqEntry($active, 'Expired question?', 30, ['expires_at' => now()->subMinute()]);
    faqEntry($inactive, 'Inactive category question?', 10);

    $this->getJson('/api/v1/storefronts/fragrances/faqs')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonCount(1, 'data.0.faqs')
        ->assertJsonPath('data.0.faqs.0.question', 'Published question?');
});

it('persists a public contact submission and returns only a reference', function () {
    contentStorefront('fragrances');

    $response = $this->postJson('/api/v1/storefronts/fragrances/contact-submissions', contactPayload())
        ->assertAccepted()
        ->assertJsonPath('message', 'Your message has been received.')
        ->assertJsonMissingPath('data.email')
        ->assertJsonMissingPath('data.message');

    expect($response->json('data.reference'))->toBeString()
        ->and(ContactSubmission::query()->sole()->email)->toBe('jane@example.com')
        ->and(ContactSubmission::query()->sole()->status)->toBe('new');
    Mail::assertQueued(ContactSubmissionReceivedMail::class);
});

it('associates authenticated customers and ignores client-owned internal fields', function () {
    contentStorefront('fragrances');
    $user = User::query()->create(['name' => 'Jane', 'email' => 'account@example.com', 'password' => 'password']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/storefronts/fragrances/contact-submissions', [
        ...contactPayload(), 'status' => 'resolved', 'resolved_at' => now()->toISOString(), 'customer_account_id' => 999,
    ])->assertAccepted();

    $submission = ContactSubmission::query()->sole();
    expect($submission->customer_account_id)->toBe($user->id)
        ->and($submission->status)->toBe('new')
        ->and($submission->resolved_at)->toBeNull();
});

it('rejects unsafe contact content and silently accepts honeypot spam without persistence', function () {
    contentStorefront('fragrances');
    $this->postJson('/api/v1/storefronts/fragrances/contact-submissions', [
        ...contactPayload(), 'message' => '<script>alert(1)</script> javascript:bad',
    ])->assertUnprocessable()->assertJsonValidationErrors('message');

    $this->postJson('/api/v1/storefronts/fragrances/contact-submissions', [
        ...contactPayload(), 'website' => 'spam.example',
    ])->assertAccepted()->assertJsonStructure(['data' => ['reference']]);

    expect(ContactSubmission::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

it('validates contact details and rate limits by storefront and IP', function () {
    contentStorefront('fragrances');
    $this->postJson('/api/v1/storefronts/fragrances/contact-submissions', [
        ...contactPayload(), 'email' => 'invalid', 'phone' => '12', 'message' => 'short',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone', 'message']);

    Cache::flush();
    config()->set('api_rate_limits.contact_submissions_per_minute', 2);
    foreach ([1, 2] as $number) {
        $this->postJson('/api/v1/storefronts/fragrances/contact-submissions', [
            ...contactPayload(), 'message' => "This is distinct customer message number {$number}.",
        ])->assertAccepted();
    }
    $this->postJson('/api/v1/storefronts/fragrances/contact-submissions', [
        ...contactPayload(), 'message' => 'This third distinct message should be throttled.',
    ])->assertStatus(429)->assertJsonPath('message', 'Too many requests. Please try again later.');
});

function contentStorefront(string $slug, array $overrides = []): string
{
    $id = (string) Str::ulid();
    DB::table('categories')->insert(array_merge([
        'id' => $id, 'name' => str($slug)->headline(), 'slug' => $slug, 'parent_id' => null,
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null,
    ], $overrides));

    return $id;
}

function contentPage(array $overrides = []): string
{
    $id = (string) Str::ulid();
    DB::table('content_pages')->insert(array_merge([
        'id' => $id, 'slug' => 'about', 'title' => 'Page', 'content' => '<p>Safe content.</p>', 'page_type' => 'about',
        'is_published' => true, 'published_at' => now()->subMinute(), 'expires_at' => null,
        'applies_to_all_storefronts' => true, 'display_order' => 0, 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null,
    ], $overrides));

    return $id;
}

function faqCategory(string $name, int $order): string
{
    $id = (string) Str::ulid();
    DB::table('faq_categories')->insert(['id' => $id, 'name' => $name, 'slug' => str($name)->slug(), 'display_order' => $order, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

    return $id;
}

function faqEntry(string $categoryId, string $question, int $order, array $overrides = []): string
{
    $id = (string) Str::ulid();
    DB::table('faqs')->insert(array_merge([
        'id' => $id, 'faq_category_id' => $categoryId, 'question' => $question, 'answer' => '<p>Helpful answer.</p>',
        'display_order' => $order, 'is_published' => true, 'published_at' => now()->subMinute(), 'expires_at' => null,
        'applies_to_all_storefronts' => true, 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null,
    ], $overrides));

    return $id;
}

function contactPayload(): array
{
    return [
        'name' => 'Jane Doe', 'email' => ' JANE@example.com ', 'phone' => '+2348000000000',
        'subject' => 'Delivery question', 'message' => "Please confirm when my order will arrive.\r\nThank you.",
        'source' => 'contact-page', 'website' => '',
    ];
}
