<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Pengajuan Proposal Saya', href: '/pengajuan-proposal-saya' },
];

const columns = [
    { key: 'kategori_proposal_nama', label: 'Kategori', searchable: true, orderable: false, visible: true },
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

const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => {
    const actionList = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/pengajuan-proposal-saya/${row.id}`),
            permission: true,
        },
    ];

    // Tambahkan Edit jika bisa diedit (status menunggu_verifikasi atau ditolak)
    if (row.status === 'menunggu_verifikasi' || row.status === 'ditolak') {
        actionList.push({
            label: 'Edit',
            onClick: () => router.visit(`/pengajuan-proposal-saya/${row.id}/edit`),
            permission: true,
        });
    }

    return actionList;
};
</script>

<template>
    <PageIndex
        title="Pengajuan Proposal Saya"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/pengajuan-proposal-saya/create'"
        :actions="actions"
        :api-endpoint="'/api/pengajuan-proposal-saya'"
        :can-create="true"
        :hide-checkbox="true"
        ref="pageIndex"
    />
</template>

