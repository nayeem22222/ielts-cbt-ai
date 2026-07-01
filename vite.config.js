import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/reading-test-renderer.css', 'resources/js/app.js', 'resources/js/reading-student.js', 'resources/js/listening-player.js', 'resources/js/reading-passage-builder.js', 'resources/js/listening-section-builder.js', 'resources/js/reading-matching-builder.js', 'resources/js/reading-objective-builder.js', 'resources/js/reading-completion-builder.js', 'resources/js/reading-diagram-builder.js', 'resources/js/reading-short-answer-builder.js', 'resources/js/reading-drag-drop-preview.js', 'resources/js/listening/review-transcript-highlight.js', 'resources/js/listening/review-audio-player.js', 'resources/js/listening/review-navigation.js', 'resources/js/listening-test-result-review.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
