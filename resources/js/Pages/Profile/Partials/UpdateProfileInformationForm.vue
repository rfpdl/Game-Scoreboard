<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;
const { primaryColor } = useBranding();

const form = useForm({
    name: user.name,
    nickname: user.nickname || '',
    email: user.email,
});

const avatarForm = useForm({
    avatar: null as File | null,
});

const avatarInput = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);

const selectAvatar = () => {
    avatarInput.value?.click();
};

const onAvatarSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        avatarForm.avatar = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);

        // Upload immediately
        avatarForm.post(route('profile.avatar'), {
            preserveScroll: true,
            onSuccess: () => {
                avatarPreview.value = null;
                if (avatarInput.value) {
                    avatarInput.value.value = '';
                }
            },
            onError: () => {
                avatarPreview.value = null;
            },
        });
    }
};

const removeAvatar = () => {
    router.delete(route('profile.avatar.remove'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Update your account's profile information and email address.
            </p>
        </header>

        <!-- Avatar Upload -->
        <div class="mt-6">
            <InputLabel value="Profile Photo" />

            <div class="mt-2 flex items-center gap-4">
                <!-- Avatar Preview -->
                <div class="relative">
                    <div
                        v-if="avatarPreview || user.avatar"
                        class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700"
                    >
                        <img
                            :src="avatarPreview || `/storage/${user.avatar}`"
                            class="w-full h-full object-cover"
                            alt="Avatar"
                        />
                    </div>
                    <div
                        v-else
                        class="w-20 h-20 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400"
                    >
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <!-- Loading overlay -->
                    <div
                        v-if="avatarForm.processing"
                        class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 rounded-full flex items-center justify-center"
                    >
                        <svg class="animate-spin h-6 w-6" :style="{ color: primaryColor }" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <button
                        type="button"
                        @click="selectAvatar"
                        :disabled="avatarForm.processing"
                        class="px-3 py-1.5 text-sm font-medium rounded-md border transition-colors"
                        :style="{
                            borderColor: primaryColor,
                            color: primaryColor,
                        }"
                    >
                        {{ user.avatar ? 'Change Photo' : 'Upload Photo' }}
                    </button>
                    <button
                        v-if="user.avatar && !avatarPreview"
                        type="button"
                        @click="removeAvatar"
                        class="px-3 py-1.5 text-sm font-medium rounded-md border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    >
                        Remove Photo
                    </button>
                </div>

                <input
                    ref="avatarInput"
                    type="file"
                    class="hidden"
                    accept="image/*"
                    @change="onAvatarSelected"
                />
            </div>

            <InputError class="mt-2" :message="avatarForm.errors.avatar" />

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                JPG, PNG or GIF. Max 2MB.
            </p>
        </div>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="nickname" value="Nickname (optional)" />

                <div class="mt-1 flex rounded-md shadow-sm">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                        @
                    </span>
                    <TextInput
                        id="nickname"
                        type="text"
                        class="flex-1 block w-full rounded-none rounded-r-md"
                        v-model="form.nickname"
                        placeholder="the-magician"
                        autocomplete="off"
                    />
                </div>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Lowercase letters, numbers, and hyphens only. This will be your unique profile URL.
                </p>

                <InputError class="mt-2" :message="form.errors.nickname" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
