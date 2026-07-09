<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platobné údaje</title>
    <style>
        body, h1, p, a { margin: 0; padding: 0; font-family: sans-serif; }
        .container { padding: 24px; background-color: #f5f8fa; }
        .content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 32px;
            border-radius: 12px;
            border: 1px solid #e1e8ed;
        }
        .header { text-align: center; margin-bottom: 24px; color: #003366; }
        table { width: 100%; margin-top: 16px; }
        table td { padding: 6px 0; }
        table td.label { color: #777; width: 40%; }
        table td.value { font-weight: bold; }
        .footer { margin-top: 24px; text-align: center; font-size: 12px; color: #777; }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h1 class="header">Ďakujeme, rezervácia je overená</h1>
        <p>Dobrý deň, {{ $reservation->customer_name }},</p>
        <p style="margin-top: 16px;">Vaša rezervácia bola úspešne overená a čaká na spárovanie platby. Platobné údaje nájdete v priloženom PDF doklade. Rezervácia bude finálne potvrdená až po prijatí platby.</p>

        <table>
            <tr><td class="label">Ihrisko</td><td class="value">{{ $reservation->playground->name }}</td></tr>
            <tr><td class="label">Termín</td><td class="value">{{ $reservation->start_time->format('d.m.Y H:i') }} &ndash; {{ $reservation->end_time->format('H:i') }}</td></tr>
            <tr><td class="label">Suma</td><td class="value">{{ number_format((float)$reservation->total_price, 2, ',', ' ') }} &euro;</td></tr>
            <tr><td class="label">Variabilný symbol</td><td class="value">{{ $reservation->variable_symbol }}</td></tr>
        </table>

        <div class="footer">
            <p>Kompletné platobné údaje nájdete v prílohe tohto e-mailu.</p>
        </div>
    </div>
</div>
</body>
</html>
