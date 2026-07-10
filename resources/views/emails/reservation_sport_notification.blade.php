<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nová zaplatená rezervácia</title>
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
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h1 class="header">Nová zaplatená rezervácia</h1>
        <p>Bola vytvorená a automaticky schválená nasledujúca rezervácia (platba kartou):</p>

        <table>
            <tr><td class="label">Areál</td><td class="value">{{ $reservation->playground->area->name }}</td></tr>
            <tr><td class="label">Ihrisko</td><td class="value">{{ $reservation->playground->name }}</td></tr>
            <tr><td class="label">Termín</td><td class="value">{{ $reservation->startTimeLocal()->format('d.m.Y H:i') }} &ndash; {{ $reservation->endTimeLocal()->format('H:i') }}</td></tr>
            <tr><td class="label">Zákazník</td><td class="value">{{ $reservation->customer_name }}</td></tr>
            <tr><td class="label">E-mail</td><td class="value">{{ $reservation->customer_email }}</td></tr>
            <tr><td class="label">Telefón</td><td class="value">{{ $reservation->customer_phone }}</td></tr>
            <tr><td class="label">Suma</td><td class="value">{{ number_format((float)$reservation->total_price, 2, ',', ' ') }} &euro;</td></tr>
            <tr><td class="label">Variabilný symbol</td><td class="value">{{ $reservation->variable_symbol }}</td></tr>
        </table>
    </div>
</div>
</body>
</html>
