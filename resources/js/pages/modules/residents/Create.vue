<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

defineProps<{
    listFamily?: { value: number; label: string }[];
    listStatus?: { value: number; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Warga', href: '/data-warga/residents' },
    { title: 'Tambah Warga', href: '/data-warga/residents/create' },
];
</script>

<template>
    <PageCreate title="Tambah Warga" :breadcrumbs="breadcrumbs" back-url="/data-warga/residents">
        <Form mode="create" :list-family="listFamily" :list-status="listStatus" />
    </PageCreate>
</template>

