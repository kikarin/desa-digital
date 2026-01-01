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
    { title: 'Pengajuan Proposal', href: '/pengajuan-proposal' },
];

const columns = [
    { key: 'kategori_proposal_nama', label: 'Kategori', searchable: true, orderable: false, visible: true },
    { 
        key: 'resident_nama', 
        label: 'Pemohon', 
        searchable: true, 
        orderable: false, 
        visible: true,
        format: (row: any) => {
            if (row.resident_nama && row.resident_nik) {
                return `${row.resident_nama} (${row.resident_nik})`;
            }
            return row.resident_nama || row.resident_nik || '-';
        }
    },
    { key: 'nama_kegiatan', label: 'Nama Kegiatan', searchable: true, orderable: true, visible: true },
    { key: 'usulan_anggaran_formatted', label: 'Usulan Anggaran', searchable: false, orderable: true, visible: true },
    {
        key: 'status',
        label: 'Status',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            if (row.status === 'disetujui') {
                return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">DISETUJUI</span>';
            } else if (row.status === 'ditolak') {
                return '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-200">DITOLAK</span>';
            } else {
                return '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-900 dark:text-yellow-200">MENUNGGU VERIFIKASI</span>';
            }
        },
    },
    { key: 'created_at', label: 'Tanggal Dibuat', searchable: false, orderable: true, visible: true },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => {
    const actionList = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/pengajuan-proposal/${row.id}`),
            permission: props.can?.Detail,
        },
    ];

    // Tambahkan Verifikasi jika status menunggu verifikasi
    if (row.status === 'menunggu_verifikasi' && props.can?.Verifikasi) {
        actionList.push({
            label: 'Verifikasi',
            onClick: () => router.visit(`/pengajuan-proposal/${row.id}/verifikasi`),
            permission: true,
        });
    }

    // Tambahkan Delete jika belum disetujui
    if (row.status !== 'disetujui' && props.can?.Delete) {
        actionList.push({
            label: 'Delete',
            onClick: () => pageIndex.value.handleDeleteRow(row),
            permission: true,
        });
    }

    return actionList;
};

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/pengajuan-proposal/destroy-selected', {
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
    await router.delete(`/pengajuan-proposal/${row.id}`, {
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
        title="Pengajuan Proposal"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/pengajuan-proposal"
        ref="pageIndex"
        :on-delete-row-confirm="deleteRow"
        :can="props.can"
        :can-create="false"
    />
</template>

