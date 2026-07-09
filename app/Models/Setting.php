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
