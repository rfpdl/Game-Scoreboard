import { defineComponent, type PropType, computed } from 'vue';

type AvatarSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

export default defineComponent({
    name: 'Avatar',
    props: {
        src: {
            type: String as PropType<string | null | undefined>,
            default: null,
        },
        name: {
            type: String,
            required: true,
        },
        size: {
            type: String as PropType<AvatarSize>,
            default: 'md',
        },
    },
    setup(props) {
        const sizeClasses: Record<AvatarSize, string> = {
            xs: 'h-6 w-6 text-xs',
            sm: 'h-8 w-8 text-sm',
            md: 'h-10 w-10 text-base',
            lg: 'h-12 w-12 text-lg',
            xl: 'h-16 w-16 text-xl',
        };

        const initials = computed(() => {
            return props.name
                .split(' ')
                .map(word => word[0])
                .join('')
                .toUpperCase()
                .slice(0, 2);
        });

        const bgColor = computed(() => {
            const colors = [
                'bg-red-500',
                'bg-orange-500',
                'bg-amber-500',
                'bg-yellow-500',
                'bg-lime-500',
                'bg-green-500',
                'bg-emerald-500',
                'bg-teal-500',
                'bg-cyan-500',
                'bg-sky-500',
                'bg-blue-500',
                'bg-indigo-500',
                'bg-violet-500',
                'bg-purple-500',
                'bg-fuchsia-500',
                'bg-pink-500',
            ];
            const hash = props.name.split('').reduce((acc, char) => {
                return char.charCodeAt(0) + ((acc << 5) - acc);
            }, 0);
            return colors[Math.abs(hash) % colors.length];
        });

        return () => (
            <div
                class={[
                    'inline-flex items-center justify-center rounded-full overflow-hidden flex-shrink-0',
                    sizeClasses[props.size],
                ]}
            >
                {props.src ? (
                    <img
                        src={props.src}
                        alt={props.name}
                        class="h-full w-full object-cover"
                    />
                ) : (
                    <span
                        class={[
                            'flex items-center justify-center h-full w-full font-medium text-white',
                            bgColor.value,
                        ]}
                    >
                        {initials.value}
                    </span>
                )}
            </div>
        );
    },
});
