<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Player {
    id: number;
    name: string;
    team: string;
    isConfirmed: boolean;
    isWinner: boolean;
    ratingBefore: number | null;
    ratingAfter: number | null;
    ratingChange: number | null;
}

interface Match {
    uuid: string;
    code: string;
    status: string;
    format: string;
    game: {
        name: string;
        icon: string | null;
        slug: string;
    } | null;
    players: Player[];
    createdAt: string;
    completedAt: string | null;
}

interface Game {
    id: number;
    name: string;
    icon: string | null;
}

interface User {
    id: number;
    name: string;
    display_name: string;
}

interface Props {
    matches: {
        data: Match[];
        links: any;
        meta: any;
    };
    games: Game[];
    users: User[];
    filters: {
        status: string;
        game: number | null;
        user: number | null;
        date_from: string | null;
        date_to: string | null;
    };
}

const props = defineProps<Props>();

const statusFilter = ref(props.filters.status);
const gameFilter = ref(props.filters.game);
const userFilter = ref(props.filters.user);
const dateFromFilter = ref(props.filters.date_from);
const dateToFilter = ref(props.filters.date_to);

const applyFilters = () => {
    router.get(route('admin.matches.index'), {
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        game: gameFilter.value || undefined,
        user: userFilter.value || undefined,
        date_from: dateFromFilter.value || undefined,
        date_to: dateToFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([statusFilter, gameFilter, userFilter, dateFromFilter, dateToFilter], applyFilters);

const clearFilters = () => {
    statusFilter.value = 'all';
    gameFilter.value = null;
    userFilter.value = null;
    dateFromFilter.value = null;
    dateToFilter.value = null;
};

const hasActiveFilters = () => {
    return statusFilter.value !== 'all' ||
           gameFilter.value !== null ||
           userFilter.value !== null ||
           dateFromFilter.value !== null ||
           dateToFilter.value !== null;
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300';
        case 'in_progress':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300';
        case 'pending':
            return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300';
        case 'cancelled':
            return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300';
        default:
            return 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
    }
};

const formatStatus = (status: string) => {
    return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getWinnerName = (match: Match) => {
    const winner = match.players.find(p => p.isWinner);
    return winner?.name;
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Manage Matches" />

    <AdminLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Matches</h1>
        </template>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select
                    v-model="statusFilter"
                    class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2"
                >
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Game</label>
                <select
                    v-model="gameFilter"
                    class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2"
                >
                    <option :value="null">All Games</option>
                    <option v-for="game in games" :key="game.id" :value="game.id">
                        {{ game.icon }} {{ game.name }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Player</label>
                <select
                    v-model="userFilter"
                    class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2"
                >
                    <option :value="null">All Players</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                        {{ user.display_name }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                <input
                    v-model="dateFromFilter"
                    type="date"
                    class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                <input
                    v-model="dateToFilter"
                    type="date"
                    class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2"
                />
            </div>
            <div v-if="hasActiveFilters()">
                <button
                    @click="clearFilters"
                    class="rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 text-sm px-3 py-2 transition-colors"
                >
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Matches Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Match
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Players
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Result
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="match in matches.data" :key="match.uuid">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span v-if="match.game?.icon" class="text-lg">{{ match.game.icon }}</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ match.game?.name || 'Unknown Game' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ match.code }} &middot; {{ match.format }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <div
                                    v-for="player in match.players"
                                    :key="player.id"
                                    class="text-sm"
                                    :class="{
                                        'text-green-600 dark:text-green-400 font-medium': player.isWinner,
                                        'text-gray-600 dark:text-gray-400': !player.isWinner
                                    }"
                                >
                                    {{ player.name }}
                                    <span v-if="player.ratingChange" class="text-xs">
                                        ({{ player.ratingChange > 0 ? '+' : '' }}{{ player.ratingChange }})
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="['inline-flex px-2 py-1 text-xs font-medium rounded', getStatusBadgeClass(match.status)]"
                            >
                                {{ formatStatus(match.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <template v-if="match.status === 'completed'">
                                <span class="text-green-600 dark:text-green-400 font-medium">
                                    {{ getWinnerName(match) }} won
                                </span>
                            </template>
                            <template v-else-if="match.status === 'cancelled'">
                                <span class="text-gray-500">Cancelled</span>
                            </template>
                            <template v-else>
                                <span class="text-gray-400">-</span>
                            </template>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ formatDate(match.createdAt) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <Link
                                :href="route('matches.show', { uuid: match.uuid })"
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                            >
                                View
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="matches.data.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            No matches found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="matches.data.length > 0" class="mt-4 flex justify-between items-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Showing {{ matches.meta?.from || 1 }} to {{ matches.meta?.to || matches.data.length }} of {{ matches.meta?.total || matches.data.length }} matches
            </div>
            <div v-if="matches.links" class="flex gap-1">
                <template v-for="link in matches.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'px-3 py-1 text-sm rounded border',
                            link.active
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="px-3 py-1 text-sm text-gray-400 dark:text-gray-500"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
