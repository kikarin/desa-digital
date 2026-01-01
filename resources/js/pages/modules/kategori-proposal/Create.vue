<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '#' },
    { title: 'Kategori Proposal', href: '/data-master/kategori-proposal' },
    { title: 'Tambah Kategori Proposal', href: '/data-master/kategori-proposal/create' },
];
</script>

<template>
    <PageCreate title="Tambah Kategori Proposal" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-proposal">
        <Form mode="create" />
    </PageCreate>
</template>

