import { defineComponent, Transition, TransitionGroup } from 'vue';
import { useToastStore, type ToastType } from '@/stores/toast';

export default defineComponent({
    name: 'Toast',
    setup() {
        const toastStore = useToastStore();

        const getIcon = (type: ToastType) => {
            switch (type) {
                case 'success':
                    return (
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    );
                case 'error':
                    return (
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    );
                case 'warning':
                    return (
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    );
                case 'info':
                default:
                    return (
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    );
            }
        };

        const getStyles = (type: ToastType) => {
            switch (type) {
                case 'success':
                    return 'bg-green-50 border-green-200 text-green-800';
                case 'error':
                    return 'bg-red-50 border-red-200 text-red-800';
                case 'warning':
                    return 'bg-yellow-50 border-yellow-200 text-yellow-800';
                case 'info':
                default:
                    return 'bg-blue-50 border-blue-200 text-blue-800';
            }
        };

        const getIconStyles = (type: ToastType) => {
            switch (type) {
                case 'success':
                    return 'text-green-500';
                case 'error':
                    return 'text-red-500';
                case 'warning':
                    return 'text-yellow-500';
                case 'info':
                default:
                    return 'text-blue-500';
            }
        };

        return () => (
            <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
                <TransitionGroup
                    enterActiveClass="transition-all duration-300 ease-out"
                    enterFromClass="opacity-0 translate-x-full"
                    enterToClass="opacity-100 translate-x-0"
                    leaveActiveClass="transition-all duration-200 ease-in"
                    leaveFromClass="opacity-100 translate-x-0"
                    leaveToClass="opacity-0 translate-x-full"
                >
                    {toastStore.toasts.map((toast) => (
                        <div
                            key={toast.id}
                            class={[
                                'pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-lg border shadow-lg',
                                getStyles(toast.type),
                            ]}
                        >
                            <span class={['flex-shrink-0 mt-0.5', getIconStyles(toast.type)]}>
                                {getIcon(toast.type)}
                            </span>
                            <p class="flex-1 text-sm font-medium">{toast.message}</p>
                            <button
                                onClick={() => toastStore.remove(toast.id)}
                                class="flex-shrink-0 opacity-70 hover:opacity-100 transition-opacity"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    ))}
                </TransitionGroup>
            </div>
        );
    },
});
