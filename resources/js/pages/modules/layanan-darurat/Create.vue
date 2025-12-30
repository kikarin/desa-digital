<script setup lang="ts">
import Form from './Form.vue';
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';

const breadcrumbs = [
    { title: 'Layanan Darurat', href: '#' },
    { title: 'Tambah Layanan Darurat', href: '/layanan-darurat/create' },
];
</script>

<template>
    <PageCreate title="Tambah Layanan Darurat" :breadcrumbs="breadcrumbs" back-url="/layanan-darurat">
        <Form mode="create" />
    </PageCreate>
</template>

