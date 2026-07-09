<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Setting;
use App\Traits\CanInstantiate;
use Barryvdh\DomPDF\Facade\Pdf;

class ReservationPdfService
{
    use CanInstantiate;

    /**
     * Renders the payment slip PDF for a reservation and returns the raw PDF bytes.
     */
    public function generatePaymentSlip(Reservation $reservation): string
    {
        $orgName = Setting::query()->where('key', Setting::ORG_NAME_KEY)->value('value') ?: 'Mestská časť Bratislava-Karlova Ves';
        $orgIban = Setting::query()->where('key', Setting::ORG_IBAN_KEY)->value('value') ?: '';
        $orgBankName = Setting::query()->where('key', Setting::ORG_BANK_NAME_KEY)->value('value') ?: '';

        return Pdf::setOptions([
            // DejaVu Sans is bundled with dompdf and covers Slovak diacritics;
            // the core PDF fonts (Helvetica/"sans-serif") don't, so without
            // this the payment slip renders "?" wherever a diacritic is used
            // even though the Blade view's CSS also sets the font-family.
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ])->loadView('pdf.payment_slip', [
            'reservation' => $reservation->loadMissing('playground.area'),
            'orgName' => $orgName,
            'orgIban' => $orgIban,
            'orgBankName' => $orgBankName,
        ])->output();
    }
}
