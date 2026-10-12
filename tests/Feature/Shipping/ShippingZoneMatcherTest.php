<?php

use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingZone;
use App\Infrastructure\Persistence\Eloquent\Services\ShippingZoneMatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('shipping_zones', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('country');
        $table->string('state')->nullable();
        $table->string('city')->nullable();
        $table->string('postal_code_pattern')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('shipping_zone_locations', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('shipping_zone_id');
        $table->string('country');
        $table->string('scope');
        $table->string('state')->nullable();
        $table->string('city')->nullable();
        $table->string('match_key')->unique();
        $table->timestamps();
    });
});

function matcherZone(string $id, string $name, array $locations): ShippingZone
{
    $zone = ShippingZone::create([
        'id' => $id,
        'name' => $name,
        'country' => 'NGA',
        'state' => '__grouped__',
        'city' => '__grouped__',
        'is_active' => true,
    ]);

    $zone->locations()->createMany($locations);

    return $zone;
}

it('selects city before grouped state and country zones', function () {
    matcherZone('01CITY00000000000000000000', 'Selected Lagos cities', [[
        'id' => '01LOCATIONCITY000000000000',
        'country' => 'NGA',
        'scope' => 'city',
        'state' => 'LA',
        'city' => 'Ikeja',
        'match_key' => 'NGA|city|LA|ikeja',
    ]]);
    matcherZone('01STATE0000000000000000000', 'Lagos and Ogun', [
        [
            'id' => '01LOCATIONSTATE1000000000',
            'country' => 'NGA',
            'scope' => 'state',
            'state' => 'LA',
            'city' => null,
            'match_key' => 'NGA|state|LA',
        ],
        [
            'id' => '01LOCATIONSTATE2000000000',
            'country' => 'NGA',
            'scope' => 'state',
            'state' => 'OG',
            'city' => null,
            'match_key' => 'NGA|state|OG',
        ],
    ]);
    matcherZone('01COUNTRY00000000000000000', 'Nigeria', [[
        'id' => '01LOCATIONCOUNTRY00000000',
        'country' => 'NGA',
        'scope' => 'country',
        'state' => null,
        'city' => null,
        'match_key' => 'NGA|country',
    ]]);

    $matcher = app(ShippingZoneMatcher::class);

    expect($matcher->find(new ShippingAddressEntity('NG', 'Lagos', 'ikeja', null))?->name)
        ->toBe('Selected Lagos cities')
        ->and($matcher->find(new ShippingAddressEntity('NGA', 'LA', 'Epe', null))?->name)
        ->toBe('Lagos and Ogun')
        ->and($matcher->find(new ShippingAddressEntity('Nigeria', 'FC', 'Bwari', null))?->name)
        ->toBe('Nigeria');
});
