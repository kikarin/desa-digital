<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

defineProps<{
    listHouse?: { value: number; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Kartu Keluarga', href: '/data-warga/families' },
    { title: 'Tambah Kartu Keluarga', href: '/data-warga/families/create' },
];
</script>

<template>
    <PageCreate title="Tambah Kartu Keluarga" :breadcrumbs="breadcrumbs" back-url="/data-warga/families">
        <Form mode="create" :list-house="listHouse" />
    </PageCreate>
</template>

