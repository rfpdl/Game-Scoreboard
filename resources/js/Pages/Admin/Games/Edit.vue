<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import type { Game } from '@/types';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useToastStore } from '@/stores/toast';

interface Props {
    game: Game;
}

const props = defineProps<Props>();
const toast = useToastStore();

const form = useForm({
    name: props.game.name,
    slug: props.game.slug,
    description: props.game.description || '',
    rules: props.game.rules?.join('\n') || '',
    icon: props.game.icon || '',
    min_players: props.game.minPlayers,
    max_players: props.game.maxPlayers,
});

const submit = () => {
    form.put(route('admin.games.update', { game: props.game.id }), {
        onSuccess: () => {
            toast.success('Game updated');
        },
        onError: () => {
            toast.error('Failed to update game');
        },
    });
};
</script>

<template>
    <Head :title="`Edit ${game.name}`" />

    <AdminLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit {{ game.name }}</h1>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-2xl">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="slug" value="Slug" />
                        <TextInput
                            id="slug"
                            v-model="form.slug"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.slug" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="icon" value="Icon (Emoji)" />
                        <TextInput
                            id="icon"
                            v-model="form.icon"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.icon" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="description" value="Description" />
                        <TextInput
                            id="description"
                            v-model="form.description"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="min_players" value="Min Players" />
                        <TextInput
                            id="min_players"
                            type="number"
                            v-model="form.min_players"
                            min="2"
                            max="10"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.min_players" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="max_players" value="Max Players" />
                        <TextInput
                            id="max_players"
                            type="number"
                            v-model="form.max_players"
                            min="2"
                            max="10"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.max_players" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="rules" value="Rules (one per line)" />
                    <textarea
                        id="rules"
                        v-model="form.rules"
                        rows="5"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        placeholder="Players take turns shooting&#10;First to pocket all balls wins&#10;..."
                    ></textarea>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter each rule on a new line. These rules will be shown during matches.</p>
                    <InputError :message="form.errors.rules" class="mt-2" />
                </div>

                <div class="flex gap-4">
                    <PrimaryButton :disabled="form.processing">
                        Save Changes
                    </PrimaryButton>
                    <Link
                        :href="route('admin.games.index')"
                        class="inline-flex items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
