<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Berita & Pengumuman', href: '/berita-pengumuman' },
    { title: 'Edit Berita/Pengumuman', href: `/berita-pengumuman/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Berita & Pengumuman" :breadcrumbs="breadcrumbs" back-url="/berita-pengumuman">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

