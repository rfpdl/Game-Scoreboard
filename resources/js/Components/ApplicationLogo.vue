<script setup lang="ts">
import { useBranding } from '@/composables/useBranding';

const { appName, logoUrl, hasLogoImage, primaryColor } = useBranding();
</script>

<template>
    <!-- Image logo if provided -->
    <img
        v-if="hasLogoImage"
        :src="logoUrl"
        :alt="appName"
        class="h-8 w-auto dark:invert"
    />
    <!-- Text-based logo fallback -->
    <span v-else class="text-2xl font-black tracking-tight">
        <span class="text-gray-900 dark:text-white">{{ appName }}</span>
    </span>
</template>
