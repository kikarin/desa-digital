<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
    listFamily?: { value: number; label: string }[];
    listStatus?: { value: number; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Warga', href: '/data-warga/residents' },
    { title: 'Edit Warga', href: `/data-warga/residents/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Warga" :breadcrumbs="breadcrumbs" back-url="/data-warga/residents">
        <Form mode="edit" :initial-data="item" :list-family="listFamily" :list-status="listStatus" />
    </PageEdit>
</template>

