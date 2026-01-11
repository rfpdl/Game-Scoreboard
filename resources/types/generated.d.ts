declare namespace App.Data {
export type GameData = {
id: number | null;
name: string;
slug: string;
description: string | null;
rules: Array<any> | null;
icon: string | null;
minPlayers: number;
maxPlayers: number;
isActive: boolean;
};
export type LeaderboardEntryData = {
rank: number;
userId: number;
userName: string;
userAvatar: string | null;
rating: number;
matchesPlayed: number;
wins: number;
losses: number;
winRate: number;
winStreak: number;
};
export type MatchData = {
id: number | null;
uuid: string;
gameId: number;
matchCode: string;
matchType: string;
matchFormat: string;
maxPlayers: number;
name: string | null;
scheduledAt: string | null;
shareUrl: string | null;
status: string;
createdByUserId: number;
playedAt: string | null;
gameName: string | null;
gameIcon: string | null;
gameRules: Array<any> | null;
createdByName: string | null;
players: Array<App.Data.MatchPlayerData>;
};
export type MatchPlayerData = {
id: number | null;
matchId: number;
userId: number;
team: string | null;
result: string;
placement: number | null;
ratingBefore: number | null;
ratingAfter: number | null;
ratingChange: number | null;
confirmedAt: string | null;
userName: string | null;
userAvatar: string | null;
userNickname: string | null;
};
export type PlayerRatingData = {
id: number | null;
uuid: string;
userId: number;
gameId: number;
rating: number;
matchesPlayed: number;
wins: number;
losses: number;
winStreak: number;
bestRating: number;
winRate: number;
userName: string | null;
gameName: string | null;
gameIcon: string | null;
};
export type UserData = {
id: number;
name: string;
nickname: string | null;
displayName: string;
email: string;
isAdmin: boolean;
avatar: string | null;
emailVerifiedAt: string | null;
};
}
