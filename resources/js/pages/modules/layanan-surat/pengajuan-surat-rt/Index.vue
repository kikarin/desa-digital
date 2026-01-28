<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    can?: {
        Verifikasi?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Verifikasi Pengajuan Surat RT', href: '/layanan-surat/pengajuan-surat-rt' },
];

const columns = [
    { key: 'jenis_surat_nama', label: 'Jenis Surat', searchable: true, orderable: false, visible: true },
    { key: 'resident_nama', label: 'Nama Warga', searchable: true, orderable: false, visible: true },
    { key: 'resident_nik', label: 'NIK', searchable: true, orderable: false, visible: true },
    { key: 'tanggal_surat', label: 'Tanggal Surat', searchable: false, orderable: true, visible: true },
    {
        key: 'status',
        label: 'Status',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            const statusMap: Record<string, { label: string; class: string }> = {
                menunggu: { label: 'Menunggu Verifikasi RT', class: 'px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full' },
                diverifikasi_rt: { label: 'Sudah Diverifikasi RT', class: 'px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full' },
                disetujui: { label: 'Disetujui', class: 'px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full' },
                ditolak: { label: 'Ditolak', class: 'px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full' },
                diperbaiki: { label: 'Diperbaiki', class: 'px-2 py-1 text-xs font-semibold text-orange-800 bg-orange-100 rounded-full' },
            };
            const status = statusMap[row.status] || { label: row.status, class: 'px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded-full' };
            return `<span class="${status.class}">${status.label}</span>`;
        },
    },
    { key: 'created_at', label: 'Tanggal Pengajuan', searchable: false, orderable: true, visible: true },
];

const actions = (row: any) => {
    const actionList = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/layanan-surat/pengajuan-surat-rt/${row.id}`),
            permission: true,
        },
    ];
    
    // Tambahkan action Verifikasi jika status masih menunggu
    if (row.status === 'menunggu' && props.can?.Verifikasi) {
        actionList.push({
            label: 'Verifikasi',
            onClick: () => router.visit(`/layanan-surat/pengajuan-surat-rt/${row.id}/verifikasi`),
            permission: props.can?.Verifikasi,
        });
    }
    
    return actionList;
};
</script>

<template>
    <PageIndex
        title="Verifikasi Pengajuan Surat RT"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :actions="actions"
        api-endpoint="/api/pengajuan-surat-rt"
        :can="props.can"
    />
</template>
