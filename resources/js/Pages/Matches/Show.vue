<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import type { Match, User } from '@/types';
import PlayerCard from '@/Components/molecules/PlayerCard';
import Badge from '@/Components/atoms/Badge';
import Button from '@/Components/atoms/Button';
import TextInput from '@/Components/TextInput.vue';
import { useMatchStore } from '@/stores/match';
import { useToastStore } from '@/stores/toast';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';

// Confirmation dialog state
const showCancelConfirm = ref(false);
const showLeaveConfirm = ref(false);
const showRules = ref(false); // Collapsed by default

// Invite modal state
const showInviteModal = ref(false);
const searchQuery = ref('');
const searchResults = ref<Array<{ id: number; name: string; avatar: string | null }>>([]);
const availablePlayers = ref<Array<{ id: number; name: string; avatar: string | null }>>([]);
const isSearching = ref(false);
const isLoadingPlayers = ref(false);
const inviting = ref(false);

type BadgeVariant = 'default' | 'success' | 'warning' | 'danger' | 'info';

interface Props {
    match: Match;
}

const props = defineProps<Props>();
const page = usePage();
const matchStore = useMatchStore();

// Reactive match state for real-time updates
const liveMatch = ref<Match>(props.match);

// Subscribe to WebSocket channel for real-time updates
const toast = useToastStore();

// Track connection state
const wasDisconnected = ref(false);
const hasRealtimeUpdates = ref(false);
const isRefreshing = ref(false);

// Manual refresh for when real-time is not available
const refreshMatch = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['match'],
        onSuccess: () => {
            // Update liveMatch with fresh data
            liveMatch.value = props.match;
            toast.success('Match data refreshed');
        },
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

