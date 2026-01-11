import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
    id: number;
    message: string;
    type: ToastType;
    duration: number;
}

export const useToastStore = defineStore('toast', () => {
    const toasts = ref<Toast[]>([]);
    let nextId = 0;

    function show(message: string, type: ToastType = 'info', duration: number = 4000) {
        const id = nextId++;
        toasts.value.push({ id, message, type, duration });

        if (duration > 0) {
            setTimeout(() => {
                remove(id);
            }, duration);
        }

        return id;
    }

    function success(message: string, duration?: number) {
        return show(message, 'success', duration);
    }

    function error(message: string, duration?: number) {
        return show(message, 'error', duration ?? 6000);
    }

    function warning(message: string, duration?: number) {
        return show(message, 'warning', duration);
    }

    function info(message: string, duration?: number) {
        return show(message, 'info', duration);
    }

    function remove(id: number) {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    }

    function clear() {
        toasts.value = [];
    }

    return {
        toasts,
        show,
        success,
        error,
        warning,
        info,
        remove,
        clear,
    };
});
