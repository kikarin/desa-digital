<script setup lang="ts">
import Form from '../Form.vue';
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';

const breadcrumbs = [
    { title: 'Pengajuan Proposal Saya', href: '#' },
    { title: 'Tambah Pengajuan Proposal', href: '/pengajuan-proposal-saya/create' },
];

const props = defineProps<{
    listKategoriProposal?: Record<number, string>;
    listResident?: Array<{ id: number; label: string }>;
}>();
</script>

<template>
    <PageCreate title="Tambah Pengajuan Proposal" :breadcrumbs="breadcrumbs" back-url="/pengajuan-proposal-saya">
        <Form mode="create" :list-kategori-proposal="listKategoriProposal" :list-resident="listResident" />
    </PageCreate>
</template>

