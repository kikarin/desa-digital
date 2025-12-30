<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: {
        id: number;
        jenis_surat_id: number;
        resident_id: number;
        tanggal_surat: string;
        status: string;
    };
    jenis_surat_options?: Record<number, string>;
    resident_options?: Record<number, string>;
    atribut?: Record<number, { nilai: string; lampiran_files: string[] }>;
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Pengajuan Saya', href: '/layanan-surat/pengajuan-saya' },
    { title: 'Edit Pengajuan', href: `/layanan-surat/pengajuan-saya/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Edit Pengajuan Surat" :breadcrumbs="breadcrumbs" :back-url="`/layanan-surat/pengajuan-saya/${props.item.id}`">
        <Form
            mode="edit"
            :item="item"
            :initial-data="item"
            :jenis_surat_options="jenis_surat_options"
            :resident_options="resident_options"
            :atribut="atribut"
        />
    </PageEdit>
</template>

