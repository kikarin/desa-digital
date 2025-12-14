<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash.debounce';
import { onMounted, ref, watch } from 'vue';
import DataTable from '../components/DataTable.vue';
import HeaderActions from './HeaderActions.vue';
import FilterDialog from './FilterDialog.vue';

const { toast } = useToast();

const tableRows = ref<any[]>([]);
const total = ref(0);
const loading = ref(false);

const page = ref(1);
const localLimit = ref(10);
const search = ref('');
const sort = ref<{ key: string; order: 'asc' | 'desc' }>({ key: '', order: 'asc' });
const filters = ref<Record<string, any>>({});

const fetchData = async () => {
    loading.value = true;
    try {
        const params: Record<string, any> = {
            search: search.value,
            page: localLimit.value === -1 ? undefined : page.value > 1 ? page.value - 1 : 0,
            per_page: props.limit !== undefined ? props.limit : localLimit.value,
            sort: sort.value.key,
            order: sort.value.order,
        };

        // Check URL untuk program_id (untuk backward compatibility dan filter dari URL)
        const urlParams = new URLSearchParams(window.location.search);
        const programIdFromUrl = urlParams.get('program_id');
        if (programIdFromUrl && !filters.value.program_id) {
            filters.value.program_id = parseInt(programIdFromUrl);
        }

        Object.keys(filters.value).forEach((key) => {
            if (filters.value[key] !== null && filters.value[key] !== undefined && filters.value[key] !== '') {
                params[`filter_${key}`] = filters.value[key];
            }
        });

        const response = await axios.get(props.apiEndpoint, { params });

        tableRows.value = response.data.data;
        const meta = response.data.meta || {};
        total.value = Number(meta.total) || 0;
        page.value = Number(meta.current_page) || 1;
        localLimit.value = Number(meta.per_page) || 10;
        search.value = meta.search || '';
        sort.value.key = meta.sort || '';
        sort.value.order = meta.order || 'asc';
    } catch (error) {
        console.error('Gagal fetch data:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

watch([page, localLimit, () => sort.value.key, () => sort.value.order], (vals, oldVals) => {
    if (vals[2] !== oldVals[2] || vals[3] !== oldVals[3]) {
        fetchData();
    } else {
        fetchData();
    }
});

const props = defineProps<{
    title: string;
    breadcrumbs: BreadcrumbItem[];
    columns: { key: string; label: string }[];
    actions?: (row: any) => { label: string; onClick: () => void; permission?: boolean }[];
    createUrl: string;
    selected?: number[];
    onDeleteSelected?: () => void;
    apiEndpoint: string;
    onDeleteRowConfirm?: (row: any) => Promise<void>;
    hidePagination?: boolean;
    limit?: number;
    disableLength?: boolean;
    hideSearch?: boolean;
    showFilter?: boolean;
    filterOptions?: {
        rw?: Array<{ value: number; label: string }>;
        rt?: Array<{ value: number; label: string; rw_id?: number }>;
        jenis_rumah?: Array<{ value: string; label: string }>;
        nomor_rumah?: boolean;
        status?: Array<{ value: number; label: string }>;
        program?: Array<{ value: number; label: string }>;
    };
    can?: {
        Add?: boolean;
        Edit?: boolean;
        Delete?: boolean;
        Detail?: boolean;
        LoginAs?: boolean;
    };
    hideCreateButton?: boolean;
    createMultipleUrl?: string;
}>();

const emit = defineEmits(['search', 'update:selected']);

const localSelected = ref<number[]>([]);

watch(
    () => props.selected,
    (val) => {
        if (val) localSelected.value = val;
    },
);

watch(localSelected, (val) => {
    emit('update:selected', val);
});

const showConfirm = ref(false);
const showDeleteDialog = ref(false);
const rowToDelete = ref<any>(null);

const handleSearch = (params: { search?: string; sortKey?: string; sortOrder?: 'asc' | 'desc'; page?: number; limit?: number }) => {
    if (params.search !== undefined) search.value = params.search;
    if (params.sortKey) sort.value.key = params.sortKey;
    if (params.sortOrder) sort.value.order = params.sortOrder;
    if (params.page) page.value = params.page;
    if (params.limit) localLimit.value = params.limit;
};

const handleDeleteSelected = () => {
    if (!localSelected.value.length) return;
    if (props.onDeleteSelected) {
        props.onDeleteSelected();
    }
    showConfirm.value = false;
};

const handleDeleteRow = (row: any) => {
    rowToDelete.value = row;
    showDeleteDialog.value = true;
};

const confirmDeleteRow = async () => {
    if (!rowToDelete.value) return;

    if (props.onDeleteRowConfirm) {
        await props.onDeleteRowConfirm(rowToDelete.value);
        showDeleteDialog.value = false;
        rowToDelete.value = null;
        fetchData();
        return;
    }

    try {
        const module = props.apiEndpoint.split('/').pop();
        await router.delete(`/${module}/${rowToDelete.value.id}`, {
            onSuccess: () => {
                toast({ title: 'Data berhasil dihapus', variant: 'success' });
                fetchData();
            },
            onError: () => {
                toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
            },
        });
    } finally {
        showDeleteDialog.value = false;
        rowToDelete.value = null;
    }
};

const localActions = (row: any) => {
    const base = props.actions ? props.actions(row) : [];
    return base.map((action) => {
        if (action.label === 'Delete') {
            return {
                ...action,
                onClick: () => handleDeleteRow(row),
            };
        }
        return action;
    });
};

const handleSearchDebounced = debounce((val: string) => {
    search.value = val;
    fetchData();
}, 400);

const handleSort = debounce((val: { key: string; order: 'asc' | 'desc' }) => {
    sort.value.key = val.key;
    sort.value.order = val.order;
    page.value = 1;
    fetchData();
}, 200);

const showFilterDialog = ref(false);

const handleFilterClick = () => {
    showFilterDialog.value = true;
};

const handleFilterApply = (filterValues: any) => {
    filters.value = {};
    if (filterValues.rw?.value) filters.value.rw_id = filterValues.rw.value;
    if (filterValues.rt?.value) filters.value.rt_id = filterValues.rt.value;
    if (filterValues.jenis_rumah?.value) filters.value.jenis_rumah = filterValues.jenis_rumah.value;
    if (filterValues.nomor_rumah?.value && filterValues.nomor_rumah.value.trim() !== '') {
        filters.value.nomor_rumah = filterValues.nomor_rumah.value;
    }
    if (filterValues.status?.value) filters.value.status_id = filterValues.status.value;
    if (filterValues.program?.value) filters.value.program_id = filterValues.program.value;
    page.value = 1;
    fetchData();
};

const handleFilterReset = (resetFilters: any) => {
    filters.value = {};
    page.value = 1;
    fetchData();
};

const getFilterDialogProps = () => {
    const filterProps: any = {};
    if (props.filterOptions?.rw) {
        filterProps.rw = {
            value: filters.value.rw_id || null,
            options: props.filterOptions.rw,
        };
    }
    if (props.filterOptions?.rt) {
        filterProps.rt = {
            value: filters.value.rt_id || null,
            options: props.filterOptions.rt,
        };
    }
    if (props.filterOptions?.jenis_rumah) {
        filterProps.jenis_rumah = {
            value: filters.value.jenis_rumah || null,
            options: props.filterOptions.jenis_rumah,
        };
    }
    if (props.filterOptions?.nomor_rumah === true) {
        filterProps.nomor_rumah = {
            value: filters.value.nomor_rumah || null,
        };
    }
    if (props.filterOptions?.status) {
        filterProps.status = {
            value: filters.value.status_id || null,
            options: props.filterOptions.status,
        };
    }
    if (props.filterOptions?.program) {
        filterProps.program = {
            value: filters.value.program_id || null,
            options: props.filterOptions.program,
        };
    }
    return filterProps;
};

defineExpose({ fetchData });
</script>

<template>
    <Head :title="title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <HeaderActions
                :title="title"
                :selected="localSelected"
                :on-delete-selected="() => (showConfirm = true)"
                :can-create="props.can?.Add && !props.hideCreateButton"
                :can-create-multiple="props.can?.Add"
                :can-delete="props.can?.Delete"
                :show-filter="props.showFilter"
                :on-filter-click="handleFilterClick"
                v-bind="createUrl && !props.hideCreateButton ? { createUrl } : {}"
                :create-multiple-url="props.createMultipleUrl"
            />
            <DataTable
                :columns="columns"
                :rows="tableRows"
                :actions="localActions"
                :total="total"
                :loading="loading"
                v-model:selected="localSelected"
                :search="search"
                :sort="sort"
                :page="page"
                :per-page="props.limit !== undefined ? props.limit : localLimit"
                @update:search="handleSearchDebounced"
                @update:sort="handleSort"
                @update:page="(val: number) => handleSearch({ page: val })"
                @update:perPage="(val: number) => handleSearch({ limit: Number(val), page: 1 })"
                @deleted="fetchData()"
                :on-delete-row="handleDeleteRow"
                :hide-pagination="props.hidePagination"
                :disable-length="props.disableLength"
                :hide-search="props.hideSearch"
            />

            <Dialog v-model:open="showConfirm">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus data terpilih?</DialogTitle>
                        <DialogDescription>
                            Anda akan menghapus {{ localSelected.length }} data. Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showConfirm = false">Batal</Button>
                        <Button variant="destructive" @click="handleDeleteSelected">Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="showDeleteDialog">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus data ini!</DialogTitle>
                        <DialogDescription> Apakah Anda yakin? </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showDeleteDialog = false">Batal</Button>
                        <Button variant="destructive" @click="confirmDeleteRow">Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <FilterDialog
                v-if="props.showFilter"
                v-model:open="showFilterDialog"
                :filters="getFilterDialogProps()"
                @apply="handleFilterApply"
                @reset="handleFilterReset"
            />
        </div>
    </AppLayout>
</template>
