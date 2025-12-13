<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
    listRt?: { value: number; label: string }[];
    listResidents?: { value: number; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Rumah', href: '/data-warga/houses' },
    { title: 'Edit Rumah', href: `/data-warga/houses/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Rumah" :breadcrumbs="breadcrumbs" back-url="/data-warga/houses">
        <Form mode="edit" :initial-data="item" :list-rt="listRt" :list-residents="listResidents" />
    </PageEdit>
</template>

