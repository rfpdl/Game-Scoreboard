<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';

const { primaryColor, primaryColorHover } = useBranding();
</script>

<template>
    <GuestLayout>
        <Head title="Registration Closed" />

        <div class="text-center">
            <div class="mb-6">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>

            <h2 class="text-xl font-semibold text-gray-900 mb-2">
                Registration Closed
            </h2>

            <p class="text-gray-600 mb-6">
                New user registration is currently disabled. Please contact an administrator if you need access.
            </p>

            <Link
                :href="route('login')"
                class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-white transition-colors"
                :style="{ backgroundColor: primaryColor }"
                @mouseenter="($event.target as HTMLElement).style.backgroundColor = primaryColorHover"
                @mouseleave="($event.target as HTMLElement).style.backgroundColor = primaryColor"
            >
                Back to Login
            </Link>
        </div>
    </GuestLayout>
</template>
