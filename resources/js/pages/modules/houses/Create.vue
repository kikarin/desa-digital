<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

defineProps<{
    listRt?: { value: number; label: string }[];
    listResidents?: { value: number; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Rumah', href: '/data-warga/houses' },
    { title: 'Tambah Rumah', href: '/data-warga/houses/create' },
];
</script>

<template>
    <PageCreate title="Tambah Rumah" :breadcrumbs="breadcrumbs" back-url="/data-warga/houses">
        <Form mode="create" :list-rt="listRt" :list-residents="listResidents" />
    </PageCreate>
</template>

