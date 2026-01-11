<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';

const props = defineProps<{
    href: string;
    active?: boolean;
    method?: string;
    as?: string;
}>();

const { primaryColor, primaryColorLight } = useBranding();
const isHovered = ref(false);

const baseClasses = 'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium transition duration-150 ease-in-out focus:outline-none';

const classes = computed(() =>
    props.active
        ? `${baseClasses}`
        : `${baseClasses} border-transparent text-gray-600 dark:text-gray-400`,
);

const dynamicStyle = computed(() => {
    if (props.active) {
        return {
            borderLeftColor: primaryColor.value,
            color: primaryColor.value,
            backgroundColor: primaryColorLight.value
        };
    }
    if (isHovered.value) {
        return { color: primaryColor.value };
    }
    return {};
});
</script>

<template>
    <Link
        :href="href"
        :method="method"
        :as="as"
        :class="classes"
        :style="dynamicStyle"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
    >
        <slot />
    </Link>
</template>
