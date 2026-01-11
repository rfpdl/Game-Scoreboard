import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface Branding {
    name: string;
    logoUrl: string | null;
    primaryColor: string;
}

export function useBranding() {
    const page = usePage();

    const branding = computed<Branding>(() => {
        return (page.props.branding as Branding) || {
            name: 'Game Scoreboard',
            logoUrl: null,
            primaryColor: '#f97316',
        };
    });

    const appName = computed(() => branding.value.name);
    const logoUrl = computed(() => branding.value.logoUrl);
    const primaryColor = computed(() => branding.value.primaryColor);
    const hasLogoImage = computed(() => !!branding.value.logoUrl);

    // Generate hover/active color variants (darken by ~10%)
    const primaryColorHover = computed(() => {
        const hex = branding.value.primaryColor.replace('#', '');
        const r = Math.max(0, parseInt(hex.substring(0, 2), 16) - 16);
        const g = Math.max(0, parseInt(hex.substring(2, 4), 16) - 16);
        const b = Math.max(0, parseInt(hex.substring(4, 6), 16) - 16);
        return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
    });

    const primaryColorActive = computed(() => {
        const hex = branding.value.primaryColor.replace('#', '');
        const r = Math.max(0, parseInt(hex.substring(0, 2), 16) - 25);
        const g = Math.max(0, parseInt(hex.substring(2, 4), 16) - 25);
        const b = Math.max(0, parseInt(hex.substring(4, 6), 16) - 25);
        return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
    });

    // For backgrounds with opacity
    const primaryColorLight = computed(() => `${branding.value.primaryColor}1A`); // 10% opacity

    return {
        branding,
        appName,
        logoUrl,
        primaryColor,
        primaryColorHover,
        primaryColorActive,
        primaryColorLight,
        hasLogoImage,
    };
}
