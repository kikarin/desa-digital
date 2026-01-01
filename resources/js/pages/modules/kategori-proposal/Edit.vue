<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '#' },
    { title: 'Kategori Proposal', href: '/data-master/kategori-proposal' },
    { title: 'Edit Kategori Proposal', href: `/data-master/kategori-proposal/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Kategori Proposal" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-proposal">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

