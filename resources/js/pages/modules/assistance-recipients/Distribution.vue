<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash.debounce';
import { onMounted, ref, watch, computed } from 'vue';
import FilterDialog from '../base-page/FilterDialog.vue';
import DistributionModal from './DistributionModal.vue';
import HeaderActions from '../base-page/HeaderActions.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{
    program: {
        id: number;
        nama_program: string;
        tahun: string;
        periode: string;
        target_penerima: string;
        items: Array<{
            id: number;
            nama_item: string;
            jumlah: number;
            satuan: string;
            tipe: string;
        }>;
    };
    filterOptions: {
        rw?: Array<{ value: number; label: string }>;
        rt?: Array<{ value: number; label: string; rw_id?: number }>;
        status?: Array<{ value: string; label: string }>;
    };
}>();

const { toast } = useToast();

const tableRows = ref<any[]>([]);
const total = ref(0);
const loading = ref(false);

const page = ref(1);
const localLimit = ref(10);
const search = ref('');
const sort = ref<{ key: string; order: 'asc' | 'desc' }>({ key: '', order: 'asc' });
const filters = ref<Record<string, any>>({
    program_id: props.program.id,
});

const selectedModal = ref<any>(null);
const isModalOpen = ref(false);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
    { title: 'Penerima Bantuan', href: `/program-bantuan/penerima?program_id=${props.program.id}` },
    { title: 'Penyaluran', href: '#' },
]);

const fetchData = async () => {
    loading.value = true;
    try {
        const params: Record<string, any> = {
            search: search.value,
            page: localLimit.value === -1 ? undefined : page.value > 1 ? page.value - 1 : 0,
            per_page: localLimit.value,
            sort: sort.value.key,
            order: sort.value.order,
        };

        Object.keys(filters.value).forEach((key) => {
            if (filters.value[key] !== null && filters.value[key] !== undefined && filters.value[key] !== '') {
                params[`filter_${key}`] = filters.value[key];
            }
        });

        const response = await axios.get('/api/assistance-recipients/distribution', { params });
        tableRows.value = response.data.data || [];
        total.value = response.data.meta?.total || 0;
    } catch (error: any) {
        console.error('Error fetching data:', error);
        toast({
            title: 'Gagal mengambil data',
            variant: 'destructive',
        });
    } finally {
        loading.value = false;
    }
};

const debouncedFetch = debounce(fetchData, 300);

watch([search, sort, filters], () => {
    page.value = 1;
    debouncedFetch();
}, { deep: true });

watch(localLimit, () => {
    page.value = 1;
    fetchData();
});

watch(page, () => {
    fetchData();
});

onMounted(() => {
    fetchData();
});

const totalPages = computed(() => {
    if (localLimit.value === -1) return 1;
    return Math.ceil(total.value / localLimit.value);
});

const getPageNumbers = () => {
    const pages: number[] = [];
    const maxPages = 5;
    let startPage = Math.max(1, page.value - Math.floor(maxPages / 2));
    let endPage = Math.min(totalPages.value, startPage + maxPages - 1);
    
    if (endPage - startPage < maxPages - 1) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }
    
    return pages;
};

const sortBy = (key: string) => {
    if (sort.value.key === key) {
        sort.value.order = sort.value.order === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value.key = key;
        sort.value.order = 'asc';
    }
    page.value = 1;
    fetchData();
};

const columns = computed(() => [
    {
        key: 'target_type',
        label: 'Tipe',
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
        format: (row: any) => {
            if (row.status === 'DATANG') {
                return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">DATANG</span>';
            } else if (row.status === 'TIDAK_DATANG') {
                return '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-200">TIDAK DATANG</span>';
            } else {
                return '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-900 dark:text-yellow-200">PROSES</span>';
            }
        },
    },
    {
        key: 'perwakilan',
        label: 'Perwakilan',
        format: (row: any) => {
            if (row.penerima_lapangan && row.penerima_lapangan.nama) {
                return row.penerima_lapangan.nama;
            }
            return '-';
        },
    },
    {
        key: 'tanggal_penyaluran',
        label: 'Tanggal Penyaluran',
        format: (row: any) => {
            if (row.tanggal_penyaluran) {
                return new Date(row.tanggal_penyaluran).toLocaleDateString('id-ID');
            }
            return '-';
        },
    },
]);

const handleRowClick = (row: any) => {
    // Hanya bisa klik jika status PROSES
    if (row.status === 'PROSES') {
        selectedModal.value = row;
        isModalOpen.value = true;
    }
};

const handleModalClose = () => {
    isModalOpen.value = false;
    selectedModal.value = null;
};

const handleModalSuccess = () => {
    handleModalClose();
    fetchData();
    // Trigger refresh di Program Bantuan index untuk update badge
    window.dispatchEvent(new CustomEvent('refresh-assistance-programs-index'));
};

const showFilterDialog = ref(false);

const handleFilterClick = () => {
    showFilterDialog.value = true;
};

const handleFilterApply = (filterValues: any) => {
    filters.value = { program_id: props.program.id };
    if (filterValues.rw?.value) filters.value.rw_id = filterValues.rw.value;
    if (filterValues.rt?.value) filters.value.rt_id = filterValues.rt.value;
    if (filterValues.status?.value) filters.value.status = filterValues.status.value;
    page.value = 1;
    fetchData();
};

const handleFilterReset = () => {
    filters.value = { program_id: props.program.id };
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
    if (props.filterOptions?.status) {
        filterProps.status = {
            value: filters.value.status || null,
            options: props.filterOptions.status,
        };
    }
    return filterProps;
};

</script>

