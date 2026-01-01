<script setup lang="ts">
import Form from './Form.vue';
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';

const breadcrumbs = [
    { title: 'Pengajuan Proposal', href: '#' },
    { title: 'Edit Pengajuan Proposal', href: '/pengajuan-proposal/edit' },
];

const props = defineProps<{
    item: Record<string, any>;
}>();
</script>

<template>
    <PageEdit title="Edit Pengajuan Proposal" :breadcrumbs="breadcrumbs" back-url="/pengajuan-proposal">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

