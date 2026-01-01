<script setup lang="ts">
import Form from '../Form.vue';
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';

const breadcrumbs = [
    { title: 'Pengajuan Proposal Saya', href: '#' },
    { title: 'Edit Pengajuan Proposal', href: '/pengajuan-proposal-saya/edit' },
];

const props = defineProps<{
    item: Record<string, any>;
    listKategoriProposal?: Record<number, string>;
    listResident?: Array<{ id: number; label: string }>;
}>();
</script>

<template>
    <PageEdit title="Edit Pengajuan Proposal" :breadcrumbs="breadcrumbs" back-url="/pengajuan-proposal-saya">
        <Form mode="edit" :initial-data="item" :list-kategori-proposal="listKategoriProposal" :list-resident="listResident" />
    </PageEdit>
</template>

