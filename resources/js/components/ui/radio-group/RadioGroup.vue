<script setup lang="ts">
import { computed, provide } from 'vue';
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';

const props = defineProps<{
  defaultValue?: string;
  modelValue?: string;
  class?: string;
  disabled?: boolean;
}>();

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
});

provide('radioGroupValue', modelValue);
provide('radioGroupUpdate', (value: string) => {
  modelValue.value = value;
});
provide('radioGroupDisabled', computed(() => props.disabled));
</script>

<template>
  <div :class="cn('grid gap-2', props.class)">
    <slot />
  </div>
</template>

