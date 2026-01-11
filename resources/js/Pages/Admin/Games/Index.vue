<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { Game } from '@/types';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useToastStore } from '@/stores/toast';

interface Props {
    games: Game[];
}

defineProps<Props>();

const toast = useToastStore();

const toggleGame = (game: Game) => {
    router.patch(route('admin.games.toggle', { game: game.id }), {}, {
        onSuccess: () => {
            toast.success(game.isActive ? 'Game disabled' : 'Game enabled');
        },
        onError: () => {
            toast.error('Failed to update game');
        },
    });
};

const deleteGame = (game: Game) => {
    if (confirm(`Are you sure you want to delete "${game.name}"? This action cannot be undone.`)) {
        router.delete(route('admin.games.destroy', { game: game.id }), {
            onSuccess: () => {
                toast.success(`Game "${game.name}" deleted`);
            },
            onError: () => {
                toast.error('Failed to delete game');
            },
        });
    }
};
</script>

<template>
    <Head title="Manage Games" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Games</h1>
                <Link :href="route('admin.games.create')">
                    <PrimaryButton>Add Game</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Game
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Players
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="game in games" :key="game.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <span v-if="game.icon" class="text-2xl">{{ game.icon }}</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ game.name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ game.slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ game.minPlayers }}-{{ game.maxPlayers }} players
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="[
                                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                    game.isActive
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-600'
                                ]"
                            >
                                {{ game.isActive ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="flex justify-end gap-2">
                                <Link
                                    :href="route('admin.games.edit', { game: game.id })"
                                    class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="toggleGame(game)"
                                    :class="[
                                        'font-medium',
                                        game.isActive
                                            ? 'text-amber-600 hover:text-amber-800'
                                            : 'text-green-600 hover:text-green-800'
                                    ]"
                                >
                                    {{ game.isActive ? 'Disable' : 'Enable' }}
                                </button>
                                <button
                                    @click="deleteGame(game)"
                                    class="text-red-600 hover:text-red-800 font-medium"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="games.length === 0">
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            No games yet. Add your first game to get started.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
