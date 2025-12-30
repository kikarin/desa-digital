<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Aduan Masyarakat', href: '/aduan-saya' },
    { title: 'Edit Aduan', href: `/aduan-saya/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Aduan Masyarakat" :breadcrumbs="breadcrumbs" back-url="/aduan-saya">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

