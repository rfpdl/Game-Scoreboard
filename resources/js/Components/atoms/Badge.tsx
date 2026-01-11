import { defineComponent, type PropType } from 'vue';

type BadgeVariant = 'default' | 'success' | 'warning' | 'danger' | 'info';
type BadgeSize = 'sm' | 'md';

export default defineComponent({
    name: 'Badge',
    props: {
        variant: {
            type: String as PropType<BadgeVariant>,
            default: 'default',
        },
        size: {
            type: String as PropType<BadgeSize>,
            default: 'md',
        },
    },
    setup(props, { slots }) {
        const baseClasses = 'inline-flex items-center font-medium rounded-full';

        const variantClasses: Record<BadgeVariant, string> = {
            default: 'bg-gray-100 text-gray-800',
            success: 'bg-green-100 text-green-800',
            warning: 'bg-yellow-100 text-yellow-800',
            danger: 'bg-red-100 text-red-800',
            info: 'bg-blue-100 text-blue-800',
        };

        const sizeClasses: Record<BadgeSize, string> = {
            sm: 'px-2 py-0.5 text-xs',
            md: 'px-2.5 py-0.5 text-sm',
        };

        return () => (
            <span class={[baseClasses, variantClasses[props.variant], sizeClasses[props.size]]}>
                {slots.default?.()}
            </span>
        );
    },
});
