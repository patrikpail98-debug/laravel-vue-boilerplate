<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potvrďte rezerváciu</title>
    <style>
        body, h1, p, a { margin: 0; padding: 0; font-family: sans-serif; }
        .button {
            display: inline-block;
            padding: 12px 24px;
            margin: 20px 0;
            background-color: #FFCC00;
            color: #003366;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
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
        <h1 class="header">Potvrďte Vašu rezerváciu</h1>
        <p>Dobrý deň, {{ $reservation->customer_name }},</p>
        <p style="margin-top: 16px;">Prijali sme Vašu žiadosť o rezerváciu športoviska. Pre jej dokončenie kliknite na tlačidlo nižšie. Odkaz je platný {{ \App\Models\Reservation::HOLD_MINUTES }} minút, po jeho vypršaní bude termín uvoľnený pre iných záujemcov.</p>

        <table>
            <tr><td class="label">Ihrisko</td><td class="value">{{ $reservation->playground->name }}</td></tr>
            <tr><td class="label">Termín</td><td class="value">{{ $reservation->startTimeLocal()->format('d.m.Y H:i') }} &ndash; {{ $reservation->endTimeLocal()->format('H:i') }}</td></tr>
        </table>

        <a href="{{ $url }}" class="button">Potvrdiť rezerváciu</a>

        <p>Ak ste túto rezerváciu nevytvorili Vy, tento e-mail jednoducho ignorujte.</p>

        <div class="footer">
            <p>Ak tlačidlo nefunguje, skopírujte a vložte nasledujúci odkaz do prehliadača:</p>
            <p style="word-break: break-all; margin-top: 8px;"><a href="{{ $url }}">{{ $url }}</a></p>
        </div>
    </div>
</div>
</body>
</html>
