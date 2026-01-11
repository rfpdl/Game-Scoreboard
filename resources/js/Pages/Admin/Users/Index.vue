<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToastStore } from '@/stores/toast';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

interface User {
    id: number;
    name: string;
    nickname: string | null;
    displayName: string;
    email: string;
    isAdmin: boolean;
    isDisabled: boolean;
    ratingsCount: number;
    matchesCount: number;
    createdAt: string;
}

interface Props {
    users: User[];
}

defineProps<Props>();

const page = usePage();
const currentUserId = page.props.auth.user.id;
const toast = useToastStore();

// Confirmation state
const confirmDelete = ref<User | null>(null);
const confirmAdminToggle = ref<User | null>(null);
const confirmDisableToggle = ref<User | null>(null);

// Edit state
const editingUser = ref<User | null>(null);
const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// Create user state
const showCreateModal = ref(false);
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_admin: false,
});

const openCreateModal = () => {
    showCreateModal.value = true;
    createForm.reset();
    createForm.clearErrors();
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('admin.users.store'), {
        onSuccess: () => {
            toast.success('User created successfully');
            closeCreateModal();
        },
        onError: () => {
            toast.error('Failed to create user');
        },
    });
};

const openEditModal = (user: User) => {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.clearErrors();
};

const closeEditModal = () => {
    editingUser.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingUser.value) return;

    editForm.put(route('admin.users.update', { user: editingUser.value.id }), {
        onSuccess: () => {
            toast.success('User updated');
            closeEditModal();
        },
        onError: () => {
            toast.error('Failed to update user');
        },
    });
};

const toggleAdmin = (user: User) => {
    if (user.id === currentUserId) {
        toast.error('You cannot modify your own admin status');
        return;
    }
    confirmAdminToggle.value = user;
};

const confirmToggleAdmin = () => {
    if (!confirmAdminToggle.value) return;
    const user = confirmAdminToggle.value;

    router.patch(route('admin.users.toggle-admin', { user: user.id }), {}, {
        onSuccess: () => {
            toast.success(user.isAdmin ? 'Admin rights removed' : 'User is now admin');
            confirmAdminToggle.value = null;
        },
        onError: () => {
            toast.error('Failed to update user');
        },
    });
};

const toggleDisabled = (user: User) => {
    if (user.id === currentUserId) {
        toast.error('You cannot disable your own account');
        return;
    }
    confirmDisableToggle.value = user;
};

const confirmToggleDisabled = () => {
    if (!confirmDisableToggle.value) return;
    const user = confirmDisableToggle.value;

    router.patch(route('admin.users.toggle-disabled', { user: user.id }), {}, {
        onSuccess: () => {
            toast.success(user.isDisabled ? 'User enabled' : 'User disabled');
            confirmDisableToggle.value = null;
        },
        onError: () => {
            toast.error('Failed to update user');
        },
    });
};

const deleteUser = (user: User) => {
    confirmDelete.value = user;
};

const confirmDeleteUser = () => {
    if (confirmDelete.value) {
        const userName = confirmDelete.value.displayName;
        router.delete(route('admin.users.destroy', { user: confirmDelete.value.id }), {
            onSuccess: () => {
                confirmDelete.value = null;
                toast.success(`User "${userName}" removed`);
            },
            onError: () => {
                toast.error('Failed to remove user');
            },
        });
    }
};

const cancelDelete = () => {
    confirmDelete.value = null;
};
</script>

