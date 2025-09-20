<div>
    @if($showModal)
    <!-- Modal Backdrop -->
    <div 
        class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm transition-opacity duration-300"
        wire:click="closeModal"
    >
        <!-- Modal Container -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Modal Content -->
            <div 
                class="{{ config('livewire-translations.ui.modal.size', 'max-w-4xl') }} w-full bg-white dark:bg-gray-800 rounded-2xl shadow-2xl transform transition-all duration-300 scale-100"
                wire:click.stop
                x-data="{ currentTab: @entangle('currentLanguage') }"
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                Manage Translations
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Configure content for multiple languages
                            </p>
                        </div>
                    </div>
                    <button 
                        wire:click="closeModal"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Alert Messages -->
                    @if(session()->has('message'))
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-green-800 dark:text-green-200 font-medium">{{ session('message') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(session()->has('error'))
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <p class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Language Tabs -->
                    <div class="mb-8">
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <nav class="flex space-x-8 overflow-x-auto" aria-label="Tabs">
                                @foreach($availableLanguages as $language)
                                <button 
                                    wire:click="switchLanguage('{{ $language['language_code'] }}')"
                                    class="relative whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $currentLanguage === $language['language_code'] 
                                        ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                                >
                                    <span class="flex items-center gap-2">
                                        {{ $language['native_name'] ?: $language['name'] }}
                                        
                                        @if($this->translationStatus[$language['language_code']] ?? false)
                                            <span class="inline-flex items-center justify-center w-2 h-2 bg-green-500 rounded-full">
                                                <span class="sr-only">Translated</span>
                                            </span>
                                        @endif
                                    </span>
                                </button>
                                @endforeach
                            </nav>
                        </div>
                    </div>

                    <!-- Translation Form -->
                    <form wire:submit.prevent="saveTranslation" class="space-y-6">
                        @foreach($translatableFields as $field)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ str_replace('_', ' ', Str::title($field)) }}
                                @if($model->getOriginal($field))
                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                        (Original: {{ Str::limit($model->getOriginal($field), 40) }})
                                    </span>
                                @endif
                            </label>
                            
                            @if(in_array($field, ['description', 'content', 'summary', 'excerpt']))
                                <textarea 
                                    wire:model="translations.{{ $currentLanguage }}.{{ $field }}"
                                    rows="4"
                                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white resize-none transition-colors"
                                    placeholder="Enter {{ str_replace('_', ' ', $field) }} in {{ collect($availableLanguages)->firstWhere('language_code', $currentLanguage)['name'] ?? $currentLanguage }}"
                                ></textarea>
                            @else
                                <input 
                                    type="text"
                                    wire:model="translations.{{ $currentLanguage }}.{{ $field }}"
                                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors"
                                    placeholder="Enter {{ str_replace('_', ' ', $field) }} in {{ collect($availableLanguages)->firstWhere('language_code', $currentLanguage)['name'] ?? $currentLanguage }}"
                                />
                            @endif
                            
                            @error("translations.{$currentLanguage}.{$field}")
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        @endforeach

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                @if($this->translationStatus[$currentLanguage] ?? false)
                                    <button 
                                        type="button"
                                        wire:click="deleteTranslation"
                                        wire:confirm="Are you sure you want to delete this translation?"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 focus:ring-4 focus:ring-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/30 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Delete Translation
                                    </button>
                                @endif
                            </div>
                            
                            <button 
                                type="submit" 
                                class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                </svg>
                                Save Translation
                            </button>
                        </div>
                    </form>

                    <!-- Translation Status Summary -->
                    <div class="mt-8 p-6 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Translation Status</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            @foreach($availableLanguages as $language)
                            <div class="flex items-center gap-2">
                                @if($this->translationStatus[$language['language_code']] ?? false)
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                @else
                                    <div class="w-3 h-3 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                                @endif
                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $language['name'] }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Translation Button (can be included in your views) -->
    @if(!$showModal && $model)
    <button 
        type="button" 
        wire:click="openModal"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 focus:ring-4 focus:ring-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800 dark:hover:bg-blue-900/30 transition-colors"
        title="Manage Translations"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
        </svg>
        Translations
    </button>
    @endif
</div>
