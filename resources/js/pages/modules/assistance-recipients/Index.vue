<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import DistributionModal from './DistributionModal.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';

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

// Watch untuk perubahan URL (saat back button)
watch(() => window.location.search, () => {
    const newProgramId = getProgramIdFromUrl();
    if (newProgramId !== programIdFromUrl.value) {
        programIdFromUrl.value = newProgramId;
        if (pageIndex.value) {
            (pageIndex.value as any).fetchData();
        }
    }
}, { immediate: true });

const breadcrumbs = computed(() => {
    const base = [
        { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
        { title: 'Penerima Bantuan', href: programIdFromUrl.value ? `/program-bantuan/penerima?program_id=${programIdFromUrl.value}` : '/program-bantuan/penerima' },
    ];
    return base;
});

const createUrl = computed(() => {
    // Untuk create multiple, wajib ada program_id
    if (programIdFromUrl.value) {
        return `/program-bantuan/penerima/create-multiple?program_id=${programIdFromUrl.value}`;
    }
    // Jika tidak ada program_id, arahkan ke halaman program bantuan untuk pilih program dulu
    return '/program-bantuan/program-bantuan';
});

const columns = computed(() => {
    const cols = [];

    // Hanya tampilkan kolom Program jika tidak ada filter program_id
    if (!programIdFromUrl.value) {
        cols.push({ key: 'nama_program', label: 'Program', searchable: true, orderable: true, visible: true });
    }

    cols.push(
        {
            key: 'target_type',
            label: 'Tipe',
            searchable: false,
            orderable: true,
            visible: true,
            format: (row: any) => {
                if (row.target_type === 'KELUARGA') {
                    return '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-200">KELUARGA</span>';
                } else {
                    return '<span class="px-2 py-1 text-xs font-semibold text-purple-800 bg-purple-100 rounded-full dark:bg-purple-900 dark:text-purple-200">INDIVIDU</span>';
                }
            },
        },
        {
            key: 'target_name',
            label: 'Penerima',
            searchable: true,
            orderable: false,
            visible: true,
            format: (row: any) => {
                if (row.target_type === 'KELUARGA' && row.family) {
                    return row.family.no_kk || '-';
                } else if (row.target_type === 'INDIVIDU' && row.resident) {
                    return row.resident.nama || '-';
                }
                return '-';
            },
        },
        {
            key: 'kepala_keluarga',
            label: 'Kepala Keluarga',
            searchable: false,
            orderable: false,
            visible: true,
            format: (row: any) => {
                if (row.kepala_keluarga && row.kepala_keluarga.nama) {
                    return row.kepala_keluarga.nama;
                }
                return '-';
            },
        },
        {
            key: 'rt',
            label: 'RT',
            searchable: false,
            orderable: false,
            visible: true,
            format: (row: any) => {
                if (row.target_type === 'KELUARGA' && row.family && row.family.rt) {
                    return row.family.rt;
                } else if (row.target_type === 'INDIVIDU' && row.resident && row.resident.rt) {
                    return row.resident.rt;
                }
                return '-';
            },
        },
        {
            key: 'rw',
            label: 'RW',
            searchable: false,
            orderable: false,
            visible: true,
            format: (row: any) => {
                if (row.target_type === 'KELUARGA' && row.family && row.family.rw) {
                    return row.family.rw;
                } else if (row.target_type === 'INDIVIDU' && row.resident && row.resident.rw) {
                    return row.resident.rw;
                }
                return '-';
            },
        },
        {
            key: 'status',
            label: 'Status',
            searchable: false,
            orderable: true,
            visible: true,
            format: (row: any) => {
                if (row.status === 'SELESAI' || row.status === 'DATANG') {
                    return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">' + row.status + '</span>';
                } else if (row.status === 'TIDAK_DATANG') {
                    return '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-200">TIDAK DATANG</span>';
                } else {
                    return '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-900 dark:text-yellow-200">PROSES</span>';
                }
            },
        },
        {
            key: 'tanggal_penyaluran',
            label: 'Tanggal Penyaluran',
            searchable: false,
            orderable: true,
            visible: true,
            format: (row: any) => {
                if (row.tanggal_penyaluran) {
                    return new Date(row.tanggal_penyaluran).toLocaleDateString('id-ID');
                }
                return '-';
            },
        }
    );

    return cols;
});

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();
const filterOptions = ref<{
    rw?: Array<{ value: number; label: string }>;
    rt?: Array<{ value: number; label: string; rw_id?: number }>;
}>({});

onMounted(async () => {
    try {
        const response = await axios.get('/api/assistance-recipients');
        if (response.data.filterOptions) {
            filterOptions.value = response.data.filterOptions;
        }
    } catch (error) {
        console.error('Gagal mengambil filter options:', error);
    }
});

// Listen untuk event refresh dari Create Multiple
const handleRefreshEvent = () => {
    if (pageIndex.value) {
        pageIndex.value.fetchData();
    }
};

onMounted(() => {
    window.addEventListener('refresh-assistance-recipients-index', handleRefreshEvent);
});

onUnmounted(() => {
    window.removeEventListener('refresh-assistance-recipients-index', handleRefreshEvent);
});

const actions = (row: any) => {
    const actionsList = [
        {
            label: 'Detail',
            onClick: () => {
                // Arahkan ke detail families atau resident berdasarkan target_type
                if (row.target_type === 'KELUARGA' && row.family_id) {
                    router.visit(`/data-warga/families/${row.family_id}`);
                } else if (row.target_type === 'INDIVIDU' && row.resident_id) {
                    router.visit(`/data-warga/residents/${row.resident_id}`);
                }
            },
            permission: props.can?.Detail,
        },
    ];

    // Tambahkan action Penyaluran jika status = PROSES (langsung buka modal, bukan redirect)
    if (row.status === 'PROSES' && props.can?.Edit) {
        actionsList.push({
            label: 'Penyaluran',
            onClick: () => {
                // Langsung buka modal penyaluran
                openDistributionModal(row);
            },
            permission: props.can?.Edit,
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
        const response = await axios.post('/program-bantuan/penerima/destroy-selected', {
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

const deleteRecipient = async (row: any) => {
    await router.delete(`/program-bantuan/penerima/${row.id}`, {
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

// Modal penyaluran
const selectedModalRecipient = ref<any>(null);
const isDistributionModalOpen = ref(false);

const openDistributionModal = (row: any) => {
    selectedModalRecipient.value = row;
    isDistributionModalOpen.value = true;
};

const handleDistributionModalClose = () => {
    isDistributionModalOpen.value = false;
    selectedModalRecipient.value = null;
};

const handleDistributionModalSuccess = () => {
    handleDistributionModalClose();
    pageIndex.value.fetchData();
    // Trigger refresh di Program Bantuan index untuk update badge
    window.dispatchEvent(new CustomEvent('refresh-assistance-programs-index'));
};
</script>

<template>
    <PageIndex
        title="Penerima Bantuan"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="createUrl"
        :create-multiple-url="createUrl"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/assistance-recipients"
        ref="pageIndex"
        :on-delete-row-confirm="deleteRecipient"
        :can="props.can"
        :hide-create-button="true"
        :show-filter="true"
        :filter-options="filterOptions"
    />

    <!-- Distribution Modal -->
    <DistributionModal
        :open="isDistributionModalOpen"
        :recipient="selectedModalRecipient"
        @update:open="(val: boolean) => (isDistributionModalOpen = val)"
        @success="handleDistributionModalSuccess"
        @close="handleDistributionModalClose"
    />
</template>

