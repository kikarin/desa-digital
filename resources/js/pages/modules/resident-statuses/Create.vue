<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '#' },
    { title: 'Status Warga', href: '/data-master/resident-statuses' },
    { title: 'Tambah Status Warga', href: '/data-master/resident-statuses/create' },
];
</script>

<template>
    <PageCreate title="Tambah Status Warga" :breadcrumbs="breadcrumbs" back-url="/data-master/resident-statuses">
        <Form mode="create" />
    </PageCreate>
</template>