<template>
    <Head title="Penyaluran Bantuan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <!-- Info Program & Items -->
            <Card>
                <CardHeader>
                    <CardTitle>Informasi Program & Item Bantuan</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Program</p>
                        <p class="text-lg font-semibold">{{ program.nama_program }} ({{ program.tahun }} - {{ program.periode }})</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground mb-2">Item Bantuan</p>
                        <div class="space-y-2">
                            <div
                                v-for="item in program.items"
                                :key="item.id"
                                class="flex items-center justify-between p-3 bg-muted/50 rounded-lg"
                            >
                                <span class="font-medium">{{ item.nama_item }}</span>
                                <span class="text-sm">
                                    <template v-if="item.tipe === 'UANG' && item.satuan === 'Rupiah'">
                                        {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.jumlah) }}
                                    </template>
                                    <template v-else>
                                        {{ new Intl.NumberFormat('id-ID').format(item.jumlah) }} {{ item.satuan }}
                                    </template>
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Header Actions -->
            <HeaderActions
                title="Penyaluran Bantuan"
                :selected="[]"
                :can-create="false"
                :can-delete="false"
                :show-filter="true"
                :on-filter-click="handleFilterClick"
            />
            <div class="flex justify-end">
                <Button variant="outline" size="sm" @click="router.visit(`/program-bantuan/penerima?program_id=${program.id}`)">
                    Kembali
                </Button>
            </div>

            <!-- Custom Table -->
            <div class="space-y-4">
                <!-- Search dan Length -->
                <div class="flex flex-col flex-wrap items-center justify-center gap-4 text-center sm:flex-row sm:justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm">Show</span>
                        <Select :model-value="localLimit" @update:model-value="(val: string | number) => (localLimit = val === 'all' ? -1 : Number(val))">
                            <SelectTrigger class="w-24">
                                <SelectValue :placeholder="localLimit === -1 ? 'All' : localLimit.toString()" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="10">10</SelectItem>
                                <SelectItem :value="25">25</SelectItem>
                                <SelectItem :value="50">50</SelectItem>
                                <SelectItem :value="100">100</SelectItem>
                                <SelectItem :value="500">500</SelectItem>
                                <SelectItem value="all">All</SelectItem>
                            </SelectContent>
                        </Select>
                        <span class="text-muted-foreground text-sm">entries</span>
                    </div>
                    <div class="w-full bg-card sm:w-64">
                        <Input :model-value="search" @update:model-value="(val: string) => (search = val)" placeholder="Search..." class="w-full" />
                    </div>
                </div>

                <!-- Table -->
                <div class="rounded-md border shadow-sm bg-card">
                    <div class="w-full overflow-x-auto">
                        <Table class="min-w-max">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12 text-center">No</TableHead>
                                    <TableHead
                                        v-for="col in columns"
                                        :key="col.key"
                                        class="cursor-pointer select-none"
                                        @click="sortBy(col.key)"
                                    >
                                        <div class="flex items-center gap-1">
                                            {{ col.label }}
                                            <span v-if="sort.key === col.key">
                                                <span v-if="sort.order === 'asc'">▲</span>
                                                <span v-else>▼</span>
                                            </span>
                                        </div>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(row, index) in tableRows"
                                    :key="index"
                                    class="hover:bg-muted/40 border-t transition"
                                    :class="{
                                        'cursor-pointer': row.status === 'PROSES',
                                        'opacity-50 cursor-not-allowed': row.status !== 'PROSES',
                                    }"
                                    @click="handleRowClick(row)"
                                >
                                    <TableCell class="text-center text-xs sm:text-sm px-2 sm:px-4 whitespace-normal break-words">
                                        {{ (page - 1) * localLimit + index + 1 }}
                                    </TableCell>
                                    <TableCell
                                        v-for="col in columns"
                                        :key="col.key"
                                        class="text-xs sm:text-sm px-2 sm:px-4 whitespace-normal break-words"
                                    >
                                        <span v-if="typeof col.format === 'function'" v-html="col.format(row)"></span>
                                        <span v-else>{{ row[col.key] }}</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="localLimit !== -1"
                        class="text-muted-foreground flex flex-col items-center justify-center gap-2 border-t p-4 text-center text-sm md:flex-row md:justify-between"
                    >
                        <span>
                            Showing {{ (page - 1) * localLimit + 1 }} to {{ Math.min(page * localLimit, total) }} of
                            {{ total }} entries
                        </span>
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <Button size="sm" :disabled="page === 1" @click="page = page - 1" class="bg-muted/40 text-foreground">
                                Previous
                            </Button>
                            <div class="flex flex-wrap items-center gap-1">
                                <Button
                                    v-for="p in getPageNumbers()"
                                    :key="p"
                                    size="sm"
                                    class="rounded-md border px-3 py-1.5 text-sm"
                                    :class="[
                                        page === p
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'bg-muted border-input text-black dark:text-white',
                                    ]"
                                    @click="page = p"
                                >
                                    {{ p }}
                                </Button>
                            </div>
                            <Button
                                size="sm"
                                :disabled="page === totalPages"
                                @click="page = page + 1"
                                class="bg-muted/40 text-foreground"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Dialog -->
            <FilterDialog
                v-model:open="showFilterDialog"
                :filters="getFilterDialogProps()"
                @apply="handleFilterApply"
                @reset="handleFilterReset"
            />

            <!-- Distribution Modal -->
            <DistributionModal
                v-model:open="isModalOpen"
                :recipient="selectedModal"
                @success="handleModalSuccess"
                @close="handleModalClose"
            />
        </div>
    </AppLayout>
</template>

