<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
    listRw?: { value: number; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'RT', href: '/data-warga/rts' },
    { title: 'Edit RT', href: `/data-warga/rts/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="RT" :breadcrumbs="breadcrumbs" back-url="/data-warga/rts">
        <Form mode="edit" :initial-data="item" :list-rw="listRw" />
    </PageEdit>
</template>

