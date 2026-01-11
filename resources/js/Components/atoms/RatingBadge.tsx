import { defineComponent, type PropType, computed } from 'vue';

type RatingSize = 'sm' | 'md' | 'lg';

export default defineComponent({
    name: 'RatingBadge',
    props: {
        rating: {
            type: Number,
            required: true,
        },
        change: {
            type: Number as PropType<number | null>,
            default: null,
        },
        size: {
            type: String as PropType<RatingSize>,
            default: 'md',
        },
        showTrend: {
            type: Boolean,
            default: true,
        },
    },
    setup(props) {
        const sizeClasses: Record<RatingSize, string> = {
            sm: 'text-sm px-2 py-0.5',
            md: 'text-base px-3 py-1',
            lg: 'text-lg px-4 py-1.5',
        };

        const ratingTier = computed(() => {
            if (props.rating >= 2000) return { name: 'Master', color: 'bg-purple-100 text-purple-800 border-purple-200' };
            if (props.rating >= 1800) return { name: 'Diamond', color: 'bg-cyan-100 text-cyan-800 border-cyan-200' };
            if (props.rating >= 1600) return { name: 'Platinum', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
            if (props.rating >= 1400) return { name: 'Gold', color: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
            if (props.rating >= 1200) return { name: 'Silver', color: 'bg-gray-100 text-gray-800 border-gray-200' };
            return { name: 'Bronze', color: 'bg-orange-100 text-orange-800 border-orange-200' };
        });

        const changeDisplay = computed(() => {
            if (props.change === null || props.change === 0) return null;
            const sign = props.change > 0 ? '+' : '';
            const color = props.change > 0 ? 'text-green-600' : 'text-red-600';
            return { value: `${sign}${props.change}`, color };
        });

        return () => (
            <div class="inline-flex items-center gap-1.5">
                <span
                    class={[
                        'inline-flex items-center font-semibold rounded-md border',
                        sizeClasses[props.size],
                        ratingTier.value.color,
                    ]}
                >
                    {props.rating}
                </span>
                {props.showTrend && changeDisplay.value && (
                    <span class={['text-sm font-medium', changeDisplay.value.color]}>
                        {changeDisplay.value.value}
                    </span>
                )}
            </div>
        );
    },
});
