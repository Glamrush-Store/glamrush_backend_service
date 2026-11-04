<?php

use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::flush());

it('lists countries using the country name as label and code3 as code', function () {
    $this->getJson('/api/v1/locations/countries')
        ->assertOk()
        ->assertHeader('Cache-Control')
        ->assertJsonFragment([
            'label' => 'Nigeria',
            'code' => 'NGA',
        ]);
});

it('lists states for a selected country using state names and codes', function () {
    $this->getJson('/api/v1/locations/countries/NGA/states')
        ->assertOk()
        ->assertJsonFragment([
            'label' => 'Lagos',
            'value' => 'LA',
        ]);
});

it('lists subdivisions as city labels and values', function () {
    $this->getJson('/api/v1/locations/countries/NGA/states/LA/cities')
        ->assertOk()
        ->assertJsonFragment([
            'label' => 'Ikeja',
            'value' => 'Ikeja',
        ]);
});

it('returns not found for unknown countries and states', function () {
    $this->getJson('/api/v1/locations/countries/XXX/states')->assertNotFound();
    $this->getJson('/api/v1/locations/countries/NGA/states/XXX/cities')->assertNotFound();
});
