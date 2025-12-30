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
        Verifikasi?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Aduan Masyarakat', href: '/aduan-masyarakat' },
];

const columns = [
    { key: 'kategori_aduan_nama', label: 'Kategori', searchable: true, orderable: false, visible: true },
    { key: 'judul', label: 'Judul', searchable: true, orderable: true, visible: true },
    { 
        key: 'created_by_user', 
        label: 'Pelapor', 
        searchable: false, 
        orderable: false, 
        visible: true,
        format: (row: any) => {
            return row.created_by_user?.name || '-';
        }
    },
    {
        key: 'status',
        label: 'Status',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            if (row.status === 'selesai') {
                return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">SELESAI</span>';
            } else if (row.status === 'dibatalkan') {
                return '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-200">DIBATALKAN</span>';
            } else {
                return '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-900 dark:text-yellow-200">MENUNGGU VERIFIKASI</span>';
            }
        },
    },
    {
        key: 'jenis_aduan',
        label: 'Jenis',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            return row.jenis_aduan === 'publik' 
                ? '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">PUBLIK</span>'
                : '<span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded-full">PRIVATE</span>';
        },
    },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => {
    const actionList = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/aduan-masyarakat/${row.id}`),
            permission: props.can?.Detail,
        },
    ];

    // Tambahkan Verifikasi jika status menunggu_verifikasi
    if (row.status === 'menunggu_verifikasi' && props.can?.Verifikasi) {
        actionList.push({
            label: 'Verifikasi',
            onClick: () => router.visit(`/aduan-masyarakat/${row.id}/verifikasi`),
            permission: true,
        });
    }

    // Tambahkan Delete jika belum selesai (admin hanya bisa delete, tidak bisa edit)
    if (row.status !== 'selesai' && props.can?.Delete) {
        actionList.push({
            label: 'Delete',
            onClick: () => pageIndex.value.handleDeleteRow(row),
            permission: true,
        });
    }

    return actionList;
};

const handleDeleteRow = async (row: any) => {
    await router.delete(`/aduan-masyarakat/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/aduan-masyarakat/destroy-selected', {
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
        title="Aduan Masyarakat"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="''"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/aduan-masyarakat"
        :on-delete-row-confirm="handleDeleteRow"
        ref="pageIndex"
        :can="props.can"
        :can-create="false"
    />
</template>

