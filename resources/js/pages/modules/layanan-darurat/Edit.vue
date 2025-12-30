<script setup lang="ts">
import Form from './Form.vue';
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';

const breadcrumbs = [
    { title: 'Layanan Darurat', href: '#' },
    { title: 'Edit Layanan Darurat', href: '/layanan-darurat/edit' },
];

const props = defineProps<{
    item: Record<string, any>;
}>();
</script>

<template>
    <PageEdit title="Edit Layanan Darurat" :breadcrumbs="breadcrumbs" back-url="/layanan-darurat">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

