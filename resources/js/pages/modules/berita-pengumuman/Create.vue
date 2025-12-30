<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Berita & Pengumuman', href: '/berita-pengumuman' },
    { title: 'Tambah Berita/Pengumuman', href: '/berita-pengumuman/create' },
];
</script>

<template>
    <PageCreate title="Berita & Pengumuman" :breadcrumbs="breadcrumbs" back-url="/berita-pengumuman">
        <Form mode="create" />
    </PageCreate>
</template>

