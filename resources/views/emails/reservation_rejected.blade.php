<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervácia zamietnutá</title>
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
        .note { margin-top: 16px; padding: 12px 16px; background-color: #fdf2f2; border-radius: 8px; color: #a33; }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h1 class="header">Rezervácia zamietnutá</h1>
        <p>Dobrý deň, {{ $reservation->customer_name }},</p>
        <p style="margin-top: 16px;">Bohužiaľ, Vašu rezerváciu sme museli zamietnuť.</p>

        <table>
            <tr><td class="label">Ihrisko</td><td class="value">{{ $reservation->playground->name }}</td></tr>
            <tr><td class="label">Termín</td><td class="value">{{ $reservation->startTimeLocal()->format('d.m.Y H:i') }} &ndash; {{ $reservation->endTimeLocal()->format('H:i') }}</td></tr>
        </table>

        @if($reservation->admin_note)
            <div class="note">{{ $reservation->admin_note }}</div>
        @endif
    </div>
</div>
</body>
</html>
