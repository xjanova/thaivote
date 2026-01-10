import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

/**
 * Toast notification composable
 * @returns {Object} { toasts, success, error, warning, info, remove }
 */
export function useToast() {
    const addToast = (message, type = 'info', duration = 5000) => {
        const id = ++toastId;
        const toast = {
            id,
            message,
            type,
            visible: true,
        };

        toasts.value.push(toast);

        if (duration > 0) {
            setTimeout(() => {
                remove(id);
            }, duration);
        }

        return id;
    };

    const remove = (id) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index > -1) {
            toasts.value[index].visible = false;
            setTimeout(() => {
                toasts.value.splice(index, 1);
            }, 300); // Wait for animation
        }
    };

    const success = (message, duration) => addToast(message, 'success', duration);

    const error = (message, duration) => addToast(message, 'error', duration);

    const warning = (message, duration) => addToast(message, 'warning', duration);

    const info = (message, duration) => addToast(message, 'info', duration);

    return {
        toasts,
        success,
        error,
        warning,
        info,
        remove,
    };
}
