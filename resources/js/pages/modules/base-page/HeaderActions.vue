<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Filter, Upload } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
    createUrl?: string;
    createMultipleUrl?: string;
    bulkAssignDesilUrl?: string;
    importUrl?: string;
    onImportClick?: () => void;
    selected: number[];
    onDeleteSelected: () => void;
    canCreate?: boolean;
    canCreateMultiple?: boolean;
    canBulkAssignDesil?: boolean;
    canDelete?: boolean;
    canImport?: boolean;
    showFilter?: boolean;
    onFilterClick?: () => void;
    canDeleteSelected?: boolean;
}>();

const emit = defineEmits(['import']);

const handleImportClick = () => {
    if (props.onImportClick) {
        props.onImportClick();
    } else {
        emit('import');
    }
};
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

            <Button 
                v-if="props.importUrl && props.canImport !== false"
                variant="outline" 
                size="sm"
                @click="handleImportClick"
            >
                <Upload class="h-4 w-4 mr-2" />
                Import Excel
            </Button>

            <Link v-if="props.createUrl && props.canCreate !== false" :href="props.createUrl">
                <Button variant="secondary" size="sm">+ Create</Button>
            </Link>

            <Link v-if="props.createMultipleUrl && props.canCreateMultiple !== false" :href="props.createMultipleUrl">
                <Button variant="outline" size="sm">+ Create Multiple</Button>
            </Link>

            <Link v-if="props.bulkAssignDesilUrl && props.canBulkAssignDesil !== false" :href="props.bulkAssignDesilUrl">
                <Button variant="outline" size="sm">Bulk Assign Desil</Button>
            </Link>

            <Button 
                v-if="props.canDelete !== false && props.canDeleteSelected !== true"
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
