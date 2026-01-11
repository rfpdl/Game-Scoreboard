<script setup lang="ts">
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';

const props = defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    stats: {
        matchesPlayed: number;
        matchesWon: number;
        matchesLost: number;
        winRate: number;
    };
    recentMatches: Array<{
        id: number;
        uuid: string;
        game: { name: string; icon: string };
        opponent: { name: string; avatar: string | null } | null;
        result: string;
        rating_change: number | null;
        played_at: string | null;
    }>;
}>();

const user = usePage().props.auth.user;
const { primaryColor } = useBranding();

const activeTab = ref<'matches' | 'settings'>('matches');
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <!-- Profile Header -->
            <div class="bg-white dark:bg-gray-800 shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6">
                        <!-- Avatar -->
                        <div class="relative">
                            <div
                                v-if="user.avatar"
                                class="w-24 h-24 sm:w-32 sm:h-32 rounded-full overflow-hidden ring-4 ring-white dark:ring-gray-700 shadow-lg"
                            >
                                <img
                                    :src="`/storage/${user.avatar}`"
                                    class="w-full h-full object-cover"
                                    :alt="user.name"
                                />
                            </div>
                            <div
                                v-else
                                class="w-24 h-24 sm:w-32 sm:h-32 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center ring-4 ring-white dark:ring-gray-700 shadow-lg"
                            >
                                <svg class="w-12 h-12 sm:w-16 sm:h-16 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="flex-1 text-center sm:text-left">
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                {{ user.name }}
                            </h1>
                            <p class="text-gray-500 dark:text-gray-400">{{ user.email }}</p>

                            <!-- Stats -->
                            <div class="flex justify-center sm:justify-start gap-6 mt-4">
                                <div class="text-center">
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.matchesPlayed }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Matches</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ stats.matchesWon }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Wins</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ stats.matchesLost }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Losses</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold" :style="{ color: primaryColor }">{{ stats.winRate }}%</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Win Rate</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex border-b border-gray-200 dark:border-gray-700 mt-6 -mb-px">
                        <button
                            @click="activeTab = 'matches'"
                            class="px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                            :class="activeTab === 'matches'
                                ? 'border-current text-gray-900 dark:text-white'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                            :style="activeTab === 'matches' ? { borderColor: primaryColor, color: primaryColor } : {}"
                        >
                            Recent Matches
                        </button>
                        <button
                            @click="activeTab = 'settings'"
                            class="px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                            :class="activeTab === 'settings'
                                ? 'border-current text-gray-900 dark:text-white'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                            :style="activeTab === 'settings' ? { borderColor: primaryColor, color: primaryColor } : {}"
                        >
                            Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <!-- Recent Matches Tab -->
                <div v-if="activeTab === 'matches'">
                    <div v-if="recentMatches.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No matches yet</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating or joining a match.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('matches.create')"
                                class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                Create Match
                            </Link>
                        </div>
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="match in recentMatches"
                            :key="match.id"
                            :href="route('matches.show', match.uuid)"
                            class="block bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <!-- Game Icon -->
                                    <span class="text-2xl">{{ match.game.icon }}</span>

                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ match.game.name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            vs {{ match.opponent?.name || 'Unknown' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="match.result === 'win'
                                            ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400'
                                            : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400'"
                                    >
                                        {{ match.result === 'win' ? 'Won' : 'Lost' }}
                                        <span
                                            v-if="match.rating_change"
                                            class="ml-1"
                                        >
                                            ({{ match.rating_change > 0 ? '+' : '' }}{{ match.rating_change }})
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ match.played_at }}
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div v-if="activeTab === 'settings'" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-xl"
                        />
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm class="max-w-xl" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
