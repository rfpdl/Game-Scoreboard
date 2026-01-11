/// <reference path="../../types/generated.d.ts" />

export type User = App.Data.UserData;
export type Game = App.Data.GameData;
export type PlayerRating = App.Data.PlayerRatingData;
export type MatchPlayer = App.Data.MatchPlayerData;
export type Match = App.Data.MatchData;
export type LeaderboardEntry = App.Data.LeaderboardEntryData;

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    branding: {
        name: string;
        logoUrl: string | null;
        primaryColor: string;
    };
    registrationEnabled: boolean;
};
