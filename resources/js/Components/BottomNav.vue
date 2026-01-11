<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';
import { ref, onMounted, onUnmounted } from 'vue';

const { primaryColor, primaryColorLight } = useBranding();
const user = usePage().props.auth.user;

// Track dark mode
const isDark = ref(false);

const updateDarkMode = () => {
    isDark.value = document.documentElement.classList.contains('dark');
};

onMounted(() => {
    updateDarkMode();
    const observer = new MutationObserver(updateDarkMode);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    onUnmounted(() => observer.disconnect());
});

const inactiveBg = () => isDark.value ? '#374151' : '#f3f4f6'; // gray-700 : gray-100
const inactiveColor = () => isDark.value ? '#9ca3af' : '#4b5563'; // gray-400 : gray-600
const inactiveTextColor = () => isDark.value ? '#9ca3af' : '#6b7280'; // gray-400 : gray-500
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 pb-safe z-50 sm:hidden">
        <div class="flex items-center justify-around px-2 pt-2 pb-3">
            <!-- Home -->
            <Link
                :href="route('dashboard')"
                class="flex flex-col items-center justify-center"
            >
                <div
                    class="w-11 h-11 rounded-xl flex items-center justify-center transition-all active:scale-95"
                    :style="{
                        backgroundColor: route().current('dashboard') ? primaryColorLight : inactiveBg(),
                        color: route().current('dashboard') ? primaryColor : inactiveColor()
                    }"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <span
                    class="text-[10px] mt-1 font-medium"
                    :style="{ color: route().current('dashboard') ? primaryColor : inactiveTextColor() }"
                >
                    Home
                </span>
            </Link>

            <!-- Create Match -->
            <Link
                :href="route('matches.create')"
                class="flex flex-col items-center justify-center"
            >
                <div
                    class="w-11 h-11 rounded-xl flex items-center justify-center transition-all active:scale-95"
                    :style="{
                        backgroundColor: route().current('matches.create') ? primaryColorLight : inactiveBg(),
                        color: route().current('matches.create') ? primaryColor : inactiveColor()
                    }"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <span
                    class="text-[10px] mt-1 font-medium"
                    :style="{ color: route().current('matches.create') ? primaryColor : inactiveTextColor() }"
                >
                    Create
                </span>
            </Link>

            <!-- Leaderboard (Prominent) -->
            <Link
                :href="route('leaderboard.index')"
                class="flex flex-col items-center justify-center"
            >
                <div
                    class="w-12 h-12 rounded-full flex items-center justify-center text-white shadow-lg transform hover:scale-105 active:scale-95 transition-transform btn-primary"
                >
                    <!-- Trophy Icon -->
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 00-.584.859 6.753 6.753 0 006.138 5.6 6.73 6.73 0 002.743 1.346A6.707 6.707 0 019.279 15H8.54c-1.036 0-1.875.84-1.875 1.875V19.5h-.75a.75.75 0 000 1.5h12.17a.75.75 0 000-1.5h-.75v-2.625c0-1.036-.84-1.875-1.875-1.875h-.739a6.707 6.707 0 01-1.112-3.173 6.73 6.73 0 002.743-1.347 6.753 6.753 0 006.139-5.6.75.75 0 00-.585-.858 47.077 47.077 0 00-3.07-.543V2.62a.75.75 0 00-.658-.744 49.22 49.22 0 00-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 00-.657.744zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 013.16 5.337a45.6 45.6 0 012.006-.343v.256zm13.5 0v-.256c.674.1 1.343.214 2.006.343a5.265 5.265 0 01-2.863 3.207 6.72 6.72 0 00.857-3.294z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span
                    class="text-[10px] mt-1 font-semibold"
                    :style="{ color: primaryColor }"
                >Leaderboard</span>
            </Link>

            <!-- Join Match -->
            <Link
                :href="route('matches.join')"
                class="flex flex-col items-center justify-center"
            >
                <div
                    class="w-11 h-11 rounded-xl flex items-center justify-center transition-all active:scale-95"
                    :style="{
                        backgroundColor: route().current('matches.join') ? primaryColorLight : inactiveBg(),
                        color: route().current('matches.join') ? primaryColor : inactiveColor()
                    }"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <span
                    class="text-[10px] mt-1 font-medium"
                    :style="{ color: route().current('matches.join') ? primaryColor : inactiveTextColor() }"
                >
                    Join
                </span>
            </Link>

            <!-- Profile -->
            <Link
                :href="route('profile.edit')"
                class="flex flex-col items-center justify-center"
            >
                <div
                    class="w-11 h-11 rounded-xl flex items-center justify-center transition-all active:scale-95 overflow-hidden"
                    :style="{
                        backgroundColor: route().current('profile.edit') ? primaryColorLight : inactiveBg(),
                        color: route().current('profile.edit') ? primaryColor : inactiveColor()
                    }"
                >
                    <img
                        v-if="user.avatar"
                        :src="`/storage/${user.avatar}`"
                        class="w-full h-full object-cover"
                        :alt="user.name"
                    />
                    <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <span
                    class="text-[10px] mt-1 font-medium"
                    :style="{ color: route().current('profile.edit') ? primaryColor : inactiveTextColor() }"
                >
                    Profile
                </span>
            </Link>
        </div>
    </nav>
</template>

<style scoped>
/* Safe area padding for devices with home indicator */
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom, 0);
}
</style>
