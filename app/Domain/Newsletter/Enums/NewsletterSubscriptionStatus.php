<?php

namespace App\Domain\Newsletter\Enums;

enum NewsletterSubscriptionStatus: string
{
    case Pending = 'pending';
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
}
