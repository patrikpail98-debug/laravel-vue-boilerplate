<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Platobný doklad</title>
    <style>
        /* DejaVu Sans (bundled with dompdf) is used instead of the default core
           PDF font because Helvetica/"sans-serif" cannot render Slovak diacritics
           (č, š, ž, ľ, ô, ä, ...) - they'd otherwise show up as missing/garbled glyphs. */
        body { font-family: 'DejaVu Sans', sans-serif; color: #222; font-size: 13px; }
        .header { text-align: center; color: #003366; margin-bottom: 24px; }
        .header h1 { font-size: 18px; margin: 0 0 4px; }
        .header p { margin: 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table td { padding: 8px 10px; border-bottom: 1px solid #e1e8ed; }
        table td.label { color: #555; width: 40%; }
        table td.value { font-weight: bold; }
        .vs-box { margin-top: 24px; padding: 16px; background-color: #f5f8fa; border: 1px solid #e1e8ed; border-radius: 8px; text-align: center; }
        .vs-box .vs { font-size: 22px; font-weight: bold; color: #003366; letter-spacing: 2px; }
        .footer { margin-top: 32px; font-size: 11px; color: #777; text-align: center; }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $orgName }}</h1>
    <p>Platobný doklad k rezervácii športoviska</p>
</div>

<table>
    <tr>
        <td class="label">Areál</td>
        <td class="value">{{ $reservation->playground->area->name }}</td>
    </tr>
    <tr>
        <td class="label">Ihrisko</td>
        <td class="value">{{ $reservation->playground->name }}</td>
    </tr>
    <tr>
        <td class="label">Rezervujúci</td>
        <td class="value">{{ $reservation->customer_name }}</td>
    </tr>
    <tr>
        <td class="label">Termín</td>
        <td class="value">{{ $reservation->start_time->format('d.m.Y H:i') }} &ndash; {{ $reservation->end_time->format('H:i') }}</td>
    </tr>
    <tr>
        <td class="label">Suma na úhradu</td>
        <td class="value">{{ number_format((float)$reservation->total_price, 2, ',', ' ') }} &euro;</td>
    </tr>
    <tr>
        <td class="label">IBAN</td>
        <td class="value">{{ $orgIban }}</td>
    </tr>
    @if($orgBankName)
        <tr>
            <td class="label">Banka</td>
            <td class="value">{{ $orgBankName }}</td>
        </tr>
    @endif
</table>

<div class="vs-box">
    <p style="margin: 0 0 6px;">Variabilný symbol</p>
    <div class="vs">{{ $reservation->variable_symbol }}</div>
</div>

<p style="margin-top: 24px;">Rezervácia bude potvrdená až po prijatí platby a jej spárovaní podľa variabilného symbolu. O potvrdení alebo zamietnutí rezervácie budete informovaní e-mailom.</p>

<div class="footer">
    <p>{{ $orgName }}</p>
</div>
</body>
</html>
