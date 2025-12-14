<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    can?: {
        Add?: boolean;
        Edit?: boolean;
        Delete?: boolean;
        Detail?: boolean;
        Penyaluran?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Program Bantuan', href: '#' },
];

const columns = [
    { key: 'nama_program', label: 'Nama Program', searchable: true, orderable: true, visible: true },
    { key: 'tahun', label: 'Tahun', searchable: false, orderable: true, visible: true },
    { key: 'periode', label: 'Periode', searchable: true, orderable: true, visible: true },
    {
        key: 'target_penerima',
        label: 'Target Penerima',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            if (row.target_penerima === 'KELUARGA') {
                return '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-200">KELUARGA</span>';
            } else {
                return '<span class="px-2 py-1 text-xs font-semibold text-purple-800 bg-purple-100 rounded-full dark:bg-purple-900 dark:text-purple-200">INDIVIDU</span>';
            }
        },
    },
    {
        key: 'status',
        label: 'Status',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            if (row.status === 'SELESAI') {
                return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">SELESAI</span>';
            } else {
                return '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-900 dark:text-yellow-200">PROSES</span>';
            }
        },
    },
    {
        key: 'items_count',
        label: 'Item',
        searchable: false,
        orderable: false,
        visible: true,
        format: (row: any) => {
            const count = row.items_count || 0;
            return `<a href="/program-bantuan/item-program?program_id=${row.id}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer transition-colors">${count} Item</a>`;
        },
    },
    {
        key: 'recipients_count',
        label: 'Penerima',
        searchable: false,
        orderable: false,
        visible: true,
        format: (row: any) => {
            const count = row.recipients_count || 0;
            const targetType = row.target_penerima || 'KELUARGA';
            const label = targetType === 'KELUARGA' ? 'Keluarga' : 'Individu';
            return `<a href="/program-bantuan/penerima?program_id=${row.id}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-600 text-white hover:bg-green-700 cursor-pointer transition-colors">${count} ${label}</a>`;
        },
    },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

// Listen untuk event refresh dari Item Program
const handleRefreshEvent = () => {
    if (pageIndex.value) {
        pageIndex.value.fetchData();
    }
};

// Listen untuk visibility change (saat user kembali ke tab/halaman)
const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible' && pageIndex.value) {
        // Refresh data saat halaman menjadi visible
        pageIndex.value.fetchData();
    }
};

onMounted(() => {
    window.addEventListener('refresh-assistance-programs-index', handleRefreshEvent);
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    window.removeEventListener('refresh-assistance-programs-index', handleRefreshEvent);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});

const actions = (row: any) => {
    const actionsList = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/program-bantuan/program-bantuan/${row.id}`),
            permission: props.can?.Detail,
        },
        {
            label: 'Edit',
            onClick: () => router.visit(`/program-bantuan/program-bantuan/${row.id}/edit`),
            permission: props.can?.Edit,
        },
    ];

    // Tambahkan action Penyaluran jika ada permission dan program status bukan SELESAI
    if (props.can?.Penyaluran && row.status !== 'SELESAI') {
        actionsList.push({
            label: 'Penyaluran',
            onClick: () => router.visit(`/program-bantuan/penyaluran?program_id=${row.id}`),
            permission: props.can?.Penyaluran,
        });
    }

    actionsList.push({
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: props.can?.Delete,
    });

    return actionsList;
};

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/program-bantuan/program-bantuan/destroy-selected', {
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

const deleteProgram = async (row: any) => {
    await router.delete(`/program-bantuan/program-bantuan/${row.id}`, {
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
        title="Program Bantuan"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/program-bantuan/program-bantuan/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/assistance-programs"
        ref="pageIndex"
        :on-delete-row-confirm="deleteProgram"
        :can="props.can"
    />
</template>

