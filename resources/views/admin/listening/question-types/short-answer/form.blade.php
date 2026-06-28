@php $settings = old('settings', $group->settings ?? ['word_limit' => 3, 'allow_number' => true]); @endphp
<div class="md:col-span-2 rounded-xl border border-neutral-200 p-3 text-sm dark:border-neutral-800">
    <p>Short answer questions are configured per question. Set word limit and instruction on each question row.</p>
    <input type="hidden" name="settings" value="{{ json_encode(is_array($settings) ? $settings : json_decode($settings, true)) }}">
</div>
