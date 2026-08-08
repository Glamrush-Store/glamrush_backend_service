<?php

namespace App\OpenApi;

/**
 * @OA\Get(
 *   path="/api/v1/storefronts/{storefront}/pages/{slug}",
 *   tags={"Public Content"}, summary="Get a published storefront page",
 *
 *   @OA\Parameter(name="storefront", in="path", required=true, @OA\Schema(type="string", example="fragrances")),
 *   @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string", example="about")),
 *
 *   @OA\Response(response=200, description="Published page"),
 *   @OA\Response(response=404, description="Storefront or page unavailable")
 * )
 *
 * @OA\Get(
 *   path="/api/v1/storefronts/{storefront}/faqs",
 *   tags={"Public Content"}, summary="List published storefront FAQs",
 *
 *   @OA\Parameter(name="storefront", in="path", required=true, @OA\Schema(type="string", example="fragrances")),
 *   @OA\Parameter(name="category", in="query", @OA\Schema(type="string")),
 *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1)),
 *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=50)),
 *
 *   @OA\Response(response=200, description="Grouped FAQ results"),
 *   @OA\Response(response=404, description="Storefront unavailable"),
 *   @OA\Response(response=422, description="Invalid filter")
 * )
 *
 * @OA\Post(
 *   path="/api/v1/storefronts/{storefront}/contact-submissions",
 *   tags={"Contact"}, summary="Submit a public contact message",
 *
 *   @OA\Parameter(name="storefront", in="path", required=true, @OA\Schema(type="string", example="fragrances")),
 *
 *   @OA\RequestBody(required=true, @OA\JsonContent(
 *     required={"name","email","message"},
 *
 *     @OA\Property(property="name", type="string", maxLength=150),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="subject", type="string", nullable=true),
 *     @OA\Property(property="message", type="string", minLength=10, maxLength=5000),
 *     @OA\Property(property="source", type="string", nullable=true, example="contact-page"),
 *     @OA\Property(property="website", type="string", description="Honeypot; leave empty")
 *   )),
 *
 *   @OA\Response(response=202, description="Message accepted; response contains only a reference"),
 *   @OA\Response(response=404, description="Storefront unavailable"),
 *   @OA\Response(response=422, description="Invalid message"),
 *   @OA\Response(response=429, description="Rate limit exceeded")
 * )
 */
final class PublicContent {}
