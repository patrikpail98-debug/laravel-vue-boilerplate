<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obnovenie hesla</title>
    <style>
        body, h1, p, a { margin: 0; padding: 0; font-family: sans-serif; box-sizing: border-box; }
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
        .container {
            width: 100%;
            padding: 24px;
            background-color: #f5f8fa;
        }
        .content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 32px;
            border-radius: 12px;
            border: 1px solid #e1e8ed;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
            color: #003366;
        }
        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h1 class="header">Žiadosť o obnovenie hesla</h1>
        <p>Dobrý deň, {{ $user->name }},</p>
        <p style="margin-top: 16px;">Prijali sme žiadosť o obnovenie Vášho hesla.</p>

        <a href="{{ $url }}" class="button">Obnoviť heslo</a>

        <p>Platnosť tohto odkazu vyprší o 15 minút.</p>
        <p style="margin-top: 16px;">Ak ste o obnovenie hesla nežiadali, tento e-mail jednoducho ignorujte.</p>

        <p style="margin-top: 16px;">S pozdravom,<br>Mestská časť Bratislava-Karlova Ves</p>

        <div class="footer">
            <p>Ak tlačidlo nefunguje, skopírujte a vložte nasledujúci odkaz do prehliadača:</p>
            <p style="word-break: break-all; margin-top: 8px;"><a href="{{ $url }}">{{ $url }}</a></p>
        </div>
    </div>
</div>
</body>
</html>
