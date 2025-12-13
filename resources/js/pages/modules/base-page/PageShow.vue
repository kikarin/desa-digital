<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Info, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import HeaderShow from './HeaderShow.vue';

const props = defineProps<{
    title: string;
    breadcrumbs: BreadcrumbItem[];
    fields: { label: string; value: string; className?: string }[];
    actionFields?: { label: string; value: string }[];
    backUrl?: string;
    onDelete?: () => void;
    onEdit?: () => void;
}>();

const showDeleteDialog = ref(false);

const handleDelete = () => {
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (props.onDelete) {
        props.onDelete();
    }
    showDeleteDialog.value = false;
};
</script>

<template>
    <Head :title="`Detail ${title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <!-- Header & Action Buttons -->
            <HeaderShow :title="`Detail ${title}`">
                <!-- Only show if onEdit is provided -->
                <button
                    v-if="onEdit"
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="onEdit"
                >
                    <Pencil class="h-4 w-4" />
                    Edit
                </button>

                <!-- Always show Delete -->
                <button
                    v-if="onDelete"
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="handleDelete"
                >
                    <Trash2 class="h-4 w-4 text-red-500" />
                    Delete
                </button>

                <!-- Back -->
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(backUrl || '#')"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </button>
            </HeaderShow>

            <div class="grid grid-cols-12 gap-6">
                <!-- Information Panel -->
                <div class="col-span-12 md:col-span-8">
                    <div class="bg-card border-border rounded-2xl border shadow-sm">
                        <div class="border-border flex items-center gap-2 border-b px-6 py-4">
                            <Info class="text-muted-foreground h-4 w-4" />
                            <h2 class="text-muted-foreground text-sm font-semibold tracking-wide uppercase">Information</h2>
                        </div>

                        <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
                            <div v-for="field in fields" :key="field.label" class="space-y-1" :class="field.label === 'Data' ? 'sm:col-span-2' : ''">
                                <div class="text-muted-foreground text-xs">{{ field.label }}</div>
                                <div
                                    :class="['text-foreground text-sm font-semibold break-words whitespace-pre-wrap', field.className]"
                                    v-if="field.value && field.value.startsWith && field.value.startsWith('<div')"
                                    v-html="field.value"
                                ></div>
                                <div v-else :class="['text-foreground text-sm font-semibold break-words whitespace-pre-wrap', field.className]">
                                    {{ field.value }}
                                </div>
                            </div>
                        </div>

                        <div class="px-6 pb-6">
                            <slot name="custom" />
                        </div>
                    </div>
                </div>

                <!-- Action Time Panel -->
                <div class="col-span-12 md:col-span-4">
                    <div class="bg-card border-border rounded-2xl border shadow-sm">
                        <div class="border-border flex items-center gap-2 border-b px-6 py-4">
                            <Clock class="text-muted-foreground h-4 w-4" />
                            <h2 class="text-muted-foreground text-sm font-semibold tracking-wide uppercase">Action Time</h2>
                        </div>

                        <div class="grid grid-cols-1 gap-3 p-6">
                            <div v-for="field in actionFields" :key="field.label" class="space-y-1">
                                <div class="text-muted-foreground text-xs">{{ field.label }}</div>
                                <div class="text-foreground text-sm font-semibold break-words whitespace-pre-wrap">
                                    {{ field.value }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus data ini!</DialogTitle>
                    <DialogDescription> Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan. </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">Batal</Button>
                    <Button variant="destructive" @click="confirmDelete">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
