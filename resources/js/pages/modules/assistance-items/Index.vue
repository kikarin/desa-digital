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
    { title: 'Data Master', href: '#' },
    { title: 'Item Bantuan', href: '/data-master/assistance-items' },
];

const columns = [
    { key: 'nama_item', label: 'Nama Item', searchable: true, orderable: true, visible: true },
    {
        key: 'tipe',
        label: 'Tipe',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            if (row.tipe === 'UANG') {
                return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">UANG</span>';
            } else {
                return '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-200">BARANG</span>';
            }
        },
    },
    { key: 'satuan', label: 'Satuan', searchable: true, orderable: true, visible: true },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/data-master/assistance-items/${row.id}`),
        permission: props.can?.Detail,
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/data-master/assistance-items/${row.id}/edit`),
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
        const response = await axios.post('/data-master/assistance-items/destroy-selected', {
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

const deleteItem = async (row: any) => {
    await router.delete(`/data-master/assistance-items/${row.id}`, {
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
        title="Item Bantuan"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/data-master/assistance-items/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/assistance-items"
        ref="pageIndex"
        :on-delete-row-confirm="deleteItem"
        :can="props.can"
    />
</template>

