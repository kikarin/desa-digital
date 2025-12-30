<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps<{
    can?: {
        Add?: boolean;
        Edit?: boolean;
        Delete?: boolean;
        Detail?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Atribut Jenis Surat', href: '/layanan-surat/atribut-jenis-surat' },
];

const columns = [
    { key: 'jenis_surat_nama', label: 'Jenis Surat', searchable: true, orderable: false, visible: true },
    { key: 'nama_atribut', label: 'Nama Atribut', searchable: true, orderable: true, visible: true },
    { key: 'tipe_data', label: 'Tipe Data', searchable: false, orderable: true, visible: true },
    { key: 'is_required', label: 'Wajib', searchable: false, orderable: false, visible: true },
    { key: 'urutan', label: 'Urutan', searchable: false, orderable: true, visible: true },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/layanan-surat/atribut-jenis-surat/${row.id}`),
        permission: props.can?.Detail,
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/layanan-surat/atribut-jenis-surat/${row.id}/edit`),
        permission: props.can?.Edit,
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: props.can?.Delete,
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/layanan-surat/atribut-jenis-surat/destroy-selected', {
            ids: selected.value,
        });

        selected.value = [];
        pageIndex.value.fetchData();

        toast({
            title: response.data?.message || 'Data berhasil dihapus',
            variant: 'success',
        });
    } catch (error: any) {
        console.error('Gagal menghapus data:', error);
        const message = error.response?.data?.message || 'Gagal menghapus data';
        toast({
            title: message,
            variant: 'destructive',
        });
    }
};
</script>

<template>
    <PageIndex
        title="Atribut Jenis Surat"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/layanan-surat/atribut-jenis-surat/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        :api-endpoint="'/api/atribut-jenis-surat'"
        :can-create="can?.Add"
        ref="pageIndex"
    />
</template>

