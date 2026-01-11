<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import type { Game, User } from '@/types';
import Button from '@/Components/atoms/Button';
import Badge from '@/Components/atoms/Badge';
import Input from '@/Components/atoms/Input';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

interface Props {
    games: Game[];
}

const props = defineProps<Props>();
const page = usePage();

const isAdmin = (page.props.auth?.user as User | undefined)?.is_admin ?? false;

// Form state for adding new game (admin only)
const showAddForm = ref(false);
const newGame = ref({
    name: '',
    slug: '',
    description: '',
    icon: '',
    min_players: 2,
    max_players: 2,
});
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const generateSlug = (name: string) => {
    return name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
};

const handleNameChange = (value: string | number) => {
    newGame.value.name = String(value);
    newGame.value.slug = generateSlug(String(value));
};

const submitGame = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.post(route('games.store'), newGame.value, {
        onSuccess: () => {
            showAddForm.value = false;
            newGame.value = {
                name: '',
                slug: '',
                description: '',
                icon: '',
                min_players: 2,
                max_players: 2,
            };
            isSubmitting.value = false;
        },
        onError: (errs) => {
            errors.value = errs;
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Games" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Games
                </h2>
                <Button v-if="isAdmin" variant="primary" size="sm" @click="showAddForm = !showAddForm">
                    {{ showAddForm ? 'Cancel' : 'Add Game' }}
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Add Game Form (Admin Only) -->
                <div v-if="showAddForm && isAdmin" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Game</h3>
                    <form @submit.prevent="submitGame" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Input
                                label="Game Name"
                                :model-value="newGame.name"
                                @update:model-value="handleNameChange"
                                placeholder="e.g., Pool"
                                :error="errors.name"
                            />
                            <Input
                                label="Slug"
                                v-model="newGame.slug"
                                placeholder="e.g., pool"
                                :error="errors.slug"
                            />
                            <Input
                                label="Icon (Emoji)"
                                v-model="newGame.icon"
                                placeholder="e.g., 8"
                                :error="errors.icon"
                            />
                            <Input
                                label="Description"
                                v-model="newGame.description"
                                placeholder="Optional description"
                                :error="errors.description"
                            />
                            <Input
                                label="Min Players"
                                type="number"
                                v-model="newGame.min_players"
                                :error="errors.min_players"
                            />
                            <Input
                                label="Max Players"
                                type="number"
                                v-model="newGame.max_players"
                                :error="errors.max_players"
                            />
                        </div>
                        <div class="flex justify-end">
                            <Button type="submit" variant="primary" :loading="isSubmitting">
                                Add Game
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- Games List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="game in games"
                            :key="game.id"
                            class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <span v-if="game.icon" class="text-3xl">{{ game.icon }}</span>
                                    <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span class="text-gray-500 font-medium">{{ game.name.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ game.name }}</h3>
                                        <p class="text-sm text-gray-500">
                                            {{ game.minPlayers === game.maxPlayers
                                                ? `${game.minPlayers} players`
                                                : `${game.minPlayers}-${game.maxPlayers} players` }}
                                        </p>
                                    </div>
                                </div>
                                <Badge :variant="game.isActive ? 'success' : 'default'" size="sm">
                                    {{ game.isActive ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>
                            <p v-if="game.description" class="mt-3 text-sm text-gray-600">
                                {{ game.description }}
                            </p>
                        </div>
                    </div>

                    <p v-if="games.length === 0" class="text-center text-gray-500 py-8">
                        No games available yet.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
