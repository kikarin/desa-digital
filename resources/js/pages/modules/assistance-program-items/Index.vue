<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps<{
    can?: {
        Add?: boolean;
        Edit?: boolean;
        Delete?: boolean;
        Detail?: boolean;
    };
}>();

// Get program_id from URL - reactive
const getProgramIdFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('program_id');
};

const programIdFromUrl = ref<string | null>(getProgramIdFromUrl());

// Watch untuk perubahan URL (saat back button) dan trigger refetch
watch(() => window.location.search, () => {
    const newProgramId = getProgramIdFromUrl();
    if (newProgramId !== programIdFromUrl.value) {
        programIdFromUrl.value = newProgramId;
        // Trigger refetch data jika pageIndex sudah ready
        if (pageIndex.value) {
            (pageIndex.value as any).fetchData();
        }
    }
}, { immediate: true });

const breadcrumbs = computed(() => {
    const base = [
        { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
        { title: 'Item Program', href: programIdFromUrl.value ? `/program-bantuan/item-program?program_id=${programIdFromUrl.value}` : '/program-bantuan/item-program' },
    ];
    return base;
});

const createUrl = computed(() => {
    if (programIdFromUrl.value) {
        return `/program-bantuan/item-program/create?program_id=${programIdFromUrl.value}`;
    }
    return '/program-bantuan/item-program/create';
});

// Hide kolom Program jika sudah di-filter by program_id
const columns = computed(() => {
    const cols = [];
    
    // Hanya tampilkan kolom Program jika tidak ada filter program_id
    if (!programIdFromUrl.value) {
        cols.push({ key: 'nama_program', label: 'Program', searchable: true, orderable: true, visible: true });
    }
    
    cols.push(
        { key: 'nama_item', label: 'Item', searchable: true, orderable: true, visible: true },
    {
        key: 'jumlah',
        label: 'Jumlah',
        searchable: false,
        orderable: true,
        visible: true,
        format: (row: any) => {
            const jumlah = parseFloat(row.jumlah);
            const satuan = row.satuan || '';
            
            // Jika satuan Rupiah atau jumlah >= 1000 (4 digit), format sebagai IDR
            if (satuan === 'Rupiah' || jumlah >= 1000) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                }).format(jumlah);
            }
            
            // Format number dengan separator untuk satuan lain
            return jumlah.toLocaleString('id-ID') + ' ' + satuan;
        },
    });
    
    return cols;
});

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();
const filterOptions = ref<{
    program?: Array<{ value: number; label: string }>;
}>({});

onMounted(async () => {
    try {
        // Fetch list program untuk filter
        const response = await axios.get('/api/assistance-programs', { params: { per_page: -1 } });
        if (response.data.data) {
            filterOptions.value.program = response.data.data.map((program: any) => ({
                value: program.id,
                label: `${program.nama_program} (${program.tahun} - ${program.periode})`,
            }));
        }
        
        // Set initial filter dari URL jika ada program_id
        if (programIdFromUrl.value && pageIndex.value) {
            // Set filter program_id di PageIndex
            const filters = (pageIndex.value as any).filters;
            if (filters) {
                filters.value.program_id = parseInt(programIdFromUrl.value);
                // Trigger fetchData
                (pageIndex.value as any).fetchData();
            }
        }
    } catch (error) {
        console.error('Gagal mengambil filter options:', error);
    }
});

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => {
            let detailUrl = `/program-bantuan/item-program/${row.id}`;
            if (programIdFromUrl.value) {
                detailUrl += `?program_id=${programIdFromUrl.value}`;
            }
            router.visit(detailUrl);
        },
        permission: props.can?.Detail,
    },
    {
        label: 'Edit',
        onClick: () => {
            let editUrl = `/program-bantuan/item-program/${row.id}/edit`;
            if (programIdFromUrl.value) {
                editUrl += `?program_id=${programIdFromUrl.value}`;
            }
            router.visit(editUrl);
        },
        permission: props.can?.Edit,
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: props.can?.Delete,
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/program-bantuan/item-program/destroy-selected', {
            ids: selected.value,
        });

        selected.value = [];
        pageIndex.value.fetchData();

        toast({
            title: response.data?.message || 'Data berhasil dihapus',
            variant: 'success',
        });
        
        // Trigger refresh di Program Bantuan index untuk update badge
        window.dispatchEvent(new CustomEvent('refresh-assistance-programs-index'));
    } catch (error: any) {
        console.error('Gagal menghapus data:', error);
        const message = error.response?.data?.message || 'Gagal menghapus data';
        toast({
            title: message,
            variant: 'destructive',
        });
    }
};

const deleteItem = async (row: any) => {
    await router.delete(`/program-bantuan/item-program/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
            // Trigger refresh di Program Bantuan index untuk update badge
            window.dispatchEvent(new CustomEvent('refresh-assistance-programs-index'));
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageIndex
        title="Item Program"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="createUrl"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/assistance-program-items"
        ref="pageIndex"
        :on-delete-row-confirm="deleteItem"
        :can="props.can"
        :show-filter="true"
        :filter-options="filterOptions"
    />
</template>

