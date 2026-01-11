import { defineComponent, ref, watch, nextTick } from 'vue';

export default defineComponent({
    name: 'MatchCodeInput',
    props: {
        modelValue: {
            type: String,
            default: '',
        },
        error: {
            type: String as () => string | null,
            default: null,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['update:modelValue', 'complete'],
    setup(props, { emit }) {
        const CODE_LENGTH = 6;
        const inputs = ref<HTMLInputElement[]>([]);
        const values = ref<string[]>(Array(CODE_LENGTH).fill(''));

        watch(
            () => props.modelValue,
            (newValue) => {
                const chars = (newValue || '').toUpperCase().split('');
                values.value = Array(CODE_LENGTH)
                    .fill('')
                    .map((_, i) => chars[i] || '');
            },
            { immediate: true }
        );

        const handleInput = (index: number, event: Event) => {
            const target = event.target as HTMLInputElement;
            const value = target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');

            if (value.length > 1) {
                const chars = value.split('');
                chars.forEach((char, i) => {
                    if (index + i < CODE_LENGTH) {
                        values.value[index + i] = char;
                    }
                });
                const newIndex = Math.min(index + chars.length, CODE_LENGTH - 1);
                nextTick(() => inputs.value[newIndex]?.focus());
            } else {
                values.value[index] = value;
                if (value && index < CODE_LENGTH - 1) {
                    nextTick(() => inputs.value[index + 1]?.focus());
                }
            }

            const code = values.value.join('');
            emit('update:modelValue', code);

            if (code.length === CODE_LENGTH) {
                emit('complete', code);
            }
        };

        const handleKeydown = (index: number, event: KeyboardEvent) => {
            if (event.key === 'Backspace' && !values.value[index] && index > 0) {
                nextTick(() => inputs.value[index - 1]?.focus());
            } else if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                inputs.value[index - 1]?.focus();
            } else if (event.key === 'ArrowRight' && index < CODE_LENGTH - 1) {
                event.preventDefault();
                inputs.value[index + 1]?.focus();
            }
        };

        const handlePaste = (event: ClipboardEvent) => {
            event.preventDefault();
            const pastedText = event.clipboardData
                ?.getData('text')
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .slice(0, CODE_LENGTH);

            if (pastedText) {
                values.value = Array(CODE_LENGTH)
                    .fill('')
                    .map((_, i) => pastedText[i] || '');
                const code = values.value.join('');
                emit('update:modelValue', code);
                if (code.length === CODE_LENGTH) {
                    emit('complete', code);
                }
            }
        };

        const setInputRef = (el: HTMLInputElement | null, index: number) => {
            if (el) inputs.value[index] = el;
        };

        return () => (
            <div class="w-full">
                <div class="flex justify-center gap-2">
                    {values.value.map((value, index) => (
                        <input
                            key={index}
                            ref={(el) => setInputRef(el as HTMLInputElement, index)}
                            type="text"
                            maxlength="6"
                            value={value}
                            disabled={props.disabled}
                            class={[
                                'w-12 h-14 text-center text-2xl font-mono font-bold rounded-lg border-2 transition-colors',
                                'focus:outline-none focus:ring-2 focus:ring-offset-0',
                                props.error
                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                                    : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-200',
                                props.disabled && 'bg-gray-50 text-gray-500 cursor-not-allowed',
                            ]}
                            onInput={(e) => handleInput(index, e)}
                            onKeydown={(e) => handleKeydown(index, e)}
                            onPaste={handlePaste}
                        />
                    ))}
                </div>
                {props.error && (
                    <p class="mt-2 text-sm text-red-600 text-center">{props.error}</p>
                )}
            </div>
        );
    },
});
