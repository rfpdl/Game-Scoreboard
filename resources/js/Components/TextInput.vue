<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useBranding } from '@/composables/useBranding';

const model = defineModel<string>({ required: true });

const input = ref<HTMLInputElement | null>(null);
const { primaryColor } = useBranding();

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        class="border border-gray-300 rounded-md shadow-sm focus:ring-1 px-3 py-2"
        :style="{
            '--tw-ring-color': primaryColor,
        }"
        v-model="model"
        ref="input"
        @focus="($event.target as HTMLInputElement).style.borderColor = primaryColor"
        @blur="($event.target as HTMLInputElement).style.borderColor = '#d1d5db'"
    />
</template>
