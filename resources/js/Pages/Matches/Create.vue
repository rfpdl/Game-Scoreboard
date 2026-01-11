<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import type { Game } from '@/types';
import MatchCreator, { type CreateMatchParams } from '@/Components/organisms/MatchCreator';
import { useMatchStore } from '@/stores/match';
import { useToastStore } from '@/stores/toast';
import { computed, watch } from 'vue';

interface Props {
    games: Game[];
}

const props = defineProps<Props>();
const matchStore = useMatchStore();
const toast = useToastStore();
const page = usePage();

// Watch for errors and show toast
const errors = computed(() => page.props.errors as Record<string, string>);

watch(errors, (newErrors) => {
    if (newErrors && Object.keys(newErrors).length > 0) {
        // Show first error as toast
        const firstError = Object.values(newErrors)[0];
        if (firstError) {
            toast.error(firstError);
        }
    }
}, { immediate: true });

const handleCreate = (params: CreateMatchParams) => {
    matchStore.createMatch(params);
};
</script>

<template>
    <Head title="Create Match" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Create Match
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <MatchCreator
                    :games="games"
                    :loading="matchStore.isLoading"
                    @create="handleCreate"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
