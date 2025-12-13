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
    { title: 'Rumah', href: '/data-warga/houses' },
];

const columns = [
    { key: 'nomor_rumah', label: 'Nomor Rumah', searchable: true, orderable: true, visible: true },
    { key: 'jenis_rumah', label: 'Jenis Rumah', searchable: true, orderable: true, visible: true },
    {
        key: 'total_residents',
        label: 'Jumlah Warga',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            return `<span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900 dark:text-blue-200">${row.total_residents || 0}</span>`;
        },
    },
    { key: 'rt', label: 'RT', searchable: true, orderable: true, visible: true },
    { key: 'rw', label: 'RW', searchable: true, orderable: true, visible: true },
    { key: 'desa', label: 'Desa', searchable: true, orderable: true, visible: true },
    { key: 'kecamatan', label: 'Kecamatan', searchable: true, orderable: true, visible: true },
    { key: 'kabupaten', label: 'Kabupaten', searchable: true, orderable: true, visible: true },
    { key: 'keterangan', label: 'Keterangan', searchable: true, orderable: false, visible: true },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();
const filterOptions = ref<{ rw?: Array<{ value: number; label: string }>; rt?: Array<{ value: number; label: string }>; jenis_rumah?: Array<{ value: string; label: string }> }>({});

onMounted(async () => {
    try {
        const response = await axios.get('/api/houses');
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
        onClick: () => router.visit(`/data-warga/houses/${row.id}`),
        permission: props.can?.Detail,
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/data-warga/houses/${row.id}/edit`),
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
        const response = await axios.post('/data-warga/houses/destroy-selected', {
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

const deleteHouses = async (row: any) => {
    await router.delete(`/data-warga/houses/${row.id}`, {
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
        title="Rumah"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/data-warga/houses/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/houses"
        ref="pageIndex"
        :on-delete-row-confirm="deleteHouses"
        :can="props.can"
        :show-filter="true"
        :filter-options="filterOptions"
    />
</template>

