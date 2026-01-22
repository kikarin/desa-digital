<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, computed, watch } from 'vue';

const props = defineProps<{
    filterOptions: {
        rw?: Array<{ value: number; label: string }>;
        rt?: Array<{ value: number; label: string; rw_id?: number }>;
    };
}>();

const { toast } = useToast();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Kartu Keluarga', href: '/data-warga/families' },
    { title: 'Bulk Assign Desil', href: '#' },
];

const backUrl = '/data-warga/families';

// Data state
const data = ref<any[]>([]);
const selected = ref<number[]>([]);
const loading = ref(false);
const search = ref('');
const page = ref(1);
const perPage = ref(10);
const total = ref(0);
const selectedDesil = ref<number | null>(null);

// Filter state
const filterRw = ref<number | null>(null);
const filterRt = ref<number | null>(null);

// Computed RT options berdasarkan RW yang dipilih
const rtOptions = computed(() => {
    if (!props.filterOptions.rt) return [];
    if (!filterRw.value) return props.filterOptions.rt;
    return props.filterOptions.rt.filter((rt) => rt.rw_id === filterRw.value);
});

// Desil options
const desilOptions = Array.from({ length: 10 }, (_, i) => ({
    value: i + 1,
    label: `Desil ${i + 1}`,
}));

// Columns
const columns = [
    { key: 'no_kk', label: 'No. KK', searchable: true, orderable: true, visible: true },
    { key: 'kepala_keluarga_nama', label: 'Kepala Keluarga', searchable: true, orderable: false, visible: true },
    { key: 'alamat', label: 'Alamat', searchable: true, orderable: false, visible: true },
    { key: 'jumlah_anggota', label: 'Jumlah Anggota', searchable: false, orderable: false, visible: true },
];

// Fetch data
const fetchData = async () => {
    loading.value = true;
    try {
        const params: any = {
            page: page.value - 1,
            per_page: perPage.value,
        };

        if (search.value) {
            params.search = search.value;
        }

        if (filterRw.value) {
            params.filter_rw_id = filterRw.value;
        }

        if (filterRt.value) {
            params.filter_rt_id = filterRt.value;
        }

        const response = await axios.get('/api/families/without-desil', { params });
        
        data.value = response.data.data || [];
        total.value = response.data.meta?.total || 0;
    } catch (error: any) {
        console.error('Gagal mengambil data:', error);
        toast({
            title: error.response?.data?.message || 'Gagal mengambil data',
            variant: 'destructive',
        });
    } finally {
        loading.value = false;
    }
};

// Watch untuk refetch saat filter/search berubah
watch([filterRw, filterRt], () => {
    page.value = 1;
    fetchData();
});

// Reset RT saat RW berubah
watch(filterRw, () => {
    filterRt.value = null;
});

// Toggle select
const toggleSelect = (id: number) => {
    if (selected.value.includes(id)) {
        selected.value = selected.value.filter((item) => item !== id);
    } else {
        selected.value = [...selected.value, id];
    }
};

// Toggle select all
const toggleSelectAll = (checked: boolean) => {
    if (checked) {
        selected.value = data.value.map((item) => item.id);
    } else {
        selected.value = [];
    }
};

