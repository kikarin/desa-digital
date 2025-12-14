<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Filter } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
    createUrl?: string;
    createMultipleUrl?: string;
    selected: number[];
    onDeleteSelected: () => void;
    canCreate?: boolean;
    canCreateMultiple?: boolean;
    canDelete?: boolean;
    showFilter?: boolean;
    onFilterClick?: () => void;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-semibold tracking-tight">
            {{ title }}
        </h1>

        <div class="flex flex-wrap items-center gap-2">
            <Button 
                v-if="props.showFilter"
                variant="outline" 
                size="sm"
                @click="onFilterClick"
            >
                <Filter class="h-4 w-4 mr-2" />
                Filter
            </Button>

            <Link v-if="props.createUrl && props.canCreate !== false" :href="props.createUrl">
                <Button variant="outline" size="sm">+ Create</Button>
            </Link>

            <Link v-if="props.createMultipleUrl && props.canCreateMultiple !== false" :href="props.createMultipleUrl">
                <Button variant="outline" size="sm">+ Create Multiple</Button>
            </Link>

            <Button 
                v-if="props.canDelete !== false"
                variant="destructive" 
                size="sm" 
                :disabled="selected.length === 0" 
                @click="onDeleteSelected"
            >
                Delete Selected ({{ selected.length }})
            </Button>
        </div>
    </div>
</template>
