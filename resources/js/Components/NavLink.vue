<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const { primaryColor } = useBranding();
const isHovered = ref(false);

const baseClasses = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none';

const classes = computed(() =>
    props.active
        ? `${baseClasses} dark:text-white text-gray-900`
        : `${baseClasses} border-transparent text-gray-500 dark:text-gray-400 focus:border-gray-300`,
);

const dynamicStyle = computed(() => {
    if (props.active) {
        return { borderBottomColor: primaryColor.value };
    }
    if (isHovered.value) {
        return { color: primaryColor.value, borderBottomColor: primaryColor.value };
    }
    return {};
});
</script>

<template>
    <Link
        :href="href"
        :class="classes"
        :style="dynamicStyle"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
    >
        <slot />
    </Link>
</template>