// Handle submit
const handleSubmit = async () => {
    if (!selectedDesil.value) {
        return toast({
            title: 'Pilih desil terlebih dahulu',
            variant: 'destructive',
        });
    }

    if (selected.value.length === 0) {
        return toast({
            title: 'Pilih minimal satu keluarga',
            variant: 'destructive',
        });
    }

    try {
        await router.post('/data-warga/families/bulk-assign-desil', {
            desil: selectedDesil.value,
            family_ids: selected.value,
        }, {
            onSuccess: () => {
                toast({
                    title: `${selected.value.length} keluarga berhasil diassign desil ${selectedDesil.value}`,
                    variant: 'success',
                });
                router.visit(backUrl);
            },
            onError: (errors: any) => {
                const message = errors.message || 'Gagal assign desil';
                toast({
                    title: message,
                    variant: 'destructive',
                });
            },
        });
    } catch (error: any) {
        toast({
            title: error.response?.data?.message || 'Gagal assign desil',
            variant: 'destructive',
        });
    }
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <PageCreate title="Bulk Assign Desil" :breadcrumbs="breadcrumbs" :back-url="backUrl">
        <div class="space-y-4">
            <!-- Info Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Bulk Assign Desil</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        Pilih desil dan keluarga yang akan diassign. Hanya keluarga yang belum memiliki desil yang ditampilkan.
                    </p>
                </CardContent>
            </Card>

            <!-- Desil Selection -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Pilih Desil</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="w-full max-w-xs">
                        <label class="text-sm font-medium mb-2 block">Desil *</label>
                        <Select 
                            :model-value="selectedDesil ? String(selectedDesil) : null" 
                            @update:model-value="(val: string) => (selectedDesil = val ? Number(val) : null)"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Desil (1-10)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="desil in desilOptions"
                                    :key="desil.value"
                                    :value="String(desil.value)"
                                >
                                    {{ desil.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Filter</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- RW Filter -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">RW</label>
                            <Select v-model="filterRw" @update:model-value="fetchData">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih RW" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Semua RW</SelectItem>
                                    <SelectItem
                                        v-for="rw in filterOptions.rw"
                                        :key="rw.value"
                                        :value="rw.value"
                                    >
                                        {{ rw.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- RT Filter -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">RT</label>
                            <Select v-model="filterRt" @update:model-value="fetchData" :disabled="!filterRw">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih RT" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Semua RT</SelectItem>
                                    <SelectItem
                                        v-for="rt in rtOptions"
                                        :key="rt.value"
                                        :value="rt.value"
                                    >
                                        {{ rt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Search & Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-lg">Pilih Keluarga (Belum Ada Desil)</CardTitle>
                        <div class="flex items-center gap-2">
                            <Input
                                v-model="search"
                                placeholder="Cari..."
                                class="w-64"
                                @input="() => { page = 1; fetchData(); }"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <!-- Table -->
                        <div class="rounded-md border">
                            <div class="w-full overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead class="w-12 text-center">No</TableHead>
                                            <TableHead class="w-10 text-center">
                                                <label
                                                    class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        class="peer sr-only"
                                                        :checked="selected.length > 0 && selected.length === data.length && data.length > 0"
                                                        @change="(e: Event) => toggleSelectAll((e.target as HTMLInputElement).checked)"
                                                    />
                                                    <div class="bg-primary h-3 w-3 scale-0 transform rounded-sm transition-all peer-checked:scale-100"></div>
                                                </label>
                                            </TableHead>
                                            <TableHead
                                                v-for="col in columns"
                                                :key="col.key"
                                                class="text-xs sm:text-sm px-2 sm:px-4"
                                            >
                                                {{ col.label }}
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-if="loading">
                                            <TableCell :colspan="columns.length + 2" class="text-center py-8">
                                                Memuat data...
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-else-if="data.length === 0">
                                            <TableCell :colspan="columns.length + 2" class="text-center py-8 text-muted-foreground">
                                                Tidak ada data (semua keluarga sudah memiliki desil)
                                            </TableCell>
                                        </TableRow>
                                        <TableRow
                                            v-else
                                            v-for="(row, index) in data"
                                            :key="row.id"
                                            class="hover:bg-muted/40"
                                        >
                                            <TableCell class="text-center text-xs sm:text-sm px-2 sm:px-4">
                                                {{ (page - 1) * perPage + index + 1 }}
                                            </TableCell>
                                            <TableCell class="text-center text-xs sm:text-sm px-2 sm:px-4">
                                                <label
                                                    class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        class="peer sr-only"
                                                        :checked="selected.includes(row.id)"
                                                        @change="() => toggleSelect(row.id)"
                                                    />
                                                    <svg
                                                        class="text-primary h-4 w-4 scale-75 opacity-0 transition-all duration-200 peer-checked:scale-100 peer-checked:opacity-100"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="3"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </label>
                                            </TableCell>
                                            <TableCell
                                                v-for="col in columns"
                                                :key="col.key"
                                                class="text-xs sm:text-sm px-2 sm:px-4"
                                            >
                                                {{ row[col.key] || '-' }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-muted-foreground">
                                Menampilkan {{ data.length }} dari {{ total }} data
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="page === 1"
                                    @click="() => { page--; fetchData(); }"
                                >
                                    Previous
                                </Button>
                                <span class="text-sm">
                                    Halaman {{ page }} dari {{ Math.ceil(total / perPage) || 1 }}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="page >= Math.ceil(total / perPage)"
                                    @click="() => { page++; fetchData(); }"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>

                        <!-- Selected Count & Submit -->
                        <div class="flex items-center justify-between pt-4 border-t">
                            <div class="text-sm">
                                <span class="font-medium">{{ selected.length }}</span> keluarga dipilih
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" @click="router.visit(backUrl)">
                                    Batal
                                </Button>
                                <Button 
                                    @click="handleSubmit" 
                                    :disabled="selected.length === 0 || !selectedDesil"
                                >
                                    Simpan ({{ selected.length }})
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </PageCreate>
</template>
