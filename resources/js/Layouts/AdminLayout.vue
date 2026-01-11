<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { useBranding } from '@/composables/useBranding';

const showMobileNav = ref(false);
const page = usePage();
const { appName, primaryColor } = useBranding();

const navItems = [
    { name: 'Dashboard', route: 'admin.dashboard' },
    { name: 'Settings', route: 'admin.settings.index' },
    { name: 'Games', route: 'admin.games.index' },
    { name: 'Matches', route: 'admin.matches.index' },
    { name: 'Users', route: 'admin.users.index' },
    { name: 'Backups', route: 'admin.backups.index' },
];

const isActive = (routeName: string) => {
    const current = route().current();
    return current === routeName || current?.startsWith(routeName.replace('.index', '.'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Navigation -->
        <nav class="bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <Link :href="route('admin.dashboard')" class="flex-shrink-0">
                            <span class="text-white font-bold text-lg">Admin Panel</span>
                        </Link>
                        <div class="hidden md:block ml-10">
                            <div class="flex items-baseline space-x-4">
                                <Link
                                    v-for="item in navItems"
                                    :key="item.route"
                                    :href="route(item.route)"
                                    :class="[
                                        isActive(item.route)
                                            ? 'bg-gray-900 text-white'
                                            : 'text-gray-300 hover:bg-gray-700 hover:text-white',
                                        'rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    ]"
                                >
                                    {{ item.name }}
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="hidden md:block">
                        <div class="flex items-center gap-4">
                            <Link
                                :href="route('dashboard')"
                                class="text-gray-300 hover:text-white text-sm transition-colors"
                            >
                                Back to App
                            </Link>
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="text-gray-300 hover:text-white flex items-center gap-2">
                                        <span>{{ $page.props.auth.user.name }}</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        Profile
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button
                            @click="showMobileNav = !showMobileNav"
                            class="text-gray-400 hover:text-white"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    v-if="!showMobileNav"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    v-else
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="showMobileNav" class="md:hidden">
                <div class="space-y-1 px-2 pb-3 pt-2">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        :class="[
                            isActive(item.route)
                                ? 'bg-gray-900 text-white'
                                : 'text-gray-300 hover:bg-gray-700 hover:text-white',
                            'block rounded-md px-3 py-2 text-base font-medium',
                        ]"
                    >
                        {{ item.name }}
                    </Link>
                    <Link
                        :href="route('dashboard')"
                        class="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white"
                    >
                        Back to App
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Header -->
        <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <div class="mx-auto max-w-7xl py-6 px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>
