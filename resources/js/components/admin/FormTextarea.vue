<template>
    <div class="mb-4">
        <!-- Label -->
        <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Textarea -->
        <textarea
            :id="id"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            :class="textareaClasses"
            @input="$emit('update:modelValue', $event.target.value)"
            @blur="$emit('blur', $event)"
            @focus="$emit('focus', $event)"
        ></textarea>

        <!-- Helper Text -->
        <p v-if="helper && !error" class="mt-1 text-sm text-gray-500">
            {{ helper }}
        </p>

        <!-- Error Message -->
        <p v-if="error" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: () => `textarea-${Math.random().toString(36).substr(2, 9)}`,
    },
    label: {
        type: String,
        default: null,
    },
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    rows: {
        type: Number,
        default: 4,
    },
    error: {
        type: String,
        default: null,
    },
    helper: {
        type: String,
        default: null,
    },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

const textareaClasses = computed(() => [
    'block w-full rounded-lg border shadow-sm transition-colors duration-200',
    'focus:ring-2 focus:ring-offset-0 px-3 py-2',
    props.error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
        : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500',
    props.disabled
        ? 'bg-gray-50 text-gray-500 cursor-not-allowed'
        : 'bg-white text-gray-900',
]);
</script>
