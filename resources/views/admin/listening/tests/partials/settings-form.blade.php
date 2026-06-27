@php($setting = $listeningTest->setting)
<x-ui.card title="Test Settings">
    <form method="POST" action="{{ route($routePrefix.'.settings.update', $listeningTest) }}">
        @csrf @method('PUT')
        <div class="grid gap-4 md:grid-cols-2">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_review_after_submit" value="1" @checked(old('allow_review_after_submit', $setting?->allow_review_after_submit ?? true))> Allow review after submit</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_correct_answer" value="1" @checked(old('show_correct_answer', $setting?->show_correct_answer ?? true))> Show correct answer</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_transcript_after_submit" value="1" @checked(old('show_transcript_after_submit', $setting?->show_transcript_after_submit ?? false))> Show transcript after submit</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_audio_review" value="1" @checked(old('show_audio_review', $setting?->show_audio_review ?? false))> Show audio review</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_audio_replay" value="1" @checked(old('allow_audio_replay', $setting?->allow_audio_replay ?? false))> Allow audio replay</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_audio_seek" value="1" @checked(old('allow_audio_seek', $setting?->allow_audio_seek ?? false))> Allow audio seek</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="auto_submit_on_timer_end" value="1" @checked(old('auto_submit_on_timer_end', $setting?->auto_submit_on_timer_end ?? true))> Auto submit on timer end</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="enable_tab_switch_detection" value="1" @checked(old('enable_tab_switch_detection', $setting?->enable_tab_switch_detection ?? true))> Enable tab switch detection</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="enable_copy_protection" value="1" @checked(old('enable_copy_protection', $setting?->enable_copy_protection ?? true))> Enable copy protection</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="enable_question_flagging" value="1" @checked(old('enable_question_flagging', $setting?->enable_question_flagging ?? true))> Enable question flagging</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="enable_auto_save" value="1" @checked(old('enable_auto_save', $setting?->enable_auto_save ?? true))> Enable auto save</label>
            <x-ui.input name="auto_save_interval_seconds" type="number" min="5" max="120" label="Auto save interval (seconds)" :value="old('auto_save_interval_seconds', $setting?->auto_save_interval_seconds ?? 10)" />
        </div>
        <div class="mt-6">
            <x-ui.button type="submit">Save Settings</x-ui.button>
        </div>
    </form>
</x-ui.card>
