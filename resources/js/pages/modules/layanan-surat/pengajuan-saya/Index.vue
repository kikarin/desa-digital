<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Pengajuan Saya', href: '/layanan-surat/pengajuan-saya' },
];

const columns = [
    { key: 'jenis_surat_nama', label: 'Jenis Surat', searchable: true, orderable: false, visible: true },
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

const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => {
    const actionList = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/layanan-surat/pengajuan-saya/${row.id}`),
            permission: true,
        },
    ];

    // Tambahkan Edit jika bisa diedit
    if (row.can_be_edited) {
        actionList.push({
            label: 'Edit',
            onClick: () => router.visit(`/layanan-surat/pengajuan-saya/${row.id}/edit`),
            permission: true,
        });
    }

    return actionList;
};
</script>

<template>
    <PageIndex
        title="Pengajuan Saya"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/layanan-surat/pengajuan-saya/create'"
        :actions="actions"
        :api-endpoint="'/api/pengajuan-saya'"
        :can-create="true"
        :hide-checkbox="true"
        :can-delete-selected="true"
        ref="pageIndex"
    />
</template>

