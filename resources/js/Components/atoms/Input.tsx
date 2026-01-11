import { defineComponent, type PropType, ref } from 'vue';

type InputType = 'text' | 'email' | 'password' | 'number' | 'tel' | 'url';
type InputSize = 'sm' | 'md' | 'lg';

export default defineComponent({
    name: 'Input',
    props: {
        modelValue: {
            type: [String, Number] as PropType<string | number>,
            default: '',
        },
        type: {
            type: String as PropType<InputType>,
            default: 'text',
        },
        placeholder: {
            type: String,
            default: '',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        error: {
            type: String as PropType<string | null>,
            default: null,
        },
        size: {
            type: String as PropType<InputSize>,
            default: 'md',
        },
        label: {
            type: String as PropType<string | null>,
            default: null,
        },
        id: {
            type: String as PropType<string | null>,
            default: null,
        },
    },
    emits: ['update:modelValue', 'blur', 'focus'],
    setup(props, { emit }) {
        const inputRef = ref<HTMLInputElement | null>(null);

        const sizeClasses: Record<InputSize, string> = {
            sm: 'px-2.5 py-1.5 text-sm',
            md: 'px-3 py-2 text-sm',
            lg: 'px-4 py-2.5 text-base',
        };

        const baseClasses = 'block w-full rounded-md border shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-0';
        const normalClasses = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500';
        const errorClasses = 'border-red-300 focus:border-red-500 focus:ring-red-500';
        const disabledClasses = 'bg-gray-50 text-gray-500 cursor-not-allowed';

        const handleInput = (e: Event) => {
            const target = e.target as HTMLInputElement;
            emit('update:modelValue', props.type === 'number' ? Number(target.value) : target.value);
        };

        const focus = () => {
            inputRef.value?.focus();
        };

        return () => (
            <div class="w-full">
                {props.label && (
                    <label
                        for={props.id ?? undefined}
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        {props.label}
                    </label>
                )}
                <input
                    ref={inputRef}
                    id={props.id ?? undefined}
                    type={props.type}
                    value={props.modelValue}
                    placeholder={props.placeholder}
                    disabled={props.disabled}
                    class={[
                        baseClasses,
                        sizeClasses[props.size],
                        props.error ? errorClasses : normalClasses,
                        props.disabled && disabledClasses,
                    ]}
                    onInput={handleInput}
                    onBlur={(e) => emit('blur', e)}
                    onFocus={(e) => emit('focus', e)}
                />
                {props.error && (
                    <p class="mt-1 text-sm text-red-600">{props.error}</p>
                )}
            </div>
        );
    },
});
