<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $attempt->test?->title ?? 'Listening Test' }} · IELTS CBT</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/listening-player.js'])
</head>
<body class="ielts-listening-cbt listening-exam-body" data-listening-player @if(isset($payload)) data-player-payload='@json($payload)' @endif>
    @include('student.listening.player.partials.loading-overlay')

    <div id="listening-player-root">
        @include('student.listening.player.partials.header', ['attempt' => $attempt, 'payload' => $payload])
        @include('student.listening.player.partials.audio-player', ['payload' => $payload])
        @include('student.listening.player.partials.phase-banner', ['payload' => $payload])

        <main class="listening-exam-main">
            <div class="listening-exam-content">
                @include('student.listening.player.partials.question-area', ['payload' => $payload])
            </div>

            @include('student.listening.player.partials.navigation-controls')
        </main>

        @include('student.listening.player.partials.part-navigator', ['payload' => $payload])

        @include('student.listening.player.partials.submit-modal', ['payload' => $payload])
        @include('student.listening.player.partials.recovery-modal')
        @include('student.listening.player.partials.offline-banner')
        @include('student.listening.player.partials.time-warning-modal')
        @include('student.listening.player.partials.connection-status')
    </div>
</body>
</html>
