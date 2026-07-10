<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    const string REG_ENABLED_KEY = 'auth.registrations.enabled';
    const string UPLOAD_MAX_SIZE_KEY = 'media.upload.max_size_kb';
    const string ORG_NAME_KEY = 'org.name';
    const string ORG_IBAN_KEY = 'org.iban';
    const string ORG_BANK_NAME_KEY = 'org.bank_name';
    const string CONTACT_ADDRESS_KEY = 'contact.address';
    const string CONTACT_PHONE_KEY = 'contact.phone';
    const string CONTACT_EMAIL_KEY = 'contact.email';
    const string CONTACT_PERSON_KEY = 'contact.person';
    const string CONTACT_HOURS_KEY = 'contact.hours';
    const string CONTACT_LATITUDE_KEY = 'contact.latitude';
    const string CONTACT_LONGITUDE_KEY = 'contact.longitude';
    const string SPORT_NOTIFICATION_EMAIL_KEY = 'notifications.sport_email';

    /**
     * Setting keys that are safe to expose to unauthenticated visitors
     * (e.g. for the public footer/contact page). Keep this list minimal.
     */
    const array PUBLIC_KEYS = [
        self::ORG_NAME_KEY,
        self::CONTACT_ADDRESS_KEY,
        self::CONTACT_PHONE_KEY,
        self::CONTACT_EMAIL_KEY,
        self::CONTACT_PERSON_KEY,
        self::CONTACT_HOURS_KEY,
        self::CONTACT_LATITUDE_KEY,
        self::CONTACT_LONGITUDE_KEY,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
    ];
}
