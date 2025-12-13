<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    title: string;
    breadcrumbs: BreadcrumbItem[];
    backUrl?: string;
}>();

const emit = defineEmits(['cancel']);

const handleCancel = () => {
    emit('cancel');
    if (props.backUrl) {
        router.visit(props.backUrl);
    } else {
        router.visit('/');
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <div class="grid grid-cols-1 lg:grid-cols-12">
                <div class="col-span-1 lg:col-span-7 lg:col-start-1">
                    <Card class="w-full">
                        <CardHeader class="flex items-center justify-between">
                            <CardTitle class="text-xl">Edit {{ title }}</CardTitle>
                            <Button variant="secondary" @click="handleCancel"> ← Back </Button>
                        </CardHeader>
                        <CardContent>
                            <slot />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
