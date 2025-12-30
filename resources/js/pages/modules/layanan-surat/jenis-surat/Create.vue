<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Jenis Surat', href: '/layanan-surat/jenis-surat' },
    { title: 'Tambah Jenis Surat', href: '/layanan-surat/jenis-surat/create' },
];
</script>

<template>
    <PageCreate title="Tambah Jenis Surat" :breadcrumbs="breadcrumbs" back-url="/layanan-surat/jenis-surat">
        <Form mode="create" />
    </PageCreate>
</template>

