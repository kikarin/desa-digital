<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '#' },
    { title: 'Kategori Aduan', href: '/data-master/kategori-aduan' },
    { title: 'Tambah Kategori Aduan', href: '/data-master/kategori-aduan/create' },
];
</script>

<template>
    <PageCreate title="Tambah Kategori Aduan" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-aduan">
        <Form mode="create" />
    </PageCreate>
</template>

