<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '#' },
    { title: 'Item Bantuan', href: '/data-master/assistance-items' },
    { title: 'Edit Item Bantuan', href: `/data-master/assistance-items/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Item Bantuan" :breadcrumbs="breadcrumbs" back-url="/data-master/assistance-items">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

