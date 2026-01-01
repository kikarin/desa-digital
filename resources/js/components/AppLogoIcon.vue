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
    <img
        src="/storage/Lambang_Kabupaten_Bogor.png"
        alt="Logo Kabupaten Bogor"
        class="h-16 w-auto object-contain"
    />
</template>
