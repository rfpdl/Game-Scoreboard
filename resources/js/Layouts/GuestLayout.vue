<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';

const { appName, logoUrl, hasLogoImage } = useBranding();
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-50 dark:bg-gray-900 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/" class="flex items-center gap-1">
                <img
                    v-if="hasLogoImage"
                    :src="logoUrl"
                    :alt="appName"
                    class="h-12 w-auto dark:invert"
                />
                <span v-else class="text-4xl font-black tracking-tight text-gray-900 dark:text-white">
                    {{ appName }}
                </span>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white dark:bg-gray-800 px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
