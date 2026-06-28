@php
    $settings = old('settings', $group->settings ?? ['columns' => ['Course', 'Duration'], 'rows' => [['cells' => [['text' => ''], ['blank' => $group->start_question_number ?? 1]]]], 'word_limit' => 2]);
    if (is_string($settings)) { $settings = json_decode($settings, true) ?: []; }
@endphp
<div class="md:col-span-2" x-data='tableForm(@json($settings))'>
    <p class="text-sm font-medium mb-2">Table Structure (JSON)</p>
    <textarea name="settings" rows="8" class="aa-input w-full font-mono text-sm" x-model="json"></textarea>
</div>
<script>
function tableForm(s){return{settings:s,get json(){return JSON.stringify(this.settings,null,2)},set json(v){try{this.settings=JSON.parse(v)}catch(e){}}};}
</script>
