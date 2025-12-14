<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '#' },
    { title: 'Item Bantuan', href: '/data-master/assistance-items' },
    { title: 'Tambah Item Bantuan', href: '/data-master/assistance-items/create' },
];
</script>

<template>
    <PageCreate title="Tambah Item Bantuan" :breadcrumbs="breadcrumbs" back-url="/data-master/assistance-items">
        <Form mode="create" />
    </PageCreate>
</template>

