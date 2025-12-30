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
        'Export PDF'?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Pengajuan Surat', href: '/layanan-surat/pengajuan-surat' },
];

const columns = [
    { key: 'jenis_surat_nama', label: 'Jenis Surat', searchable: true, orderable: false, visible: true },
    { 
        key: 'resident_nama', 
        label: 'Warga', 
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
    { key: 'resident_nik', label: 'NIK', searchable: true, orderable: false, visible: false },
    { key: 'tanggal_surat', label: 'Tanggal Surat', searchable: false, orderable: true, visible: true },
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
            } else if (row.status === 'diperbaiki') {
                return '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-200">DIPERBAIKI</span>';
            } else {
                return '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-900 dark:text-yellow-200">MENUNGGU</span>';
            }
        },
    },
    { key: 'nomor_surat', label: 'Nomor Surat', searchable: true, orderable: false, visible: true },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => {
    const actionList = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/layanan-surat/pengajuan-surat/${row.id}`),
            permission: props.can?.Detail,
        },
    ];

    // Tambahkan Verifikasi jika status menunggu atau diperbaiki
    if ((row.status === 'menunggu' || row.status === 'diperbaiki') && props.can?.Verifikasi) {
        actionList.push({
            label: 'Verifikasi',
            onClick: () => router.visit(`/layanan-surat/pengajuan-surat/${row.id}/verifikasi`),
            permission: true,
        });
    }

    // Tambahkan Export PDF jika status disetujui
    if (row.status === 'disetujui' && props.can?.['Export PDF']) {
        actionList.push({
            label: 'Export PDF',
            onClick: () => window.location.href = `/layanan-surat/pengajuan-surat/${row.id}/export-pdf`,
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

const handleDeleteRow = async (row: any) => {
    await router.delete(`/layanan-surat/pengajuan-surat/${row.id}`, {
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
        const response = await axios.post('/layanan-surat/pengajuan-surat/destroy-selected', {
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
        title="Pengajuan Surat"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="''"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        :api-endpoint="'/api/pengajuan-surat'"
        :can-create="false"
        :on-delete-row-confirm="handleDeleteRow"
        ref="pageIndex"
    />
</template>

