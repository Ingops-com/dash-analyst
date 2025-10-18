<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Plan de Saneamiento - {{ $company['company_name'] }}</title>
    <style>
        @page { margin: 70px 50px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; line-height: 1.6; }
        h1, h2, h3 { font-weight: bold; }
        h1 { font-size: 16px; text-align: center; text-transform: uppercase; }
        h2 { font-size: 14px; margin-top: 25px; border-bottom: 1px solid #000; padding-bottom: 4px; text-transform: uppercase; }
        p { text-align: justify; margin-bottom: 12px; }
        .page-break { page-break-after: always; }
        .annex-container { margin-top: 25px; page-break-inside: avoid; }
        .annex-image { max-width: 100%; border: 1px solid #ccc; margin-top: 15px; }
        .signature-section { margin-top: 60px; }
        strong { font-weight: bold; }
        em { font-style: italic; }
    </style>
</head>
<body>
    <main>
        {{-- Inyectamos aquí todo el contenido procesado del Word --}}
        {!! $mainContent !!}

        <div class="page-break"></div>

        <h2>ANEXOS DEL PROGRAMA</h2>
        @if(empty($annexes))
            <p>No se adjuntaron anexos para este programa.</p>
        @else
            @foreach($annexes as $anexo)
                <div class="annex-container">
                    <h3>{{ $anexo['title'] }}</h3>
                    <img src="{{ $anexo['path'] }}" class="annex-image" alt="{{ $anexo['title'] }}">
                </div>
            @endforeach
        @endif
    </main>
</body>
</html>