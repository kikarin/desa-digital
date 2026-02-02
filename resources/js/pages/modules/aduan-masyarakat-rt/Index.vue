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
    { title: 'Aduan', href: '#' },
    { title: 'Verifikasi Aduan RT', href: '/aduan-masyarakat-rt' },
];

const columns = [
    { key: 'judul', label: 'Judul Aduan', searchable: true, orderable: true, visible: true },
    { key: 'kategori_aduan_nama', label: 'Kategori', searchable: true, orderable: false, visible: true },
    { key: 'created_by_user', label: 'Pelapor', searchable: true, orderable: false, visible: true, format: (row: any) => row.created_by_user?.name || '-' },
    {
        key: 'status',
        label: 'Status',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            const statusMap: Record<string, { label: string; class: string }> = {
                menunggu_verifikasi: { label: 'Menunggu Verifikasi RT', class: 'px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full' },
                diverifikasi_rt: { label: 'Sudah Diverifikasi RT', class: 'px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full' },
                diverifikasi_admin: { label: 'Diverifikasi Admin', class: 'px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full' },
                selesai: { label: 'Selesai', class: 'px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full' },
                dibatalkan: { label: 'Dibatalkan', class: 'px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full' },
            };
            const status = statusMap[row.status] || { label: row.status, class: 'px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded-full' };
            return `<span class="${status.class}">${status.label}</span>`;
        },
    },
    { key: 'created_at', label: 'Tanggal Aduan', searchable: false, orderable: true, visible: true },
];

const actions = (row: any) => {
    const actionList = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/aduan-masyarakat-rt/${row.id}`),
            permission: true,
        },
    ];
    
    // Tambahkan action Verifikasi jika status masih menunggu_verifikasi
    if (row.status === 'menunggu_verifikasi' && props.can?.Verifikasi) {
        actionList.push({
            label: 'Verifikasi',
            onClick: () => router.visit(`/aduan-masyarakat-rt/${row.id}/verifikasi`),
            permission: props.can?.Verifikasi,
        });
    }
    
    return actionList;
};
</script>

<template>
    <PageIndex
        title="Verifikasi Aduan RT"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :actions="actions"
        api-endpoint="/api/aduan-masyarakat-rt"
        :can="props.can"
    />
</template>
