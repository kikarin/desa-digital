<script setup lang="ts">
import { computed, inject } from 'vue';
import { cn } from '@/lib/utils';
import { Circle } from 'lucide-vue-next';

const props = defineProps<{
  value: string;
  id?: string;
  disabled?: boolean;
  class?: string;
}>();

const radioGroupValue = inject<any>('radioGroupValue', null);
const radioGroupUpdate = inject<(value: string) => void>('radioGroupUpdate', () => {});
const radioGroupDisabled = inject<computed<boolean>>('radioGroupDisabled', computed(() => false));

const isChecked = computed(() => {
  if (!radioGroupValue) return false;
  return radioGroupValue.value === props.value;
});

const isDisabled = computed(() => props.disabled || radioGroupDisabled.value);

const handleClick = () => {
  if (!isDisabled.value) {
    radioGroupUpdate(props.value);
  }
};
</script>

<template>
  <button
    :id="id"
    type="button"
    role="radio"
    :aria-checked="isChecked"
    :disabled="isDisabled"
    :class="cn(
      'relative aspect-square h-4 w-4 rounded-full border border-primary text-primary ring-offset-background focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
      isChecked && 'border-primary',
      props.class
    )"
    @click="handleClick"
  >
    <span
      v-if="isChecked"
      class="absolute inset-0 flex items-center justify-center"
    >
      <Circle class="h-2.5 w-2.5 fill-current text-current" />
    </span>
  </button>
</template>

