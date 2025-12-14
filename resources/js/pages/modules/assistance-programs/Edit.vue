<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
    { title: 'Edit Program Bantuan', href: `/program-bantuan/program-bantuan/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Program Bantuan" :breadcrumbs="breadcrumbs" back-url="/program-bantuan/program-bantuan">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

