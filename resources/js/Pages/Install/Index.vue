<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

interface Props {
    settings: {
        appName: string;
        primaryColor: string;
        logoUrl: string | null;
    };
}

const props = defineProps<Props>();

const currentStep = ref(1);
const totalSteps = 4;

// Step 1 & 2: Branding settings
const settingsForm = useForm({
    appName: props.settings.appName || 'Game Scoreboard',
    primaryColor: props.settings.primaryColor || '#f97316',
});

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

// Step 3: Logo
const logoPreview = ref<string | null>(props.settings.logoUrl);
const logoUploading = ref(false);

// Step 4: Admin
const adminForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const stepTitle = computed(() => {
    switch (currentStep.value) {
        case 1: return 'Name Your App';
        case 2: return 'Choose Primary Color';
        case 3: return 'Upload Logo (Optional)';
        case 4: return 'Create Admin Account';
        default: return '';
    }
});

const stepDescription = computed(() => {
    switch (currentStep.value) {
        case 1: return 'What would you like to call your game scoreboard?';
        case 2: return 'Pick a primary color that matches your brand.';
        case 3: return 'Add a logo to personalize your game scoreboard.';
        case 4: return 'Create the first administrator account.';
        default: return '';
    }
});

const saveSettings = () => {
    settingsForm.post(route('install.settings'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            currentStep.value++;
        },
    });
};

const nextStep = () => {
    if (currentStep.value === 1 || currentStep.value === 2) {
        saveSettings();
    } else if (currentStep.value === 3) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const skipLogo = () => {
    currentStep.value++;
};

const handleLogoUpload = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        logoUploading.value = true;
        logoPreview.value = URL.createObjectURL(file);

        const formData = new FormData();
        formData.append('logo', file);

        router.post(route('install.logo'), formData, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                logoUploading.value = false;
            },
        });
    }
};

const removeLogo = () => {
    router.delete(route('install.logo.remove'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            logoPreview.value = null;
        },
    });
};

const submitAdmin = () => {
    adminForm.post(route('install.admin'));
};
</script>

<template>
    <Head title="Setup" />

    <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Progress -->
            <div class="mb-8">
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-600">Step {{ currentStep }} of {{ totalSteps }}</span>
                    <span class="text-sm font-medium text-gray-900">{{ stepTitle }}</span>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                        class="h-2 rounded-full transition-all duration-300"
                        :style="{
                            width: `${(currentStep / totalSteps) * 100}%`,
                            backgroundColor: settingsForm.primaryColor
                        }"
                    />
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-xl p-6 sm:p-8">
                <p class="text-gray-600 mb-6">{{ stepDescription }}</p>

                <!-- Step 1: App Name -->
                <div v-if="currentStep === 1">
                    <InputLabel for="appName" value="Application Name" />
                    <TextInput
                        id="appName"
                        v-model="settingsForm.appName"
                        class="mt-1 block w-full"
                        placeholder="My Game Scoreboard"
                        autofocus
                    />
                    <InputError :message="settingsForm.errors.appName" class="mt-2" />
                </div>

                <!-- Step 2: Primary Color -->
                <div v-if="currentStep === 2">
                    <InputLabel for="primaryColor" value="Primary Color" />

                    <!-- Preset swatches -->
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="color in presetColors"
                            :key="color"
                            type="button"
                            @click="settingsForm.primaryColor = color"
                            class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110"
                            :class="settingsForm.primaryColor === color ? 'border-gray-800 scale-110' : 'border-transparent'"
                            :style="{ backgroundColor: color }"
                        />
                    </div>

                    <!-- Custom color picker -->
                    <div class="mt-3 flex items-center gap-3">
                        <input
                            id="primaryColor"
                            type="color"
                            v-model="settingsForm.primaryColor"
                            @change="($event.target as HTMLInputElement).blur()"
                            class="h-10 w-14 rounded-lg border border-gray-300 cursor-pointer"
                        />
                        <TextInput
                            v-model="settingsForm.primaryColor"
                            class="flex-1"
                            placeholder="#f97316"
                        />
                    </div>
                    <InputError :message="settingsForm.errors.primaryColor" class="mt-2" />

                    <!-- Preview -->
                    <div class="mt-6 p-4 rounded-lg border" :style="{ borderColor: settingsForm.primaryColor }">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                                :style="{ backgroundColor: settingsForm.primaryColor }"
                            >
                                {{ settingsForm.appName.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <span class="font-semibold" :style="{ color: settingsForm.primaryColor }">
                                    {{ settingsForm.appName }}
                                </span>
                                <p class="text-sm text-gray-500">Preview of your branding</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Logo -->
                <div v-if="currentStep === 3">
                    <div v-if="logoPreview" class="text-center">
                        <img :src="logoPreview" alt="Logo preview" class="h-20 mx-auto mb-4" />
                        <button
                            @click="removeLogo"
                            type="button"
                            class="text-sm text-red-600 hover:text-red-800"
                        >
                            Remove Logo
                        </button>
                    </div>
                    <div v-else>
                        <label
                            class="block w-full border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-gray-400 transition-colors"
                            :class="{ 'opacity-50': logoUploading }"
                        >
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleLogoUpload"
                                class="hidden"
                                :disabled="logoUploading"
                            />
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-gray-600">
                                {{ logoUploading ? 'Uploading...' : 'Click to upload logo' }}
                            </span>
                            <p class="text-xs text-gray-400 mt-1">PNG, JPG, SVG up to 2MB</p>
                        </label>
                    </div>
                </div>

                <!-- Step 4: Admin Account -->
                <div v-if="currentStep === 4" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput
                            id="name"
                            v-model="adminForm.name"
                            class="mt-1 block w-full"
                            autofocus
                        />
                        <InputError :message="adminForm.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            v-model="adminForm.email"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="adminForm.errors.email" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="password" value="Password" />
                        <TextInput
                            id="password"
                            type="password"
                            v-model="adminForm.password"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="adminForm.errors.password" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            v-model="adminForm.password_confirmation"
                            class="mt-1 block w-full"
                        />
                    </div>
                </div>

                <!-- Navigation -->
                <div class="mt-8 flex justify-between">
                    <button
                        v-if="currentStep > 1"
                        @click="prevStep"
                        type="button"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
                    >
                        Back
                    </button>
                    <div v-else></div>

                    <div class="flex gap-3">
                        <button
                            v-if="currentStep === 3"
                            @click="skipLogo"
                            type="button"
                            class="px-4 py-2 text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            Skip
                        </button>
                        <PrimaryButton
                            v-if="currentStep < 4"
                            @click="nextStep"
                            :disabled="settingsForm.processing"
                        >
                            {{ currentStep === 3 ? 'Next' : 'Continue' }}
                        </PrimaryButton>
                        <PrimaryButton
                            v-if="currentStep === 4"
                            @click="submitAdmin"
                            :disabled="adminForm.processing"
                        >
                            Complete Setup
                        </PrimaryButton>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-sm text-gray-400 mt-6">
                {{ settingsForm.appName }}
            </p>
        </div>
    </div>
</template>
