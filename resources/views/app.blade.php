<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Rezervačný systém športovísk Mestskej časti Bratislava-Karlova Ves.">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Rezervácia športovísk – Karlova Ves">
    <meta property="og:description" content="Rezervačný systém športovísk Mestskej časti Bratislava-Karlova Ves.">

    <link rel="shortcut icon" href="/favicon.ico" />
    <title>Rezervácia športovísk – Karlova Ves</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div id="app"></div>
</body>
</html>
