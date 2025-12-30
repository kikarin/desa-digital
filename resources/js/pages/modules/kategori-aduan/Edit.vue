<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '#' },
    { title: 'Kategori Aduan', href: '/data-master/kategori-aduan' },
    { title: 'Edit Kategori Aduan', href: `/data-master/kategori-aduan/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Kategori Aduan" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-aduan">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

