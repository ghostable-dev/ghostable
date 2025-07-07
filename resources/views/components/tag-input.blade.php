@props(['label'])

<div
    x-data="{
        tags: @entangle($attributes->wire('model')),
        newTag: '',
        addTag() {
            if (this.newTag.trim() !== '' && !this.tags.includes(this.newTag.trim())) {
                this.tags.push(this.newTag.trim());
                this.newTag = '';
            }
        },
        removeTag(index) {
            this.tags.splice(index, 1);
        }
    }"
    class="space-y-2">

    

    <flux:input
        label="{{ $label ?? 'Tags' }}"
        type="text"
        class="flux-input w-full"
        placeholder="Type and press enter..."
        x-model="newTag"
        @keydown.enter.prevent="addTag()"
    />
    
    <flux:card class="flex flex-wrap gap-2">
        <template x-for="(tag, index) in tags" :key="tag">
            <flux:badge>
                <span x-text="tag"></span> <flux:badge.close @click="removeTag(index)"/>
            </flux:badge>

            <flux:badge><span x-text="tag"></span></flux:badge>
            <div class="flex items-center bg-gray-200 px-2 py-1 rounded-full text-sm">
                <span x-text="tag"></span>
                <button
                    type="button"
                    class="ml-1 text-red-500 hover:text-red-700"
                    @click="removeTag(index)"
                >×</button>
            </div>
        </template>
    </flux:card>
</div>