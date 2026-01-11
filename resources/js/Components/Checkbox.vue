<script setup lang="ts">
import { computed } from 'vue';
import { useBranding } from '@/composables/useBranding';

const emit = defineEmits(['update:checked']);

const props = defineProps<{
    checked: boolean;
    value?: any;
}>();

const { primaryColor } = useBranding();

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="border border-gray-300 rounded shadow-sm"
        :style="{
            '--tw-ring-color': primaryColor,
            accentColor: primaryColor,
        }"
    />
</template>
