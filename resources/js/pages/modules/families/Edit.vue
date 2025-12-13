<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
    listHouse?: { value: number; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Kartu Keluarga', href: '/data-warga/families' },
    { title: 'Edit Kartu Keluarga', href: `/data-warga/families/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Kartu Keluarga" :breadcrumbs="breadcrumbs" back-url="/data-warga/families">
        <Form mode="edit" :initial-data="item" :list-house="listHouse" />
    </PageEdit>
</template>

