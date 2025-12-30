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
    { title: 'Bank Sampah', href: '/bank-sampah' },
];

const columns = [
    { key: 'nama_lokasi', label: 'Nama Lokasi', searchable: true, orderable: true, visible: true },
    { key: 'alamat', label: 'Alamat', searchable: true, orderable: true, visible: true },
    { key: 'title', label: 'Title', searchable: true, orderable: true, visible: true },
    {
        key: 'foto',
        label: 'Foto',
        searchable: false,
        orderable: false,
        visible: true,
        format: (row: any) => {
            if (row.foto) {
                return `<img src="${row.foto}" alt="${row.title}" class="w-16 h-16 object-cover rounded" />`;
            }
            return '-';
        },
    },
    {
        key: 'deskripsi',
        label: 'Deskripsi',
        searchable: true,
        orderable: false,
        visible: true,
        format: (row: any) => {
            if (row.deskripsi) {
                const truncated = row.deskripsi.length > 50 ? row.deskripsi.substring(0, 50) + '...' : row.deskripsi;
                return truncated;
            }
            return '-';
        },
    },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/bank-sampah/${row.id}`),
        permission: props.can?.Detail,
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/bank-sampah/${row.id}/edit`),
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
        const response = await axios.post('/bank-sampah/destroy-selected', {
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

const deleteRow = async (row: any) => {
    await router.delete(`/bank-sampah/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageIndex
        title="Bank Sampah"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/bank-sampah/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/bank-sampah"
        ref="pageIndex"
        :on-delete-row-confirm="deleteRow"
        :can="props.can"
    />
</template>

