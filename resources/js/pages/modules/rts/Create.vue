<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

defineProps<{
    listRw?: { value: number; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'RT', href: '/data-warga/rts' },
    { title: 'Tambah RT', href: '/data-warga/rts/create' },
];
</script>

<template>
    <PageCreate title="Tambah RT" :breadcrumbs="breadcrumbs" back-url="/data-warga/rts">
        <Form mode="create" :list-rw="listRw" />
    </PageCreate>
</template>

