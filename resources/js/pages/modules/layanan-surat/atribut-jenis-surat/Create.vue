<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const props = defineProps<{
    jenis_surat_options?: Record<number, string>;
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Atribut Jenis Surat', href: '/layanan-surat/atribut-jenis-surat' },
    { title: 'Tambah Atribut', href: '/layanan-surat/atribut-jenis-surat/create' },
];
</script>

<template>
    <PageCreate title="Tambah Atribut Jenis Surat" :breadcrumbs="breadcrumbs" back-url="/layanan-surat/atribut-jenis-surat">
        <Form mode="create" :jenis_surat_options="jenis_surat_options" />
    </PageCreate>
</template>

