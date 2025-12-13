<script setup lang="ts">
import { Button } from '@/components/ui/button';

const emit = defineEmits(['save', 'cancel']);

const handleSave = (e: Event) => {
    e.preventDefault();
    emit('save', e);
};
</script>

<template>
    <div class="mt-4 flex justify-end gap-2">
        <Button type="submit" variant="default" @click="handleSave"> Save </Button>
    </div>
</template>
