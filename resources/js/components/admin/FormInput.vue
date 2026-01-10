<template>
    <div class="mb-4">
        <!-- Label -->
        <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Input Field -->
        <div class="relative">
            <!-- Icon (left) -->
            <div v-if="icon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <component :is="icon" class="h-5 w-5 text-gray-400" />
            </div>

            <!-- Input -->
            <input
                :id="id"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                :class="inputClasses"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur', $event)"
                @focus="$emit('focus', $event)"
            />

            <!-- Loading Spinner -->
            <div v-if="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

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
        default: () => `input-${Math.random().toString(36).substr(2, 9)}`,
    },
    label: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'text',
    },
    modelValue: {
        type: [String, Number],
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
    error: {
        type: String,
        default: null,
    },
    helper: {
        type: String,
        default: null,
    },
    icon: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

const inputClasses = computed(() => [
    'block w-full rounded-lg border shadow-sm transition-colors duration-200',
    'focus:ring-2 focus:ring-offset-0',
    props.icon ? 'pl-10 pr-3 py-2' : 'px-3 py-2',
    props.error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
        : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500',
    props.disabled
        ? 'bg-gray-50 text-gray-500 cursor-not-allowed'
        : 'bg-white text-gray-900',
]);
</script>
