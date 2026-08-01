<?php

namespace Database\Seeders\Support;

/**
 * Canonical contact / brand values used across all seeders.
 */
final class SeederContact
{
    public const PHONE = '+91 0000000000';

    public const PHONE_DIGITS = '910000000000';

    public const ADDRESS = 'Lion Gate, Fort, Mumbai, Maharashtra 400001';

    public const EMAIL_SUPPORT = 'support@examtube.in';

    public const EMAIL_INFO = 'info@examtube.in';

    public const EMAIL_CONTACT = 'contact@examtube.in';

    public const EMAIL_ADMIN = 'admin@examtube.in';

    public const WHATSAPP_URL = 'https://wa.me/'.self::PHONE_DIGITS;

    public const MAPS_URL = 'https://www.google.com/maps?q=Lion+Gate,+Fort,+Mumbai,+Maharashtra+400001&output=embed';

    public const CITY = 'Mumbai';

    public const STATE = 'Maharashtra';

    public const PIN = '400001';

    public const COUNTRY = 'IN';
}
