<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Aduan Masyarakat', href: '/aduan-saya' },
    { title: 'Tambah Aduan', href: '/aduan-saya/create' },
];
</script>

<template>
    <PageCreate title="Tambah Aduan Masyarakat" :breadcrumbs="breadcrumbs" back-url="/aduan-saya">
        <Form mode="create" />
    </PageCreate>
</template>

