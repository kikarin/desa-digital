<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Bank Sampah', href: '/bank-sampah' },
    { title: 'Tambah Bank Sampah', href: '/bank-sampah/create' },
];
</script>

<template>
    <PageCreate title="Bank Sampah" :breadcrumbs="breadcrumbs" back-url="/bank-sampah">
        <Form mode="create" />
    </PageCreate>
</template>

