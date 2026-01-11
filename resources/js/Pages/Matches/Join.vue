<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import MatchCodeInput from '@/Components/molecules/MatchCodeInput';
import Button from '@/Components/atoms/Button';
import { useMatchStore } from '@/stores/match';
import { ref } from 'vue';

const matchStore = useMatchStore();
const code = ref('');
const error = ref<string | null>(null);

const handleComplete = (completedCode: string) => {
    error.value = null;
    matchStore.joinMatch(completedCode);
};

const handleSubmit = () => {
    if (code.value.length === 6) {
        handleComplete(code.value);
    } else {
        error.value = 'Please enter a 6-character code';
    }
};
</script>

<template>
    <Head title="Join Match" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Join Match
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-md sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Enter Match Code
                        </h3>
                        <p class="text-sm text-gray-500">
                            Ask your opponent for the 6-digit code to join their match
                        </p>
                    </div>

                    <MatchCodeInput
                        v-model="code"
                        :error="error || matchStore.error"
                        :disabled="matchStore.isLoading"
                        @complete="handleComplete"
                    />

                    <div class="mt-6">
                        <Button
                            variant="primary"
                            size="lg"
                            :loading="matchStore.isLoading"
                            :disabled="code.length !== 6"
                            class="w-full"
                            @click="handleSubmit"
                        >
                            Join Match
                        </Button>
                    </div>

                    <div class="mt-4 text-center">
                        <Link :href="route('matches.create')" class="text-sm text-indigo-600 hover:text-indigo-500">
                            Or create a new match instead
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
