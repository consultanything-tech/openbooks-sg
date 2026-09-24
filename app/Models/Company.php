<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'address', 'city', 'state', 'country',
        'currency_code', 'currency_symbol', 'tax_number', 'logo_path',
        'financial_year_start', 'financial_year',
        'nvidia_api_key', 'nvidia_model',
        'invoice_prefix', 'bill_prefix', 'credit_note_prefix',
        'default_payment_terms', 'default_payment_notes', 'invoice_footer',
        'accent_color', 'show_logo_on_documents', 'show_tax_number_on_documents',
        'show_phone_on_documents',
        'paynow_id', 'paynow_id_type', 'paynow_name',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
        'smtp_encryption', 'smtp_from_email', 'smtp_from_name',
        'reminder_days_1', 'reminder_days_2', 'reminder_days_3', 'auto_reminders_enabled',
    ];

    protected $casts = [
        'show_logo_on_documents' => 'boolean',
        'show_tax_number_on_documents' => 'boolean',
        'show_phone_on_documents' => 'boolean',
        'auto_reminders_enabled' => 'boolean',
        'nvidia_api_key' => 'encrypted',
        'smtp_password' => 'encrypted',
    ];

    protected $hidden = [
        'nvidia_api_key',
        'smtp_password',
    ];

    /**
     * Alias for currency_code
     */
    public function getCurrencyAttribute()
    {
        return $this->currency_code;
    }

    public function setCurrencyAttribute($value)
    {
        $this->attributes['currency_code'] = $value;
    }
}
