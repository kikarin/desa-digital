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
    { title: 'Status Warga', href: '/data-master/resident-statuses' },
];

const columns = [
    { key: 'code', label: 'Code', searchable: true, orderable: true, visible: true },
    { key: 'name', label: 'Name', searchable: true, orderable: true, visible: true },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/data-master/resident-statuses/${row.id}`),
        permission: props.can?.Detail,
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/data-master/resident-statuses/${row.id}/edit`),
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
        const response = await axios.post('/data-master/resident-statuses/destroy-selected', {
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

const deleteResidentStatus = async (row: any) => {
    await router.delete(`/data-master/resident-statuses/${row.id}`, {
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
        title="Status Warga"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/data-master/resident-statuses/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/resident-statuses"
        ref="pageIndex"
        :on-delete-row-confirm="deleteResidentStatus"
        :can="props.can"
    />
</template>

