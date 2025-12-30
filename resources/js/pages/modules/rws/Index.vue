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
    { title: 'Data Warga', href: '#' },
    { title: 'Tabel Wilayah', href: '/data-warga/rws' },
];

const columns = [
    { key: 'nomor_rw', label: 'Nomor RW', searchable: true, orderable: true, visible: true },
    { key: 'desa', label: 'Desa', searchable: true, orderable: true, visible: true },
    { key: 'kecamatan', label: 'Kecamatan', searchable: true, orderable: true, visible: true },
    { key: 'kabupaten', label: 'Kabupaten', searchable: true, orderable: true, visible: true },
    {
        key: 'has_account',
        label: 'Status Akun',
        searchable: false,
        orderable: false,
        visible: true,
        format: (row: any) => {
            return row.has_account
                ? '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Sudah Punya Akun</span>'
                : '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Belum Punya Akun</span>';
        },
    },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const handleCreateAccount = async (row: any) => {
    try {
        const response = await axios.post(`/data-warga/rws/${row.id}/create-account`);
        toast({
            title: response.data?.message || 'Akun berhasil dibuat',
            variant: 'success',
        });
        pageIndex.value.fetchData();
    } catch (error: any) {
        const message = error.response?.data?.message || 'Gagal membuat akun';
        toast({
            title: message,
            variant: 'destructive',
        });
    }
};

const actions = (row: any) => {
    const actionList = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/data-warga/rws/${row.id}`),
            permission: props.can?.Detail,
        },
        {
            label: 'Edit',
            onClick: () => router.visit(`/data-warga/rws/${row.id}/edit`),
            permission: props.can?.Edit,
        },
    ];
    
    // Tambahkan action Buat Akun jika belum punya akun
    if (!row.has_account) {
        actionList.push({
            label: 'Buat Akun',
            onClick: () => handleCreateAccount(row),
            permission: props.can?.Add, // Gunakan permission Add untuk create account
        });
    }
    
    actionList.push({
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: props.can?.Delete,
    });
    
    return actionList;
};

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/data-warga/rws/destroy-selected', {
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

const deleteRws = async (row: any) => {
    await router.delete(`/data-warga/rws/${row.id}`, {
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
        title="Wilayah"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/data-warga/rws/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/rws"
        ref="pageIndex"
        :on-delete-row-confirm="deleteRws"
        :can="props.can"
    />
</template>

