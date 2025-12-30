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
    { title: 'Berita & Pengumuman', href: '/berita-pengumuman' },
];

const columns = [
    {
        key: 'tipe',
        label: 'Tipe',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            return row.tipe === 'berita'
                ? '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">Berita</span>'
                : '<span class="px-2 py-1 text-xs font-semibold text-purple-800 bg-purple-100 rounded-full">Event</span>';
        },
    },
    { key: 'title', label: 'Title', searchable: true, orderable: true, visible: true },
    {
        key: 'foto',
        label: 'Foto',
        searchable: false,
        orderable: false,
        visible: true,
        format: (row: any) => {
            if (row.foto) {
                return `<img src="${row.foto}" alt="${row.title}" class="w-16 h-16 object-cover rounded" />`;
            }
            return '-';
        },
    },
    { key: 'tanggal', label: 'Tanggal', searchable: false, orderable: true, visible: true },
    {
        key: 'deskripsi',
        label: 'Deskripsi',
        searchable: true,
        orderable: false,
        visible: true,
        format: (row: any) => {
            if (row.deskripsi) {
                const truncated = row.deskripsi.length > 50 ? row.deskripsi.substring(0, 50) + '...' : row.deskripsi;
                return truncated;
            }
            return '-';
        },
    },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();
const filterOptions = ref<{ tipe?: Array<{ value: string; label: string }> }>({
    tipe: [
        { value: 'berita', label: 'Berita' },
        { value: 'event', label: 'Event' },
    ],
});

onMounted(async () => {
    try {
        const response = await axios.get('/api/berita-pengumuman');
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
        onClick: () => router.visit(`/berita-pengumuman/${row.id}`),
        permission: props.can?.Detail,
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/berita-pengumuman/${row.id}/edit`),
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
        const response = await axios.post('/berita-pengumuman/destroy-selected', {
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
    await router.delete(`/berita-pengumuman/${row.id}`, {
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
        title="Berita & Pengumuman"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/berita-pengumuman/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/berita-pengumuman"
        ref="pageIndex"
        :on-delete-row-confirm="deleteRow"
        :can="props.can"
        :show-filter="true"
        :filter-options="filterOptions"
    />
</template>

