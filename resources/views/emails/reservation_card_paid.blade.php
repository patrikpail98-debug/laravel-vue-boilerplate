<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platba prijatá</title>
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
        .badge {
            display: inline-block;
            padding: 4px 12px;
            background-color: #37CDBE;
            color: #ffffff;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }
        table { width: 100%; margin-top: 16px; }
        table td { padding: 6px 0; }
        table td.label { color: #777; width: 40%; }
        table td.value { font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h1 class="header">Platba prijatá <span class="badge">Potvrdené</span></h1>
        <p>Dobrý deň, {{ $reservation->customer_name }},</p>
        <p style="margin-top: 16px;">Vaša platba kartou bola úspešne prijatá a rezervácia je potvrdená. Tešíme sa na Vás.</p>

        <table>
            <tr><td class="label">Ihrisko</td><td class="value">{{ $reservation->playground->name }}</td></tr>
            <tr><td class="label">Termín</td><td class="value">{{ $reservation->startTimeLocal()->format('d.m.Y H:i') }} &ndash; {{ $reservation->endTimeLocal()->format('H:i') }}</td></tr>
            <tr><td class="label">Suma</td><td class="value">{{ number_format((float)$reservation->total_price, 2, ',', ' ') }} &euro;</td></tr>
            <tr><td class="label">Variabilný symbol</td><td class="value">{{ $reservation->variable_symbol }}</td></tr>
        </table>
    </div>
</div>
</body>
</html>
