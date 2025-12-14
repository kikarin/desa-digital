<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
    { title: 'Tambah Program Bantuan', href: '/program-bantuan/program-bantuan/create' },
];
</script>

<template>
    <PageCreate title="Tambah Program Bantuan" :breadcrumbs="breadcrumbs" back-url="/program-bantuan/program-bantuan">
        <Form mode="create" />
    </PageCreate>
</template>