<template>
    <Head title="Manage Users" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Users</h1>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add User
                </button>
            </div>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Stats
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Joined
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="user in users" :key="user.id" :class="{ 'bg-gray-50 dark:bg-gray-700/50 opacity-60': user.isDisabled }">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <component
                                :is="user.nickname ? Link : 'div'"
                                :href="user.nickname ? route('profile.public', { nickname: user.nickname }) : undefined"
                                class="flex items-center gap-3"
                                :class="{ 'hover:opacity-80 transition-opacity cursor-pointer': user.nickname }"
                            >
                                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 font-medium text-sm">
                                    {{ user.displayName.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white" :class="{ 'hover:underline': user.nickname }">{{ user.displayName }}</div>
                                    <div v-if="user.displayName !== user.name" class="text-xs text-gray-500 dark:text-gray-400">{{ user.name }}</div>
                                </div>
                            </component>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ user.email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <!-- Admin badge -->
                                <span
                                    v-if="user.isAdmin"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300"
                                >
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 1l2.47 5.01L18 6.9l-4 3.9.95 5.5L10 13.5l-4.95 2.8.95-5.5-4-3.9 5.53-.89L10 1z" clip-rule="evenodd"/>
                                    </svg>
                                    Admin
                                </span>
                                <!-- Disabled indicator -->
                                <span
                                    v-if="user.isDisabled"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300"
                                >
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Disabled
                                </span>
                                <!-- Active dot (only show if not admin and not disabled) -->
                                <span
                                    v-if="!user.isAdmin && !user.isDisabled"
                                    class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    Active
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col">
                                <span>{{ user.ratingsCount }} game{{ user.ratingsCount !== 1 ? 's' : '' }}</span>
                                <span class="text-xs">{{ user.matchesCount }} match{{ user.matchesCount !== 1 ? 'es' : '' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ new Date(user.createdAt).toLocaleDateString() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div v-if="user.id !== currentUserId" class="flex justify-end gap-2">
                                <button
                                    @click="openEditModal(user)"
                                    class="font-medium text-xs px-2 py-1 rounded text-blue-600 hover:text-blue-800 hover:bg-blue-50"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="toggleAdmin(user)"
                                    :class="[
                                        'font-medium text-xs px-2 py-1 rounded',
                                        user.isAdmin
                                            ? 'text-amber-600 hover:text-amber-800 hover:bg-amber-50'
                                            : 'text-purple-600 hover:text-purple-800 hover:bg-purple-50'
                                    ]"
                                >
                                    {{ user.isAdmin ? 'Remove Admin' : 'Make Admin' }}
                                </button>
                                <button
                                    @click="toggleDisabled(user)"
                                    :class="[
                                        'font-medium text-xs px-2 py-1 rounded',
                                        user.isDisabled
                                            ? 'text-green-600 hover:text-green-800 hover:bg-green-50'
                                            : 'text-orange-600 hover:text-orange-800 hover:bg-orange-50'
                                    ]"
                                >
                                    {{ user.isDisabled ? 'Enable' : 'Disable' }}
                                </button>
                                <button
                                    @click="deleteUser(user)"
                                    class="font-medium text-xs px-2 py-1 rounded text-red-600 hover:text-red-800 hover:bg-red-50"
                                >
                                    Remove
                                </button>
                            </div>
                            <span v-else class="text-gray-400 dark:text-gray-500 text-sm">You</span>
                        </td>
                    </tr>
                    <tr v-if="users.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            No users found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="confirmDelete" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" @click="cancelDelete"></div>
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">
                                    Remove User
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Are you sure you want to remove <strong class="text-gray-700 dark:text-gray-200">{{ confirmDelete.displayName }}</strong>?
                                        This will permanently delete their account and all associated data:
                                    </p>
                                    <ul class="mt-2 text-sm text-gray-500 dark:text-gray-400 list-disc list-inside">
                                        <li>{{ confirmDelete.ratingsCount }} rating{{ confirmDelete.ratingsCount !== 1 ? 's' : '' }} will be deleted</li>
                                        <li>{{ confirmDelete.matchesCount }} match participation{{ confirmDelete.matchesCount !== 1 ? 's' : '' }} will be removed</li>
                                    </ul>
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 font-medium">
                                        This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                        <button
                            type="button"
                            @click="confirmDeleteUser"
                            class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:w-auto"
                        >
                            Remove User
                        </button>
                        <button
                            type="button"
                            @click="cancelDelete"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div v-if="editingUser" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" @click="closeEditModal"></div>
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form @submit.prevent="submitEdit">
                        <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Edit User
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Update user details. Leave password blank to keep current.</p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="edit-name" value="Name" />
                                    <TextInput
                                        id="edit-name"
                                        v-model="editForm.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="editForm.errors.name" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="edit-email" value="Email" />
                                    <TextInput
                                        id="edit-email"
                                        v-model="editForm.email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="editForm.errors.email" class="mt-2" />
                                </div>

                                <div class="border-t dark:border-gray-600 pt-4">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reset Password (optional)</p>
                                    <div class="space-y-4">
                                        <div>
                                            <InputLabel for="edit-password" value="New Password" />
                                            <TextInput
                                                id="edit-password"
                                                v-model="editForm.password"
                                                type="password"
                                                class="mt-1 block w-full"
                                                autocomplete="new-password"
                                            />
                                            <InputError :message="editForm.errors.password" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="edit-password-confirm" value="Confirm Password" />
                                            <TextInput
                                                id="edit-password-confirm"
                                                v-model="editForm.password_confirmation"
                                                type="password"
                                                class="mt-1 block w-full"
                                                autocomplete="new-password"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="inline-flex w-full justify-center rounded-md btn-primary px-3 py-2 text-sm font-semibold text-white shadow-sm sm:w-auto disabled:opacity-50"
                            >
                                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                            </button>
                            <button
                                type="button"
                                @click="closeEditModal"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" @click="closeCreateModal"></div>
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form @submit.prevent="submitCreate">
                        <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Add New User
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Create a new user account.</p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="create-name" value="Name" />
                                    <TextInput
                                        id="create-name"
                                        v-model="createForm.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        autofocus
                                    />
                                    <InputError :message="createForm.errors.name" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="create-email" value="Email" />
                                    <TextInput
                                        id="create-email"
                                        v-model="createForm.email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="createForm.errors.email" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="create-password" value="Password" />
                                    <TextInput
                                        id="create-password"
                                        v-model="createForm.password"
                                        type="password"
                                        class="mt-1 block w-full"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <InputError :message="createForm.errors.password" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="create-password-confirm" value="Confirm Password" />
                                    <TextInput
                                        id="create-password-confirm"
                                        v-model="createForm.password_confirmation"
                                        type="password"
                                        class="mt-1 block w-full"
                                        required
                                        autocomplete="new-password"
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        id="create-is-admin"
                                        v-model="createForm.is_admin"
                                        type="checkbox"
                                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-700"
                                    />
                                    <InputLabel for="create-is-admin" value="Grant admin privileges" class="cursor-pointer" />
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="inline-flex w-full justify-center rounded-md btn-primary px-3 py-2 text-sm font-semibold text-white shadow-sm sm:w-auto disabled:opacity-50"
                            >
                                {{ createForm.processing ? 'Creating...' : 'Create User' }}
                            </button>
                            <button
                                type="button"
                                @click="closeCreateModal"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Admin Toggle Confirmation -->
        <ConfirmationModal
            :show="!!confirmAdminToggle"
            :title="confirmAdminToggle?.isAdmin ? 'Remove Admin Rights' : 'Grant Admin Rights'"
            :message="`Are you sure you want to ${confirmAdminToggle?.isAdmin ? 'remove admin rights from' : 'make'} ${confirmAdminToggle?.displayName}${confirmAdminToggle?.isAdmin ? '' : ' an admin'}?`"
            :confirm-text="confirmAdminToggle?.isAdmin ? 'Remove Admin' : 'Make Admin'"
            :variant="confirmAdminToggle?.isAdmin ? 'warning' : 'primary'"
            @confirm="confirmToggleAdmin"
            @cancel="confirmAdminToggle = null"
        />

        <!-- Disable Toggle Confirmation -->
        <ConfirmationModal
            :show="!!confirmDisableToggle"
            :title="confirmDisableToggle?.isDisabled ? 'Enable User' : 'Disable User'"
            :message="`Are you sure you want to ${confirmDisableToggle?.isDisabled ? 'enable' : 'disable'} ${confirmDisableToggle?.displayName}?${confirmDisableToggle?.isDisabled ? '' : ' They will not be able to log in.'}`"
            :confirm-text="confirmDisableToggle?.isDisabled ? 'Enable' : 'Disable'"
            :variant="confirmDisableToggle?.isDisabled ? 'primary' : 'warning'"
            @confirm="confirmToggleDisabled"
            @cancel="confirmDisableToggle = null"
        />
    </AdminLayout>
</template>
