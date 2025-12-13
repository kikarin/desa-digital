<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '#' },
    { title: 'Status Warga', href: '/data-master/resident-statuses' },
    { title: 'Edit Status Warga', href: `/data-master/resident-statuses/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Status Warga" :breadcrumbs="breadcrumbs" back-url="/data-master/resident-statuses">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

