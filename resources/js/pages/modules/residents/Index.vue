<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';

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
    { title: 'Warga', href: '/data-warga/residents' },
];

const columns = [
    { key: 'nik', label: 'NIK', searchable: true, orderable: true, visible: true },
    { key: 'nama', label: 'Nama', searchable: true, orderable: true, visible: true },
    { key: 'tempat_lahir', label: 'Tempat Lahir', searchable: true, orderable: false, visible: true },
    { key: 'tanggal_lahir', label: 'Tanggal Lahir', searchable: false, orderable: true, visible: true },
    { key: 'jenis_kelamin', label: 'Jenis Kelamin', searchable: false, orderable: true, visible: true },
    {
        key: 'status',
        label: 'Status',
        searchable: true,
        orderable: true,
        visible: true,
        format: (row: any) => {
            const status = row.status?.toUpperCase();
            if (status === 'AKTIF') {
                return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">AKTIF</span>';
            } else if (status === 'PINDAH') {
                return '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-900 dark:text-yellow-200">PINDAH</span>';
            } else if (status === 'MENINGGAL') {
                return '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-200">MENINGGAL</span>';
            }
            return row.status || '-';
        },
    },
    { key: 'no_kk', label: 'No. KK', searchable: true, orderable: true, visible: true },
    { key: 'nomor_rumah', label: 'Nomor Rumah', searchable: true, orderable: true, visible: true },
    { key: 'rt', label: 'RT', searchable: true, orderable: true, visible: true },
    { key: 'rw', label: 'RW', searchable: true, orderable: true, visible: true },
    { key: 'desa', label: 'Desa', searchable: true, orderable: true, visible: true },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();
const filterOptions = ref<{ 
    rw?: Array<{ value: number; label: string }>; 
    rt?: Array<{ value: number; label: string }>; 
    status?: Array<{ value: number; label: string }>; 
}>({});

onMounted(async () => {
    try {
        const response = await axios.get('/api/residents');
        if (response.data.filterOptions) {
            filterOptions.value = response.data.filterOptions;
        }
    } catch (error) {
        console.error('Gagal mengambil filter options:', error);
    }
});

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/data-warga/residents/${row.id}`),
        permission: props.can?.Detail,
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/data-warga/residents/${row.id}/edit`),
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
        const response = await axios.post('/data-warga/residents/destroy-selected', {
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

const deleteResidents = async (row: any) => {
    await router.delete(`/data-warga/residents/${row.id}`, {
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
        title="Warga"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/data-warga/residents/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/residents"
        ref="pageIndex"
        :on-delete-row-confirm="deleteResidents"
        :can="props.can"
        :show-filter="true"
        :filter-options="filterOptions"
    />
</template>

