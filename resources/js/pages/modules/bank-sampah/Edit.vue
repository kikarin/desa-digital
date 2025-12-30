<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Bank Sampah', href: '/bank-sampah' },
    { title: 'Edit Bank Sampah', href: `/bank-sampah/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Bank Sampah" :breadcrumbs="breadcrumbs" back-url="/bank-sampah">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

