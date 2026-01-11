<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';
import { computed } from 'vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const { appName, logoUrl, hasLogoImage, primaryColor, primaryColorHover, primaryColorLight } = useBranding();
const page = usePage();

// Registration is allowed only if route exists AND setting is enabled
const canRegisterComputed = computed(() => {
    return page.props.registrationEnabled as boolean;
});

// Generate lighter shade for backgrounds
const primaryColorLighter = computed(() => {
    // Convert hex to RGB and create a very light version
    const hex = primaryColor.value.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    // Mix with white (90% white)
    const mixR = Math.round(r * 0.1 + 255 * 0.9);
    const mixG = Math.round(g * 0.1 + 255 * 0.9);
    const mixB = Math.round(b * 0.1 + 255 * 0.9);
    return `rgb(${mixR}, ${mixG}, ${mixB})`;
});

// Generate darker shade for dark mode backgrounds
const primaryColorDarker = computed(() => {
    const hex = primaryColor.value.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    // Darken by mixing with a very dark color
    const mixR = Math.round(r * 0.3 + 30 * 0.7);
    const mixG = Math.round(g * 0.3 + 30 * 0.7);
    const mixB = Math.round(b * 0.3 + 30 * 0.7);
    return `rgb(${mixR}, ${mixG}, ${mixB})`;
});

// Check if dark mode is active
const isDarkMode = computed(() => {
    if (typeof document !== 'undefined') {
        return document.documentElement.classList.contains('dark');
    }
    return false;
});
</script>

