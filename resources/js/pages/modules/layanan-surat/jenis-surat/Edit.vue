<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: {
        id: number;
        nama: string;
        kode: string;
    };
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Jenis Surat', href: '/layanan-surat/jenis-surat' },
    { title: 'Edit Jenis Surat', href: `/layanan-surat/jenis-surat/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Edit Jenis Surat" :breadcrumbs="breadcrumbs" :back-url="`/layanan-surat/jenis-surat/${props.item.id}`">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

