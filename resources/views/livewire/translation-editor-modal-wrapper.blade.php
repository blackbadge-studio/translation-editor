@if (auth()->check())
    @php
        $toggleColumn = config('translation-editor.auth.toggle_column', 'translation_modal_enabled');
    @endphp

    @if (auth()->user()->{$toggleColumn} ?? false)
        @livewire('translation-editor-modal')
    @endif
@endif