<template>
    <Head :title="`${appName} - Track Your Game Matches`" />

    <div class="min-h-screen bg-white dark:bg-gray-900">
        <!-- Navigation -->
        <nav class="relative z-10 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <div class="flex items-center gap-2">
                    <!-- Logo -->
                    <img
                        v-if="hasLogoImage"
                        :src="logoUrl"
                        :alt="appName"
                        class="h-8 w-auto"
                    />
                    <span v-else class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                        {{ appName }}
                    </span>
                </div>

                <div v-if="canLogin" class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="route('dashboard')"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-white transition"
                            :style="{ backgroundColor: primaryColor }"
                            @mouseenter="($event.target as HTMLElement).style.backgroundColor = primaryColorHover"
                            @mouseleave="($event.target as HTMLElement).style.backgroundColor = primaryColor"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm font-medium text-gray-600 dark:text-gray-300 transition hover:text-gray-900 dark:hover:text-white"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister && canRegisterComputed"
                            :href="route('register')"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-white transition"
                            :style="{ backgroundColor: primaryColor }"
                            @mouseenter="($event.target as HTMLElement).style.backgroundColor = primaryColorHover"
                            @mouseleave="($event.target as HTMLElement).style.backgroundColor = primaryColor"
                        >
                            Sign up
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="relative">
            <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:py-40">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-6xl lg:text-7xl">
                        The <span :style="{ color: primaryColor }">Ultimate</span> Game
                        <br class="hidden sm:block" />
                        Scoreboard
                    </h1>
                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-gray-600 dark:text-gray-300">
                        Track matches, compete on leaderboards, and prove you're the best.
                        Every score is <strong>cryptographically secured</strong> and impossible to alter.
                    </p>

                    <!-- View Live Leaderboard - Prominent -->
                    <div class="mt-10">
                        <Link
                            :href="route('leaderboard.index')"
                            class="group inline-flex items-center gap-3 rounded-2xl px-8 py-5 text-lg font-bold text-white shadow-xl transition transform hover:scale-105 hover:shadow-2xl"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                            </span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 00-.584.859 6.753 6.753 0 006.138 5.6 6.73 6.73 0 002.743 1.346A6.707 6.707 0 019.279 15H8.54c-1.036 0-1.875.84-1.875 1.875V19.5h-.75a.75.75 0 000 1.5h12.17a.75.75 0 000-1.5h-.75v-2.625c0-1.036-.84-1.875-1.875-1.875h-.739a6.707 6.707 0 01-1.112-3.173 6.73 6.73 0 002.743-1.347 6.753 6.753 0 006.139-5.6.75.75 0 00-.585-.858 47.077 47.077 0 00-3.07-.543V2.62a.75.75 0 00-.658-.744 49.22 49.22 0 00-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 00-.657.744zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 013.16 5.337a45.6 45.6 0 012.006-.343v.256zm13.5 0v-.256c.674.1 1.343.214 2.006.343a5.265 5.265 0 01-2.863 3.207 6.72 6.72 0 00.857-3.294z" clip-rule="evenodd" />
                            </svg>
                            View Live Leaderboard
                            <svg class="h-5 w-5 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Features Grid -->
                <div class="mx-auto mt-24 max-w-4xl">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Everything you need to compete</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">Professional-grade features for casual games</p>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <!-- Feature 1: ELO Rankings -->
                        <div class="rounded-2xl p-6 ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 dark:bg-gray-800">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl text-white" :style="{ backgroundColor: primaryColor }">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ELO Rankings</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                The same rating system used by chess masters. Beat stronger opponents, climb faster.
                            </p>
                        </div>

                        <!-- Feature 2: Multiple Games -->
                        <div class="rounded-2xl p-6 ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 dark:bg-gray-800">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl text-white" :style="{ backgroundColor: primaryColor }">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Multiple Games</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Pool, darts, foosball, backgammon - track them all with separate leaderboards for each.
                            </p>
                        </div>

                        <!-- Feature 3: Quick Match Codes -->
                        <div class="rounded-2xl p-6 ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 dark:bg-gray-800">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl text-white" :style="{ backgroundColor: primaryColor }">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Match Codes</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Generate a 6-character code, share it, and start playing in seconds. No accounts required for opponents.
                            </p>
                        </div>

                        <!-- Feature 4: Public Leaderboards -->
                        <div class="rounded-2xl p-6 ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 dark:bg-gray-800">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl text-white" :style="{ backgroundColor: primaryColor }">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 00-.584.859 6.753 6.753 0 006.138 5.6 6.73 6.73 0 002.743 1.346A6.707 6.707 0 019.279 15H8.54c-1.036 0-1.875.84-1.875 1.875V19.5h-.75a.75.75 0 000 1.5h12.17a.75.75 0 000-1.5h-.75v-2.625c0-1.036-.84-1.875-1.875-1.875h-.739a6.707 6.707 0 01-1.112-3.173 6.73 6.73 0 002.743-1.347 6.753 6.753 0 006.139-5.6.75.75 0 00-.585-.858 47.077 47.077 0 00-3.07-.543V2.62a.75.75 0 00-.658-.744 49.22 49.22 0 00-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 00-.657.744zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 013.16 5.337a45.6 45.6 0 012.006-.343v.256zm13.5 0v-.256c.674.1 1.343.214 2.006.343a5.265 5.265 0 01-2.863 3.207 6.72 6.72 0 00.857-3.294z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Public Leaderboards</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Share your ranking with anyone. No login required to view the leaderboards.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- CTA Section -->
                <div class="mx-auto mt-24 max-w-3xl text-center">
                    <div class="rounded-3xl px-8 py-16 shadow-2xl" :style="{ backgroundColor: primaryColor }">
                        <h2 class="text-3xl font-bold text-white">Ready to prove yourself?</h2>
                        <p class="mt-4 text-lg text-white/80">
                            Join now and start climbing the leaderboard.
                        </p>
                        <div class="mt-8">
                            <Link
                                v-if="canRegister && canRegisterComputed && !$page.props.auth.user"
                                :href="route('register')"
                                class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold shadow-lg transition transform hover:scale-105"
                                :style="{ color: primaryColor }"
                            >
                                Get Started
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <Link
                                v-else-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold shadow-lg transition transform hover:scale-105"
                                :style="{ color: primaryColor }"
                            >
                                Go to Dashboard
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-100 dark:border-gray-800 py-8">
            <div class="mx-auto max-w-7xl px-6 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ appName }}
                </p>
            </div>
        </footer>
    </div>
</template>
