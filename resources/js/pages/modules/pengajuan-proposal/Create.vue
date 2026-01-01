<script setup lang="ts">
import Form from './Form.vue';
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';

const breadcrumbs = [
    { title: 'Pengajuan Proposal', href: '#' },
    { title: 'Tambah Pengajuan Proposal', href: '/pengajuan-proposal/create' },
];
</script>

<template>
    <PageCreate title="Tambah Pengajuan Proposal" :breadcrumbs="breadcrumbs" back-url="/pengajuan-proposal">
        <Form mode="create" />
    </PageCreate>
</template>

