<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Súhrn platby</title>
    <style>
        /* DejaVu Sans (bundled with dompdf) is used instead of the default core
           PDF font because Helvetica/"sans-serif" cannot render Slovak diacritics
           (č, š, ž, ľ, ô, ä, ...) - they'd otherwise show up as missing/garbled glyphs. */
        body { font-family: 'DejaVu Sans', sans-serif; color: #222; font-size: 13px; }
        .header { text-align: center; color: #003366; margin-bottom: 24px; }
        .header h1 { font-size: 18px; margin: 0 0 4px; }
        .header p { margin: 0; color: #555; }
        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 14px;
            background-color: #2e7d32;
            color: #ffffff;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table td { padding: 8px 10px; border-bottom: 1px solid #e1e8ed; }
        table td.label { color: #555; width: 40%; }
        table td.value { font-weight: bold; }
        .footer { margin-top: 32px; font-size: 11px; color: #777; text-align: center; }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $orgName }}</h1>
    <p>Súhrn platby za rezerváciu športoviska</p>
    <span class="badge">ZAPLATENÉ</span>
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
        <td class="label">E-mail</td>
        <td class="value">{{ $reservation->customer_email }}</td>
    </tr>
    <tr>
        <td class="label">Telefón</td>
        <td class="value">{{ $reservation->customer_phone }}</td>
    </tr>
    <tr>
        <td class="label">Termín</td>
        <td class="value">{{ $reservation->startTimeLocal()->format('d.m.Y H:i') }} &ndash; {{ $reservation->endTimeLocal()->format('H:i') }}</td>
    </tr>
    <tr>
        <td class="label">Suma</td>
        <td class="value">{{ number_format((float)$reservation->total_price, 2, ',', ' ') }} &euro;</td>
    </tr>
    <tr>
        <td class="label">Spôsob platby</td>
        <td class="value">{{ $reservation->payment_method === 'card' ? 'Platobná karta' : 'Bankový prevod' }}</td>
    </tr>
    <tr>
        <td class="label">Variabilný symbol</td>
        <td class="value">{{ $reservation->variable_symbol }}</td>
    </tr>
    @if($reservation->verifiedAtLocal())
        <tr>
            <td class="label">Potvrdené</td>
            <td class="value">{{ $reservation->verifiedAtLocal()->format('d.m.Y H:i') }}</td>
        </tr>
    @endif
</table>

<div class="footer">
    <p>{{ $orgName }}</p>
</div>
</body>
</html>
