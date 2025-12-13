<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/vue3';

defineProps<{
    title: string;
    breadcrumbs: BreadcrumbItem[];
    backUrl?: string;
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <!-- <HeaderIndex :title="title" :back-url="backUrl" /> -->
            <div class="grid grid-cols-1 lg:grid-cols-12">
                <div class="col-span-1 lg:col-span-7 lg:col-start-1">
                    <Card class="w-full">
                        <CardHeader class="flex items-center justify-between">
                            <CardTitle class="text-xl">{{ title }}</CardTitle>
                            <Button variant="secondary" @click="backUrl ? router.visit(backUrl) : router.visit('/')"> ← Back </Button>
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