// Try to setup real-time updates (may not be available)
const setupRealtimeUpdates = () => {
    if (!window.Echo) {
        console.log('Real-time updates not available (Echo not configured)');
        return;
    }

    hasRealtimeUpdates.value = true;
    const channel = window.Echo.channel(`match.${props.match.uuid}`);

    // Listen for match updates
    channel.listen('.match.updated', (event: { match: Match; action: string }) => {
        console.log('Received match update:', event);
        liveMatch.value = event.match;

        // Show toast based on action
        switch (event.action) {
            case 'confirmed':
                toast.success('Match started!');
                break;
            case 'completed':
                toast.success('Match completed! Ratings updated.');
                break;
            case 'cancelled':
                toast.warning('Match was cancelled');
                break;
            case 'player_joined':
                toast.info('A player joined the match');
                break;
            case 'player_left':
                toast.info('A player left the match');
                break;
            case 'team_switched':
                toast.info('A player switched teams');
                break;
            case 'format_changed':
                toast.info('Match format changed');
                showFormatSelector.value = false;
                break;
        }
    });

    // Handle connection state changes
    if (window.Echo.connector?.pusher) {
        const pusher = window.Echo.connector.pusher;

        pusher.connection.bind('disconnected', () => {
            console.log('WebSocket disconnected');
            wasDisconnected.value = true;
            hasRealtimeUpdates.value = false;
            toast.warning('Connection lost. Use refresh button to update.');
        });

        pusher.connection.bind('connected', () => {
            console.log('WebSocket connected');
            hasRealtimeUpdates.value = true;
            if (wasDisconnected.value) {
                toast.info('Reconnected! Refreshing...');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        });
    }
};

onMounted(() => {
    // Echo may load asynchronously, so we try immediately and also after a delay
    setupRealtimeUpdates();

    // Retry after a short delay in case Echo loads asynchronously
    setTimeout(() => {
        if (!hasRealtimeUpdates.value) {
            setupRealtimeUpdates();
        }
    }, 1000);
});

onUnmounted(() => {
    console.log('Match Show unmounting, leaving channel');
    if (window.Echo) {
        window.Echo.leaveChannel(`match.${props.match.uuid}`);
    }
});

const currentUser = computed(() => page.props.auth?.user as User | undefined);

const statusVariants: Record<string, BadgeVariant> = {
    pending: 'warning',
    confirmed: 'info',
    completed: 'success',
    cancelled: 'danger',
};

const getStatusLabel = computed(() => {
    const minPlayers = liveMatch.value.matchFormat === 'ffa' ? 3 : liveMatch.value.maxPlayers;
    if (liveMatch.value.status === 'pending') {
        return liveMatch.value.players.length >= minPlayers ? 'Ready to Start' : 'Waiting for Players';
    }
    const labels: Record<string, string> = {
        confirmed: 'Match in Progress',
        completed: 'Match Complete',
        cancelled: 'Match Cancelled',
    };
    return labels[liveMatch.value.status] || liveMatch.value.status;
});

const formatLabel = computed(() => {
    const formats: Record<string, string> = {
        '1v1': '1 vs 1',
        '2v2': '2 vs 2 Teams',
        '3v3': '3 vs 3 Teams',
        '4v4': '4 vs 4 Teams',
        'ffa': `Free For All (${liveMatch.value.maxPlayers} players)`,
    };
    return formats[liveMatch.value.matchFormat] || liveMatch.value.matchFormat;
});

const isTeamMatch = computed(() => {
    return ['2v2', '3v3', '4v4'].includes(liveMatch.value.matchFormat);
});

const playersPerTeam = computed(() => {
    const perTeam: Record<string, number> = {
        '2v2': 2,
        '3v3': 3,
        '4v4': 4,
    };
    return perTeam[liveMatch.value.matchFormat] || 2;
});

const statusVariantComputed = computed(() => {
    const minPlayers = liveMatch.value.matchFormat === 'ffa' ? 3 : liveMatch.value.maxPlayers;
    if (liveMatch.value.status === 'pending' && liveMatch.value.players.length >= minPlayers) {
        return 'success'; // Ready to start - green
    }
    return statusVariants[liveMatch.value.status];
});

const canConfirm = computed(() => {
    const minPlayers = liveMatch.value.matchFormat === 'ffa' ? 3 : liveMatch.value.maxPlayers;
    return liveMatch.value.status === 'pending' && liveMatch.value.players.length >= minPlayers;
});

const canComplete = computed(() => {
    return liveMatch.value.status === 'confirmed';
});

const isCreator = computed(() => {
    return liveMatch.value.createdByUserId === currentUser.value?.id;
});

const isPlayer = computed(() => {
    return liveMatch.value.players.some(p => p.userId === currentUser.value?.id);
});

const canCancel = computed(() => {
    // Only the host (creator) can cancel
    const validStatus = liveMatch.value.status === 'pending' || liveMatch.value.status === 'confirmed';
    return validStatus && isCreator.value;
});

const canLeave = computed(() => {
    // Non-creator players can leave pending matches
    return liveMatch.value.status === 'pending' && isPlayer.value && !isCreator.value;
});

const canJoin = computed(() => {
    // Can join if: pending, not already a player, and has room
    return liveMatch.value.status === 'pending'
        && !isPlayer.value
        && liveMatch.value.players.length < liveMatch.value.maxPlayers;
});

const handleJoin = () => {
    matchStore.joinMatch(liveMatch.value.matchCode);
};

const handleConfirm = () => {
    matchStore.confirmMatch(liveMatch.value.uuid);
};

const handleSelectWinner = (userId: number) => {
    matchStore.completeMatch(liveMatch.value.uuid, userId);
};

const handleCancel = () => {
    showCancelConfirm.value = false;
    matchStore.cancelMatch(liveMatch.value.uuid);
};

const handleLeave = () => {
    showLeaveConfirm.value = false;
    matchStore.leaveMatch(liveMatch.value.uuid);
};

const formatMatchDate = (date: string | null) => {
    if (!date) return null;
    return new Date(date).toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const copyCode = () => {
    navigator.clipboard.writeText(liveMatch.value.matchCode);
};

const copyShareLink = () => {
    if (liveMatch.value.shareUrl) {
        navigator.clipboard.writeText(liveMatch.value.shareUrl);
    }
};

// Invite functionality
const canInvite = computed(() => {
    return liveMatch.value.status === 'pending'
        && isCreator.value
        && liveMatch.value.players.length < liveMatch.value.maxPlayers;
});

// Team groupings for team matches (2v2, 3v3, 4v4)
const teamAPlayers = computed(() => {
    if (!isTeamMatch.value) return [];
    return liveMatch.value.players.filter(p => p.team === 'team_a');
});

const teamBPlayers = computed(() => {
    if (!isTeamMatch.value) return [];
    return liveMatch.value.players.filter(p => p.team === 'team_b');
});

// Check if current user can switch teams
const canSwitchTeam = computed(() => {
    if (!isTeamMatch.value) return false;
    if (liveMatch.value.status !== 'pending') return false;
    if (!isPlayer.value) return false;

    // Check if target team has room
    const currentPlayer = liveMatch.value.players.find(p => p.userId === currentUser.value?.id);
    if (!currentPlayer) return false;

    const targetTeam = currentPlayer.team === 'team_a' ? 'team_b' : 'team_a';
    const targetTeamPlayers = liveMatch.value.players.filter(p => p.team === targetTeam);
    return targetTeamPlayers.length < playersPerTeam.value;
});

const handleSwitchTeam = () => {
    if (!canSwitchTeam.value) return;
    matchStore.switchTeam(liveMatch.value.uuid);
};

// Format change functionality
const showFormatSelector = ref(false);
const formatOptions = [
    { value: '1v1', label: '1 vs 1', maxPlayers: 2 },
    { value: '2v2', label: '2 vs 2', maxPlayers: 4 },
    { value: '3v3', label: '3 vs 3', maxPlayers: 6 },
    { value: '4v4', label: '4 vs 4', maxPlayers: 8 },
    { value: 'ffa', label: 'Free For All', maxPlayers: 8 },
];

const canChangeFormat = computed(() => {
    return liveMatch.value.status === 'pending' && isCreator.value;
});

const handleChangeFormat = (format: string) => {
    // Check if we have too many players for the new format
    const option = formatOptions.find(o => o.value === format);
    if (option && liveMatch.value.players.length > option.maxPlayers) {
        toast.error(`Too many players for ${option.label}. Remove players first.`);
        return;
    }
    matchStore.changeFormat(liveMatch.value.uuid, format);
    showFormatSelector.value = false;
};

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

// Get exclude IDs for players already in the match
const getExcludeIds = () => {
    return liveMatch.value.players.map(p => p.userId).join(',');
};

// Load available players (called when modal opens)
const loadAvailablePlayers = async () => {
    isLoadingPlayers.value = true;
    try {
        const response = await fetch(route('matches.searchUsers') + '?exclude=' + getExcludeIds());
        const data = await response.json();
        availablePlayers.value = data.users;
    } catch (error) {
        console.error('Failed to load players:', error);
        availablePlayers.value = [];
    } finally {
        isLoadingPlayers.value = false;
    }
};

// Open invite modal and load players
const openInviteModal = () => {
    showInviteModal.value = true;
    loadAvailablePlayers();
};

watch(searchQuery, (query) => {
    if (searchDebounce) clearTimeout(searchDebounce);

    if (query.length < 2) {
        searchResults.value = [];
        return;
    }

    searchDebounce = setTimeout(async () => {
        isSearching.value = true;
        try {
            const response = await fetch(route('matches.searchUsers') + '?q=' + encodeURIComponent(query) + '&exclude=' + getExcludeIds());
            const data = await response.json();
            searchResults.value = data.users;
        } catch (error) {
            console.error('Search failed:', error);
            searchResults.value = [];
        } finally {
            isSearching.value = false;
        }
    }, 300);
});

const handleInvite = (userId: number) => {
    inviting.value = true;
    router.post(route('matches.invite', { uuid: liveMatch.value.uuid }), { user_id: userId }, {
        onSuccess: () => {
            showInviteModal.value = false;
            searchQuery.value = '';
            searchResults.value = [];
            toast.success('Player invited!');
        },
        onError: (errors) => {
            toast.error(errors.match || 'Failed to invite player');
        },
        onFinish: () => {
            inviting.value = false;
        },
    });
};

const closeInviteModal = () => {
    showInviteModal.value = false;
    searchQuery.value = '';
    searchResults.value = [];
    availablePlayers.value = [];
};
</script>

<template>
    <Head :title="`Match ${liveMatch.matchCode}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {{ liveMatch.gameName }} Match
                    </h2>
                    <!-- Format badge - clickable for creator -->
                    <div class="relative">
                        <button
                            v-if="canChangeFormat"
                            @click="showFormatSelector = !showFormatSelector"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors"
                        >
                            {{ formatLabel }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <Badge v-else-if="liveMatch.matchFormat !== '1v1'" variant="info" size="sm">
                            {{ formatLabel }}
                        </Badge>

                        <!-- Format dropdown backdrop -->
                        <div
                            v-if="showFormatSelector"
                            class="fixed inset-0 z-40"
                            @click="showFormatSelector = false"
                        ></div>
                        <!-- Format dropdown -->
                        <div
                            v-if="showFormatSelector"
                            class="absolute left-0 top-full mt-1 w-40 bg-white dark:bg-gray-800 rounded-lg shadow-lg border dark:border-gray-700 z-50"
                        >
                            <button
                                v-for="option in formatOptions"
                                :key="option.value"
                                @click="handleChangeFormat(option.value)"
                                :disabled="liveMatch.players.length > option.maxPlayers || matchStore.isLoading"
                                :class="[
                                    'w-full px-3 py-2 text-left text-sm transition-colors first:rounded-t-lg last:rounded-b-lg',
                                    liveMatch.matchFormat === option.value
                                        ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 font-medium'
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-300',
                                    liveMatch.players.length > option.maxPlayers
                                        ? 'opacity-50 cursor-not-allowed'
                                        : ''
                                ]"
                            >
                                {{ option.label }}
                                <span v-if="liveMatch.players.length > option.maxPlayers" class="text-xs text-red-500 block">
                                    (too many players)
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="statusVariantComputed" size="sm" data-testid="match-status">
                        {{ getStatusLabel }}
                    </Badge>
                    <!-- Manual refresh button (shown when real-time is not available or match is active) -->
                    <button
                        v-if="!hasRealtimeUpdates && (liveMatch.status === 'pending' || liveMatch.status === 'confirmed')"
                        @click="refreshMatch"
                        :disabled="isRefreshing"
                        class="p-1.5 rounded-full text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        title="Refresh match data"
                    >
                        <svg
                            class="w-4 h-4"
                            :class="{ 'animate-spin': isRefreshing }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-4 px-4 sm:py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl space-y-4 sm:space-y-6">
                <!-- Match Info Display -->
                <div v-if="liveMatch.name || liveMatch.playedAt || liveMatch.scheduledAt" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-6">
                    <div class="text-center">
                        <p v-if="liveMatch.name" class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">{{ liveMatch.name }}</p>
                        <p v-if="liveMatch.playedAt" class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Played {{ formatMatchDate(liveMatch.playedAt) }}
                        </p>
                        <p v-else-if="liveMatch.scheduledAt" class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Scheduled for {{ formatMatchDate(liveMatch.scheduledAt) }}
                        </p>
                    </div>
                </div>

                <!-- Match Code & Share Link Display (for pending matches) -->
                <div v-if="liveMatch.status === 'pending'" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-6">
                    <div class="text-center space-y-4">
                        <div>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-2">Share this code with your opponent</p>
                            <div class="flex items-center justify-center gap-2 sm:gap-3">
                                <span data-testid="match-code" class="text-2xl sm:text-4xl font-mono font-bold tracking-widest text-gray-900 dark:text-white">
                                    {{ liveMatch.matchCode }}
                                </span>
                                <Button variant="ghost" size="sm" @click="copyCode">
                                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </Button>
                            </div>
                        </div>

                        <div v-if="liveMatch.shareUrl" class="border-t dark:border-gray-700 pt-4">
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-2">Or share this link</p>
                            <div class="flex items-center justify-center gap-2">
                                <code data-testid="share-url" class="px-2 sm:px-3 py-1.5 sm:py-2 bg-gray-100 dark:bg-gray-700 rounded text-xs sm:text-sm text-gray-700 dark:text-gray-300 max-w-[200px] sm:max-w-xs truncate">
                                    {{ liveMatch.shareUrl }}
                                </code>
                                <Button variant="ghost" size="sm" @click="copyShareLink">
                                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Players -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-3 sm:mb-4">
                        Players
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            ({{ liveMatch.players.length }}/{{ liveMatch.maxPlayers }})
                        </span>
                    </h3>

                    <!-- Team Match Display (2v2, 3v3, 4v4) -->
                    <template v-if="isTeamMatch">
                        <div class="grid grid-cols-[1fr_auto_1fr] gap-2 sm:gap-4 items-start">
                            <!-- Team A -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-blue-600 mb-2">Team A</h4>
                                <TransitionGroup name="team-switch" tag="div" class="space-y-2">
                                <div
                                    v-for="player in teamAPlayers"
                                    :key="'player-' + player.userId"
                                    :class="[
                                        'transition-all rounded-lg',
                                        player.userId === currentUser?.id && canSwitchTeam
                                            ? 'cursor-pointer ring-2 ring-blue-300 hover:ring-blue-500'
                                            : ''
                                    ]"
                                    @click="player.userId === currentUser?.id && canSwitchTeam ? handleSwitchTeam() : null"
                                    :title="player.userId === currentUser?.id && canSwitchTeam ? 'Click to switch to Team B' : ''"
                                >
                                    <PlayerCard
                                        :player="{
                                            id: player.userId,
                                            name: player.userName || 'Unknown',
                                            avatar: player.userAvatar,
                                            rating: player.ratingBefore || undefined,
                                            ratingChange: player.ratingChange,
                                            result: player.result as 'pending' | 'win' | 'lose' | 'draw',
                                            profileUrl: player.userNickname ? `/@${player.userNickname}` : null,
                                            isHost: player.userId === liveMatch.createdByUserId,
                                        }"
                                        :show-result="liveMatch.status === 'completed'"
                                        :is-current-user="player.userId === currentUser?.id"
                                    />
                                </div>
                                <!-- Empty slots in Team A - clickable for players in Team B -->
                                <button
                                    v-for="i in (playersPerTeam - teamAPlayers.length)"
                                    :key="'slot-a-' + i"
                                    :disabled="!canSwitchTeam || teamAPlayers.some(p => p.userId === currentUser?.id)"
                                    :class="[
                                        'w-full min-h-[72px] p-3 border-2 border-dashed rounded-lg flex items-center justify-center text-sm transition-all',
                                        canSwitchTeam && !teamAPlayers.some(p => p.userId === currentUser?.id)
                                            ? 'border-blue-300 text-blue-500 hover:border-blue-500 hover:bg-blue-50 cursor-pointer'
                                            : 'border-gray-200 text-gray-400'
                                    ]"
                                    @click="canSwitchTeam && !teamAPlayers.some(p => p.userId === currentUser?.id) ? handleSwitchTeam() : null"
                                >
                                    {{ canSwitchTeam && !teamAPlayers.some(p => p.userId === currentUser?.id) ? 'Click to join Team A' : 'Waiting for player...' }}
                                </button>
                                </TransitionGroup>
                            </div>
                            <!-- VS Badge -->
                            <div class="flex items-center justify-center pt-8">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold text-sm sm:text-base">
                                    VS
                                </div>
                            </div>
                            <!-- Team B -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-red-600 mb-2">Team B</h4>
                                <TransitionGroup name="team-switch" tag="div" class="space-y-2">
                                <div
                                    v-for="player in teamBPlayers"
                                    :key="'player-' + player.userId"
                                    :class="[
                                        'transition-all rounded-lg',
                                        player.userId === currentUser?.id && canSwitchTeam
                                            ? 'cursor-pointer ring-2 ring-red-300 hover:ring-red-500'
                                            : ''
                                    ]"
                                    @click="player.userId === currentUser?.id && canSwitchTeam ? handleSwitchTeam() : null"
                                    :title="player.userId === currentUser?.id && canSwitchTeam ? 'Click to switch to Team A' : ''"
                                >
                                    <PlayerCard
                                        :player="{
                                            id: player.userId,
                                            name: player.userName || 'Unknown',
                                            avatar: player.userAvatar,
                                            rating: player.ratingBefore || undefined,
                                            ratingChange: player.ratingChange,
                                            result: player.result as 'pending' | 'win' | 'lose' | 'draw',
                                            profileUrl: player.userNickname ? `/@${player.userNickname}` : null,
                                            isHost: player.userId === liveMatch.createdByUserId,
                                        }"
                                        :show-result="liveMatch.status === 'completed'"
                                        :is-current-user="player.userId === currentUser?.id"
                                    />
                                </div>
                                <!-- Empty slots in Team B - clickable for players in Team A -->
                                <button
                                    v-for="i in (playersPerTeam - teamBPlayers.length)"
                                    :key="'slot-b-' + i"
                                    :disabled="!canSwitchTeam || teamBPlayers.some(p => p.userId === currentUser?.id)"
                                    :class="[
                                        'w-full min-h-[72px] p-3 border-2 border-dashed rounded-lg flex items-center justify-center text-sm transition-all',
                                        canSwitchTeam && !teamBPlayers.some(p => p.userId === currentUser?.id)
                                            ? 'border-red-300 text-red-500 hover:border-red-500 hover:bg-red-50 cursor-pointer'
                                            : 'border-gray-200 text-gray-400'
                                    ]"
                                    @click="canSwitchTeam && !teamBPlayers.some(p => p.userId === currentUser?.id) ? handleSwitchTeam() : null"
                                >
                                    {{ canSwitchTeam && !teamBPlayers.some(p => p.userId === currentUser?.id) ? 'Click to join Team B' : 'Waiting for player...' }}
                                </button>
                                </TransitionGroup>
                            </div>
                        </div>
                    </template>

                    <!-- 1v1 Display with VS -->
                    <template v-else-if="liveMatch.matchFormat === '1v1'">
                        <div class="flex items-center gap-2 sm:gap-4">
                            <!-- Player 1 -->
                            <div class="flex-1">
                                <PlayerCard
                                    v-if="liveMatch.players[0]"
                                    :player="{
                                        id: liveMatch.players[0].userId,
                                        name: liveMatch.players[0].userName || 'Unknown',
                                        avatar: liveMatch.players[0].userAvatar,
                                        rating: liveMatch.players[0].ratingBefore || undefined,
                                        ratingChange: liveMatch.players[0].ratingChange,
                                        result: liveMatch.players[0].result as 'pending' | 'win' | 'lose' | 'draw',
                                        profileUrl: liveMatch.players[0].userNickname ? `/@${liveMatch.players[0].userNickname}` : null,
                                        isHost: liveMatch.players[0].userId === liveMatch.createdByUserId,
                                    }"
                                    :show-result="liveMatch.status === 'completed'"
                                    :is-current-user="liveMatch.players[0].userId === currentUser?.id"
                                />
                                <div v-else class="min-h-[72px] p-3 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm">
                                    Waiting...
                                </div>
                            </div>
                            <!-- VS Badge -->
                            <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold text-sm sm:text-base">
                                VS
                            </div>
                            <!-- Player 2 -->
                            <div class="flex-1">
                                <PlayerCard
                                    v-if="liveMatch.players[1]"
                                    :player="{
                                        id: liveMatch.players[1].userId,
                                        name: liveMatch.players[1].userName || 'Unknown',
                                        avatar: liveMatch.players[1].userAvatar,
                                        rating: liveMatch.players[1].ratingBefore || undefined,
                                        ratingChange: liveMatch.players[1].ratingChange,
                                        result: liveMatch.players[1].result as 'pending' | 'win' | 'lose' | 'draw',
                                        profileUrl: liveMatch.players[1].userNickname ? `/@${liveMatch.players[1].userNickname}` : null,
                                        isHost: liveMatch.players[1].userId === liveMatch.createdByUserId,
                                    }"
                                    :show-result="liveMatch.status === 'completed'"
                                    :is-current-user="liveMatch.players[1].userId === currentUser?.id"
                                />
                                <button
                                    v-else-if="canJoin"
                                    @click="handleJoin"
                                    :disabled="matchStore.isLoading"
                                    data-testid="join-match-button"
                                    class="w-full min-h-[72px] p-3 border-2 border-dashed rounded-lg flex items-center justify-center text-sm transition-all hover:border-solid cursor-pointer btn-primary-outline disabled:opacity-50"
                                >
                                    {{ matchStore.isLoading ? 'Joining...' : 'Join Match' }}
                                </button>
                                <div v-else class="min-h-[72px] p-3 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm">
                                    Waiting...
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- FFA Display -->
                    <template v-else>
                        <div class="space-y-2 sm:space-y-3">
                            <PlayerCard
                                v-for="player in liveMatch.players"
                                :key="player.id"
                                :player="{
                                    id: player.userId,
                                    name: player.userName || 'Unknown',
                                    avatar: player.userAvatar,
                                    rating: player.ratingBefore || undefined,
                                    ratingChange: player.ratingChange,
                                    result: player.result as 'pending' | 'win' | 'lose' | 'draw',
                                    profileUrl: player.userNickname ? `/@${player.userNickname}` : null,
                                    isHost: player.userId === liveMatch.createdByUserId,
                                }"
                                :show-result="liveMatch.status === 'completed'"
                                :is-current-user="player.userId === currentUser?.id"
                            />
                        </div>
                    </template>

                    <!-- Waiting for players - clickable if user can join (not for 1v1, which has inline join) -->
                    <button
                        v-if="liveMatch.players.length < liveMatch.maxPlayers && canJoin && liveMatch.matchFormat !== '1v1'"
                        @click="handleJoin"
                        :disabled="matchStore.isLoading"
                        class="w-full flex items-center justify-center p-4 sm:p-6 border-2 border-dashed rounded-lg transition-all hover:border-solid cursor-pointer btn-primary-outline disabled:opacity-50 mt-3"
                    >
                        <div class="text-center">
                            <svg v-if="!matchStore.isLoading" class="mx-auto h-10 w-10 sm:h-12 sm:w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-primary)">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <svg v-else class="mx-auto h-10 w-10 sm:h-12 sm:w-12 animate-spin" fill="none" viewBox="0 0 24 24" style="color: var(--color-primary)">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-sm sm:text-base font-medium" style="color: var(--color-primary)">
                                {{ matchStore.isLoading ? 'Joining...' : 'Tap to join this match' }}
                            </p>
                        </div>
                    </button>

                    <!-- Waiting for players - non-clickable for current player (only for FFA) -->
                    <div v-else-if="liveMatch.players.length < liveMatch.maxPlayers && liveMatch.matchFormat === 'ffa'" class="flex flex-col items-center justify-center p-4 sm:p-6 border-2 border-dashed border-gray-200 rounded-lg mt-3">
                        <div class="text-center text-gray-500">
                            <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <p class="mt-2 text-sm sm:text-base">
                                Waiting for {{ liveMatch.maxPlayers - liveMatch.players.length }} more player{{ liveMatch.maxPlayers - liveMatch.players.length > 1 ? 's' : '' }}...
                            </p>
                        </div>
                        <!-- Invite button for creator -->
                        <Button
                            v-if="canInvite"
                            variant="secondary"
                            size="sm"
                            class="mt-4"
                            @click="openInviteModal"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Invite Player
                        </Button>
                    </div>

                    <!-- Invite button for team matches -->
                    <div v-if="canInvite && isTeamMatch" class="mt-4 text-center">
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="openInviteModal"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Invite Player
                        </Button>
                    </div>
                </div>

                <!-- Leave Match (for opponent) -->
                <div v-if="canLeave" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-6">
                    <template v-if="!showLeaveConfirm">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-2">Leave Match?</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-4">
                            Changed your mind? You can leave this match and let another player join.
                        </p>
                        <Button
                            variant="secondary"
                            size="md"
                            class="w-full sm:text-base"
                            @click="showLeaveConfirm = true"
                        >
                            Leave Match
                        </Button>
                    </template>
                    <template v-else>
                        <h3 class="text-base sm:text-lg font-medium text-orange-600 dark:text-orange-400 mb-2">Are you sure?</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-4">
                            You will be removed from this match and another player can join in your place.
                        </p>
                        <div class="flex gap-3">
                            <Button
                                variant="secondary"
                                size="md"
                                class="flex-1"
                                @click="showLeaveConfirm = false"
                            >
                                Go Back
                            </Button>
                            <Button
                                variant="warning"
                                size="md"
                                :loading="matchStore.isLoading"
                                class="flex-1"
                                @click="handleLeave"
                            >
                                Yes, Leave
                            </Button>
                        </div>
                    </template>
                </div>

                <!-- Actions - only show when there's an action available -->
                <div v-if="isPlayer && (canConfirm || canComplete || liveMatch.status === 'completed')" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-6">
                    <!-- Confirm Match -->
                    <div v-if="canConfirm">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-2">Ready to Play?</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-4">
                            Both players have joined. Confirm to start the match.
                        </p>
                        <Button
                            variant="primary"
                            size="md"
                            :loading="matchStore.isLoading"
                            class="w-full sm:text-base"
                            data-testid="start-match-button"
                            @click="handleConfirm"
                        >
                            Start Match
                        </Button>
                    </div>

                    <!-- Select Winner -->
                    <div v-else-if="canComplete">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-2">Who Won?</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-4">
                            Select the winner to complete the match and update ratings.
                        </p>
                        <div class="grid grid-cols-2 gap-2 sm:gap-3">
                            <Button
                                v-for="player in liveMatch.players"
                                :key="player.userId"
                                variant="secondary"
                                size="md"
                                :loading="matchStore.isLoading"
                                :data-testid="`select-winner-${player.userId}`"
                                @click="handleSelectWinner(player.userId)"
                            >
                                {{ player.userName }} Won
                            </Button>
                        </div>
                    </div>

                    <!-- Match Complete Summary -->
                    <div v-else-if="liveMatch.status === 'completed'">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-2">Match Complete!</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            Ratings have been updated. Check the leaderboard to see the new standings.
                        </p>
                    </div>
                </div>

                <!-- Cancel Match -->
                <div v-if="canCancel" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-6">
                    <template v-if="!showCancelConfirm">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-2">Cancel Match?</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-4">
                            {{ liveMatch.players.length < 2
                                ? 'No opponent has joined yet. You can cancel this match.'
                                : 'This will cancel the match for all players.' }}
                        </p>
                        <Button
                            variant="danger"
                            size="md"
                            class="w-full sm:text-base"
                            data-testid="cancel-match-button"
                            @click="showCancelConfirm = true"
                        >
                            Cancel Match
                        </Button>
                    </template>
                    <template v-else>
                        <h3 class="text-base sm:text-lg font-medium text-red-600 dark:text-red-400 mb-2">Are you sure?</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-4">
                            This action cannot be undone. The match will be permanently cancelled.
                        </p>
                        <div class="flex gap-3">
                            <Button
                                variant="secondary"
                                size="md"
                                class="flex-1"
                                @click="showCancelConfirm = false"
                            >
                                Go Back
                            </Button>
                            <Button
                                variant="danger"
                                size="md"
                                :loading="matchStore.isLoading"
                                class="flex-1"
                                data-testid="confirm-cancel-button"
                                @click="handleCancel"
                            >
                                Yes, Cancel
                            </Button>
                        </div>
                    </template>
                </div>

                <!-- Game Rules -->
                <div v-if="liveMatch.gameRules && liveMatch.gameRules.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-6">
                    <button
                        @click="showRules = !showRules"
                        class="w-full flex items-center justify-between text-left"
                    >
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ liveMatch.gameName }} Rules
                        </h3>
                        <svg
                            class="h-5 w-5 text-gray-500 dark:text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': showRules }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <ul v-show="showRules" class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li v-for="(rule, index) in liveMatch.gameRules" :key="index" class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex items-center justify-center text-xs font-medium">
                                {{ index + 1 }}
                            </span>
                            <span>{{ rule }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Error Display -->
                <div v-if="matchStore.error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 sm:p-4">
                    <p class="text-xs sm:text-sm text-red-600 dark:text-red-400">{{ matchStore.error }}</p>
                </div>
            </div>
        </div>

        <!-- Invite Player Modal -->
        <div v-if="showInviteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-80 transition-opacity" @click="closeInviteModal"></div>
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Invite Player</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Search for a player to invite to this match.</p>
                        </div>

                        <div class="relative">
                            <TextInput
                                v-model="searchQuery"
                                type="text"
                                class="w-full"
                                placeholder="Search by name or email..."
                                autofocus
                            />
                            <div v-if="isSearching" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Players List -->
                        <div class="mt-4 max-h-60 overflow-y-auto">
                            <!-- Loading state -->
                            <div v-if="isLoadingPlayers || isSearching" class="text-center py-6">
                                <svg class="animate-spin h-8 w-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Loading players...</p>
                            </div>

                            <!-- Search results (when searching) -->
                            <template v-else-if="searchQuery.length >= 2">
                                <div v-if="searchResults.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                    No players found matching "{{ searchQuery }}"
                                </div>
                                <div v-else class="space-y-2">
                                    <button
                                        v-for="user in searchResults"
                                        :key="user.id"
                                        @click="handleInvite(user.id)"
                                        :disabled="inviting"
                                        class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                                    >
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                            <img v-if="user.avatar" :src="user.avatar" class="w-full h-full object-cover" :alt="user.name" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-gray-500 dark:text-gray-400 font-medium">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div class="text-left flex-1">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            <!-- Available players (default view) -->
                            <template v-else>
                                <div v-if="availablePlayers.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                    No players available to invite
                                </div>
                                <div v-else class="space-y-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 px-1 mb-2">Select a player to invite:</p>
                                    <button
                                        v-for="user in availablePlayers"
                                        :key="user.id"
                                        @click="handleInvite(user.id)"
                                        :disabled="inviting"
                                        class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                                    >
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                            <img v-if="user.avatar" :src="user.avatar" class="w-full h-full object-cover" :alt="user.name" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-gray-500 dark:text-gray-400 font-medium">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div class="text-left flex-1">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6">
                        <button
                            type="button"
                            @click="closeInviteModal"
                            class="w-full inline-flex justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Team switch transition */
.team-switch-move,
.team-switch-enter-active,
.team-switch-leave-active {
    transition: all 0.3s ease;
}

.team-switch-enter-from {
    opacity: 0;
    transform: translateX(-1rem);
}

.team-switch-leave-to {
    opacity: 0;
    transform: translateX(1rem);
}

.team-switch-leave-active {
    position: absolute;
    width: 100%;
}
</style>
