@if (! empty($preview['content_preview']))
    <div class="prose prose-sm max-w-none dark:prose-invert">{!! $preview['content_preview'] !!}</div>
@elseif (! empty($preview['content']))
    <pre class="text-sm whitespace-pre-wrap">{{ $preview['content'] }}</pre>
@endif
