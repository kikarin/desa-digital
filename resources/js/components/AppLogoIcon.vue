<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" :class="className" v-bind="$attrs">
        <!-- Lingkaran background -->
        <circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.1" />

        <!-- Ikon petir -->
        <path fill="currentColor" d="M13 2L6 14h5v8l7-12h-5z" />
    </svg>
</template>
