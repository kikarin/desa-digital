<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Pengajuan Saya', href: '/layanan-surat/pengajuan-saya' },
    { title: 'Buat Pengajuan', href: '/layanan-surat/pengajuan-saya/create' },
];

const props = defineProps<{
    jenis_surat_options?: Record<number, string>;
    resident_options?: Record<number, string>;
}>();
</script>

<template>
    <PageCreate title="Buat Pengajuan Surat" :breadcrumbs="breadcrumbs" back-url="/layanan-surat/pengajuan-saya">
        <Form mode="create" :jenis_surat_options="jenis_surat_options" :resident_options="resident_options" />
    </PageCreate>
</template>

