<div x-data="{ collapsed: @js($isCollapsed) }" class="fixed" style="bottom: 24px; right: 24px; z-index: 9999;">
    <!-- Collapsed state: Small bubble with icon -->
    <div x-show="collapsed" x-transition class="flex items-center justify-center">
        <button type="button" id="translation-editor-bubble" @click="collapsed = !collapsed"
            class="flex items-center justify-center w-12 h-12 bg-primary-600 hover:bg-primary-700 text-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-200"
            title="Open Translation Editor">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129">
                </path>
            </svg>
        </button>
    </div>

    <!-- Expanded state: Full panel -->
    <div id="translation-editor-expanded" x-show="!collapsed" x-transition
        class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-800 max-w-[90vw]"
        style="width: 640px; height: 70vh; max-height: 70vh; display: flex; flex-direction: column;">
        <div class="flex flex-col flex-1 min-h-0" style="height: 100%;">
            <!-- Header -->
            <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800 shrink-0 bg-gray-50 dark:bg-gray-900/50">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Translation Editor</h3>
                </div>
                <div class="flex items-center gap-3">
                    @if ($activeLocale && !empty($translationStrings))
                        <button type="button" wire:click="saveTranslations('{{ $activeLocale }}')"
                            wire:loading.attr="disabled"
                            class="translation-editor-save-btn inline-flex items-center justify-center gap-x-1 rounded-lg border border-transparent bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none disabled:select-none">
                            <span wire:loading.remove wire:target="saveTranslations">Save</span>
                            <span wire:loading wire:target="saveTranslations">Saving...</span>
                        </button>
                    @endif
                    <button type="button" @click="collapsed = !collapsed"
                        class="inline-flex items-center justify-center rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            @php
                $locales = $this->getLocaleCodes();
            @endphp

            @if (empty($translationStrings))
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center py-4 text-gray-500 dark:text-gray-400 px-6">
                        <p class="text-sm">No translations found on this page.</p>
                    </div>
                </div>
            @else
                <!-- Locale Tabs - Filament Style -->
                @if (!empty($locales))
                    <div class="border-b border-gray-200 dark:border-gray-800 shrink-0 bg-white dark:bg-gray-900">
                        <div class="flex gap-0.5 overflow-x-auto px-4 pt-2"
                            style="scrollbar-width: none; -ms-overflow-style: none;">
                            <style>
                                .translation-tabs::-webkit-scrollbar {
                                    display: none;
                                }
                            </style>
                            <div class="flex gap-0.5 translation-tabs">
                                @foreach ($locales as $localeCode)
                                    <button type="button" wire:click="setActiveLocale('{{ $localeCode }}')"
                                        class="group relative flex items-center gap-x-2 overflow-hidden whitespace-nowrap rounded-t-lg px-4 py-2.5 text-sm font-medium transition
                                        {{ $activeLocale === $localeCode
                                            ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400'
                                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-300' }}">
                                        <span>{{ strtoupper($localeCode) }}</span>
                                        @if ($activeLocale === $localeCode)
                                            <span
                                                class="absolute inset-x-0 bottom-0 h-0.5 bg-primary-600 dark:bg-primary-400"></span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Translation Strings - Scrollable Area -->
                @if ($activeLocale)
                    <div class="flex-1 overflow-y-auto px-6 py-4 min-h-0 bg-white dark:bg-gray-900"
                        style="overflow-y: auto !important;">
                        <div class="space-y-4 pb-2">
                            @foreach ($translationStrings as $index => $string)
                                @php
                                    $group = $string['group'] ?? '';
                                    $key = $string['key'] ?? '';
                                    $translationKey = "{$group}.{$key}.{$activeLocale}";
                                    // Get value and ensure it's always a string, never an array
$value = $editableTranslations[$translationKey] ?? null;
if (is_array($value)) {
    $value = '';
} else {
    $value = (string) ($value ?? '');
                                    }
                                @endphp

                                <div class="space-y-1.5"
                                    wire:key="translation-{{ $group }}-{{ $key }}-{{ $activeLocale }}">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $key }}
                                    </label>
                                    <textarea wire:input="updateTranslationValue('{{ $translationKey }}', $event.target.value)" rows="2"
                                        class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400 disabled:bg-gray-50 disabled:text-gray-500 disabled:dark:bg-gray-800 disabled:dark:text-gray-400 text-sm px-3 py-2 resize-none"
                                        placeholder="Enter translation...">{{ $value }}</textarea>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="flex-1 flex items-center justify-center">
                        <div class="text-center py-4 text-gray-500 dark:text-gray-400 px-6">
                            <p class="text-sm">Please select a locale to edit translations.</p>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
