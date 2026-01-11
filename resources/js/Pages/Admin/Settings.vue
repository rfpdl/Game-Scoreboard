<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useToastStore } from '@/stores/toast';
import { useBranding } from '@/composables/useBranding';

const { primaryColor } = useBranding();

interface Props {
    settings: {
        appName: string;
        primaryColor: string;
        logoUrl: string | null;
        registrationEnabled: boolean;
        colorMode: 'light' | 'dark' | 'system';
        streakMultiplierEnabled: boolean;
    };
}

const props = defineProps<Props>();

const form = useForm({
    appName: props.settings.appName,
    primaryColor: props.settings.primaryColor,
    registrationEnabled: props.settings.registrationEnabled,
    colorMode: props.settings.colorMode,
    streakMultiplierEnabled: props.settings.streakMultiplierEnabled,
});

const logoPreview = ref<string | null>(props.settings.logoUrl);
const logoUploading = ref(false);
const toast = useToastStore();

// Preset color swatches
const presetColors = [
    '#f97316', // Orange
    '#ef4444', // Red
    '#ec4899', // Pink
    '#8b5cf6', // Purple
    '#3b82f6', // Blue
    '#06b6d4', // Cyan
    '#10b981', // Green
    '#84cc16', // Lime
    '#eab308', // Yellow
    '#78716c', // Stone
];

const submit = () => {
    form.put(route('admin.settings.update'), {
        onSuccess: () => {
            toast.success('Settings saved');
        },
        onError: () => {
            toast.error('Failed to save settings');
        },
    });
};

const handleLogoUpload = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        logoUploading.value = true;

        const formData = new FormData();
        formData.append('logo', file);

        router.post(route('admin.settings.logo'), formData, {
            preserveState: true,
            onSuccess: () => {
                logoPreview.value = URL.createObjectURL(file);
                toast.success('Logo uploaded');
            },
            onError: () => {
                toast.error('Failed to upload logo');
            },
            onFinish: () => {
                logoUploading.value = false;
            },
        });
    }
};

const removeLogo = () => {
    router.delete(route('admin.settings.logo.remove'), {
        preserveState: true,
        onSuccess: () => {
            logoPreview.value = null;
            toast.success('Logo removed');
        },
        onError: () => {
            toast.error('Failed to remove logo');
        },
    });
};
</script>

<template>
    <Head title="Admin Settings" />

    <AdminLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-2xl">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- App Name -->
                <div>
                    <InputLabel for="appName" value="Application Name" />
                    <TextInput
                        id="appName"
                        v-model="form.appName"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.appName" class="mt-2" />
                </div>

                <!-- Primary Color -->
                <div>
                    <InputLabel for="primaryColor" value="Primary Color" />

                    <!-- Preset swatches -->
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="color in presetColors"
                            :key="color"
                            type="button"
                            @click="form.primaryColor = color"
                            class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110"
                            :class="form.primaryColor === color ? 'border-gray-800 scale-110' : 'border-transparent'"
                            :style="{ backgroundColor: color }"
                        />
                    </div>

                    <!-- Custom color picker -->
                    <div class="mt-3 flex items-center gap-3">
                        <input
                            id="primaryColor"
                            type="color"
                            v-model="form.primaryColor"
                            @change="($event.target as HTMLInputElement).blur()"
                            class="h-10 w-14 rounded-lg border border-gray-300 cursor-pointer"
                        />
                        <TextInput
                            v-model="form.primaryColor"
                            class="flex-1"
                        />
                    </div>
                    <InputError :message="form.errors.primaryColor" class="mt-2" />
                </div>

                <!-- Logo -->
                <div>
                    <InputLabel value="Logo" />
                    <div v-if="logoPreview" class="mt-2 flex items-center gap-4">
                        <img :src="logoPreview" alt="Logo" class="h-12" />
                        <button
                            @click="removeLogo"
                            type="button"
                            class="text-sm text-red-600 hover:text-red-800"
                        >
                            Remove
                        </button>
                    </div>
                    <label
                        v-else
                        class="mt-2 block w-full border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center cursor-pointer hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
                        :class="{ 'opacity-50': logoUploading }"
                    >
                        <input
                            type="file"
                            accept="image/*,.svg"
                            @change="handleLogoUpload"
                            class="hidden"
                            :disabled="logoUploading"
                        />
                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ logoUploading ? 'Uploading...' : 'Click to upload logo (PNG, JPG, SVG)' }}
                        </span>
                    </label>
                </div>

                <!-- Registration Toggle -->
                <div class="border-t pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <InputLabel value="User Registration" />
                            <p class="text-sm text-gray-500 mt-1">
                                {{ form.registrationEnabled ? 'New users can create accounts' : 'Registration is closed' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="form.registrationEnabled = !form.registrationEnabled"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :style="{ backgroundColor: form.registrationEnabled ? primaryColor : '#d1d5db' }"
                        >
                            <span
                                :class="[
                                    form.registrationEnabled ? 'translate-x-5' : 'translate-x-0',
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                ]"
                            />
                        </button>
                    </div>
                </div>

                <!-- Color Mode -->
                <div>
                    <InputLabel for="colorMode" value="Color Mode" />
                    <select
                        id="colorMode"
                        v-model="form.colorMode"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2"
                    >
                        <option value="light">Light Mode</option>
                        <option value="dark">Dark Mode</option>
                        <option value="system">System Preference</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        <span v-if="form.colorMode === 'light'">Always use light theme</span>
                        <span v-else-if="form.colorMode === 'dark'">Always use dark theme</span>
                        <span v-else>Follow user's system preference</span>
                    </p>
                </div>

                <!-- Streak Multiplier Toggle -->
                <div class="border-t pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <InputLabel value="Streak Multiplier" />
                            <p class="text-sm text-gray-500 mt-1">
                                {{ form.streakMultiplierEnabled ? 'Bonus ELO for breaking win streaks (3+ wins)' : 'Standard ELO calculation only' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="form.streakMultiplierEnabled = !form.streakMultiplierEnabled"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :style="{ backgroundColor: form.streakMultiplierEnabled ? primaryColor : '#d1d5db' }"
                        >
                            <span
                                :class="[
                                    form.streakMultiplierEnabled ? 'translate-x-5' : 'translate-x-0',
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                ]"
                            />
                        </button>
                    </div>
                </div>

                <PrimaryButton :disabled="form.processing">
                    Save Settings
                </PrimaryButton>
            </form>
        </div>
    </AdminLayout>
</template>
