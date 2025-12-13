<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Wilayah', href: '/data-warga/rws' },
    { title: 'Edit Wilayah', href: `/data-warga/rws/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Wilayah" :breadcrumbs="breadcrumbs" back-url="/data-warga/rws">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

