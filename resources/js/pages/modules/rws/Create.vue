<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Wilayah', href: '/data-warga/rws' },
    { title: 'Tambah Wilayah', href: '/data-warga/rws/create' },
];
</script>

<template>
    <PageCreate title="Tambah Wilayah" :breadcrumbs="breadcrumbs" back-url="/data-warga/rws">
        <Form mode="create" />
    </PageCreate>
</template>

