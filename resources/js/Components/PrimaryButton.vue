<script setup lang="ts">
import { useBranding } from '@/composables/useBranding';

const { primaryColor, primaryColorHover, primaryColorActive } = useBranding();
</script>

<template>
    <button
        class="inline-flex items-center rounded-md border border-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2"
        :style="{
            backgroundColor: primaryColor,
            '--hover-color': primaryColorHover,
            '--active-color': primaryColorActive,
            '--ring-color': primaryColor,
        }"
        @mouseenter="($event.target as HTMLElement).style.backgroundColor = primaryColorHover"
        @mouseleave="($event.target as HTMLElement).style.backgroundColor = primaryColor"
        @mousedown="($event.target as HTMLElement).style.backgroundColor = primaryColorActive"
        @mouseup="($event.target as HTMLElement).style.backgroundColor = primaryColorHover"
    >
        <slot />
    </button>
</template>
