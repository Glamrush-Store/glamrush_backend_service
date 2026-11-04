<?php

namespace App\Domain\Content\Enums;

enum ContentPageType: string
{
    case About = 'about';
    case Contact = 'contact';
    case PrivacyPolicy = 'privacy_policy';
    case Terms = 'terms';
    case ShippingPolicy = 'shipping_policy';
    case ReturnsPolicy = 'returns_policy';
    case Custom = 'custom';
}
