<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: {
        id: number;
        jenis_surat_id: number;
        nama_atribut: string;
        tipe_data: string;
        opsi_pilihan: string;
        is_required: boolean;
        nama_lampiran: string;
        minimal_file: number;
        is_required_lampiran: boolean;
        urutan: number;
    };
    jenis_surat_options?: Record<number, string>;
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Atribut Jenis Surat', href: '/layanan-surat/atribut-jenis-surat' },
    { title: 'Edit Atribut', href: `/layanan-surat/atribut-jenis-surat/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Edit Atribut Jenis Surat" :breadcrumbs="breadcrumbs" :back-url="`/layanan-surat/atribut-jenis-surat/${props.item.id}`">
        <Form mode="edit" :initial-data="item" :jenis_surat_options="jenis_surat_options" />
    </PageEdit>
</template>

