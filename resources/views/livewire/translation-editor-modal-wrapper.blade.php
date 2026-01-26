<!-- Translation Editor Modal Wrapper -->
<script>
    console.log('Translation Editor wrapper loaded');
</script>

@if (auth()->check())
    @php
        $user = auth()->user();
        $toggleColumn = config('translation-editor.auth.toggle_column', 'translation_modal_enabled');
        $isEnabled = $user->{$toggleColumn} ?? false;
    @endphp

    @if ($isEnabled)
        @livewire('translation-editor-modal')
    @else
        <!-- Translation editor disabled for user. Toggle column: {{ $toggleColumn }}, Value: {{ var_export($user->{$toggleColumn} ?? null, true) }} -->
    @endif
@else
    <!-- User not authenticated -->
@endif
