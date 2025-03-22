<div>
    <div
        class="w-full h-full flex flex-col"
        x-load
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('flowforge', package: 'relaticle/flowforge'))]"
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
        x-data="flowforge({
            state: {
                statusField: '{{ $config['statusField'] }}',
            }
        })"
    >
        <!-- Board Content -->
        <div class="flex flex-row h-full w-full overflow-x-auto overflow-y-hidden py-4 px-2 gap-4">
            @foreach($columns as $columnId => $column)
                <div
                    class="flex flex-col h-full min-w-64 w-64 bg-gray-100 dark:bg-gray-800 rounded-xl shadow-sm p-2"
                    x-data="{
                        columnId: '{{ $columnId }}',
                        items: @js($column['items']),
                        isOver: false
                    }"
                    x-on:dragover.prevent="isOver = true; $event.dataTransfer.dropEffect = 'move';"
                    x-on:dragleave.prevent="isOver = false"
                    x-on:drop.prevent="
                    isOver = false;
                    const data = JSON.parse($event.dataTransfer.getData('text/plain'));
                    $wire.updateStatus(data.id, columnId);
                "
                    :class="{ 'border-2 border-primary-500 dark:border-primary-400': isOver }"
                >
                    <!-- Column Header -->
                    <div class="flex items-center justify-between p-2 mb-2 font-medium">
                        <h3 class="text-gray-900 dark:text-white">{{ $column['name'] }}</h3>
                        <button
                            type="button"
                            class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                            x-on:click="openCreateModal('{{ $columnId }}')"
                        >
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                 fill="currentColor">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Column Content -->
                    <div class="flex-1 overflow-y-auto overflow-x-hidden p-1">
                        <template x-for="(card, index) in items" :key="card.id">
                            <div
                                class="bg-white dark:bg-gray-700 rounded-md shadow-sm mb-2 p-3 cursor-pointer select-none transition-all duration-200 hover:shadow-md hover:-translate-y-[2px]"
                                :class="{
                                'border-l-4 border-red-500': card.priority === 'high',
                                'border-l-4 border-yellow-500': card.priority === 'medium',
                                'border-l-4 border-green-500': card.priority === 'low',
                                'border-l-4 border-gray-300 dark:border-gray-600': !card.priority
                            }"
                                draggable="true"
                                x-on:dragstart="
                                $event.dataTransfer.setData('text/plain', JSON.stringify({
                                    id: card.id,
                                    sourceColumn: columnId
                                }));
                                $event.target.classList.add('opacity-50');
                            "
                                x-on:dragend="$event.target.classList.remove('opacity-50')"
                                x-on:click.stop="openEditModal(card, columnId)"
                            >
                                <div class="text-sm font-medium text-gray-900 dark:text-white mb-1"
                                     x-text="card.title"></div>

                                <template x-if="card.description">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2"
                                         x-text="card.description"></div>
                                </template>

                                <div class="flex flex-wrap gap-2 mt-2">
                                    <template x-for="(value, key) in card" :key="key">
                                        <template x-if="!['id', 'title', 'description'].includes(key) && value">
                                            <div
                                                class="inline-flex items-center text-xs px-2 py-0.5 rounded-full"
                                                :class="{
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': key === 'category',
                                                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300': key === 'assignee',
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': key === 'due_date',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300': !['category', 'assignee', 'due_date'].includes(key)
                                            }"
                                            >
                                                <span x-text="value"></span>
                                            </div>
                                        </template>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Create Card Modal -->
        <x-filament::modal
            id="create-card-modal"
            :heading="__('Create New Card')"
            width="md"
        >
            <form x-on:submit="submitCreateForm">
                <!-- Title Field -->
                <label for="">Title</label>
                <x-filament::input.wrapper label="{{ __('Title') }}">
                    <x-filament::input
                        id="title"
                        type="text"
                        x-model="formData['{{ $config['titleAttribute'] }}']"
                        required
                    />
                </x-filament::input.wrapper>

                <!-- Description Field (if available) -->
                @if($config['descriptionAttribute'])
                    <x-filament::input.wrapper label="{{ __('Description') }}" class="mt-4">
                        <x-filament::input
                            id="description"
                            type="text"
                            x-model="formData['{{ $config['descriptionAttribute'] }}']"
                            multiline
                            rows="3"
                        />
                    </x-filament::input.wrapper>
                @endif

                <!-- Status Field -->
                <x-filament::input.wrapper label="{{ __('Status') }}" class="mt-4">
                    <x-filament::input.select
                        id="status"
                        x-model="formData['{{ $config['statusField'] }}']"
                    >
                        @foreach($config['statusValues'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <!-- Additional Card Attributes -->
                @foreach($config['cardAttributes'] as $attribute)
                    <x-filament::input.wrapper label="{{ __(ucfirst(str_replace('_', ' ', $attribute))) }}"
                                               class="mt-4">
                        <x-filament::input
                            id="{{ $attribute }}"
                            type="text"
                            x-model="formData['{{ $attribute }}']"
                        />
                    </x-filament::input.wrapper>
                @endforeach

                <x-slot name="footer">
                    <div class="flex justify-end gap-x-3">
                        <x-filament::button
                            type="button"
                            color="gray"
                            x-on:click="showCreateModal = false"
                        >
                            {{ __('Cancel') }}
                        </x-filament::button>

                        <x-filament::button
                            type="submit"
                            x-on:click="submitCreateForm"
                        >
                            {{ __('Create') }}
                        </x-filament::button>
                    </div>
                </x-slot>
            </form>
        </x-filament::modal>

        <!-- Edit Card Modal -->
        <x-filament::modal
            id="edit-card-modal"
            :heading="__('Edit Card')"
            width="md"
        >
            <form x-on:submit.prevent="submitEditForm">
                <!-- Title Field -->
                <x-filament::input.wrapper label="{{ __('Title') }}">
                    <x-filament::input
                        id="edit-title"
                        type="text"
                        x-model="formData['{{ $config['titleAttribute'] }}']"
                        required
                    />
                </x-filament::input.wrapper>

                <!-- Description Field (if available) -->
                @if($config['descriptionAttribute'])
                    <x-filament::input.wrapper label="{{ __('Description') }}" class="mt-4">
                        <x-filament::input
                            id="edit-description"
                            type="text"
                            x-model="formData['{{ $config['descriptionAttribute'] }}']"
                            multiline
                            rows="3"
                        />
                    </x-filament::input.wrapper>
                @endif

                <!-- Status Field -->
                <x-filament::input.wrapper label="{{ __('Status') }}" class="mt-4">
                    <x-filament::input.select
                        id="edit-status"
                        x-model="formData['{{ $config['statusField'] }}']"
                    >
                        @foreach($config['statusValues'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <!-- Additional Card Attributes -->
                @foreach($config['cardAttributes'] as $attribute)
                    <x-filament::input.wrapper label="{{ __(ucfirst(str_replace('_', ' ', $attribute))) }}"
                                               class="mt-4">
                        <x-filament::input
                            id="edit-{{ $attribute }}"
                            type="text"
                            x-model="formData['{{ $attribute }}']"
                        />
                    </x-filament::input.wrapper>
                @endforeach

                <x-slot name="footer">
                    <div class="flex justify-between">
                        <x-filament::button
                            type="button"
                            color="danger"
                            x-on:click="deleteCard()"
                        >
                            <x-filament::icon
                                name="heroicon-m-trash"
                                class="w-4 h-4 mr-1"
                            />
                            {{ __('Delete') }}
                        </x-filament::button>

                        <div class="flex items-center gap-x-3">
                            <x-filament::button
                                type="button"
                                color="gray"
                                x-on:click="showEditModal = false"
                            >
                                {{ __('Cancel') }}
                            </x-filament::button>

                            <x-filament::button
                                type="submit"
                                x-on:click="submitEditForm"
                            >
                                {{ __('Update') }}
                            </x-filament::button>
                        </div>
                    </div>
                </x-slot>
            </form>
        </x-filament::modal>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

</div>
