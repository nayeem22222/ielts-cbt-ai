@if (!empty($item['explanation']))
    <x-ui.card title="Explanation">
        <p class="text-sm">{{ $item['explanation'] }}</p>
    </x-ui.card>
@endif
