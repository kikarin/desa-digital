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
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    program: {
        id: number;
        nama_program: string;
        tahun: number;
        periode: string;
        target_penerima: 'KELUARGA' | 'INDIVIDU';
    };
    filterOptions: {
        rw?: Array<{ value: number; label: string }>;
        rt?: Array<{ value: number; label: string; rw_id?: number }>;
        jenis_kelamin?: Array<{ value: string; label: string }>;
        status?: Array<{ value: number; label: string }>;
    };
}>();

const { toast } = useToast();

const breadcrumbs = [
    { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
    { title: 'Penerima Bantuan', href: `/program-bantuan/penerima?program_id=${props.program.id}` },
    { title: 'Tambah Penerima', href: '#' },
];

const backUrl = computed(() => `/program-bantuan/penerima?program_id=${props.program.id}`);

// Data state
const data = ref<any[]>([]);
const selected = ref<number[]>([]);
const loading = ref(false);
const search = ref('');
const page = ref(1);
const perPage = ref(10);
const total = ref(0);

// Filter state
const filterRw = ref<number | null>(null);
const filterRt = ref<number | null>(null);
const filterJenisKelamin = ref<string | null>(null);

// Computed RT options berdasarkan RW yang dipilih
const rtOptions = computed(() => {
    if (!props.filterOptions.rt) return [];
    if (!filterRw.value) return props.filterOptions.rt;
    return props.filterOptions.rt.filter((rt) => rt.rw_id === filterRw.value);
});

// Columns berdasarkan target_type
const columns = computed(() => {
    if (props.program.target_penerima === 'KELUARGA') {
        return [
            { key: 'no_kk', label: 'No. KK', searchable: true, orderable: true, visible: true },
            { key: 'kepala_keluarga_nama', label: 'Kepala Keluarga', searchable: true, orderable: false, visible: true },
            { key: 'alamat', label: 'Alamat', searchable: true, orderable: false, visible: true },
            { key: 'jumlah_anggota', label: 'Jumlah Anggota', searchable: false, orderable: false, visible: true },
            { key: 'pernah_dapat_bantuan', label: 'Pernah Dapat Bantuan', searchable: false, orderable: false, visible: true },
        ];
    } else {
        return [
            { key: 'nik', label: 'NIK', searchable: true, orderable: true, visible: true },
            { key: 'nama', label: 'Nama', searchable: true, orderable: true, visible: true },
            { key: 'jenis_kelamin_label', label: 'Jenis Kelamin', searchable: false, orderable: false, visible: true },
            { key: 'usia', label: 'Usia', searchable: false, orderable: false, visible: true },
            { key: 'status_name', label: 'Status', searchable: false, orderable: false, visible: true },
            { key: 'alamat', label: 'Alamat', searchable: true, orderable: false, visible: true },
            { key: 'pernah_dapat_bantuan', label: 'Pernah Dapat Bantuan', searchable: false, orderable: false, visible: true },
        ];
    }
});

// Fetch data
const fetchData = async () => {
    loading.value = true;
    try {
        const endpoint = props.program.target_penerima === 'KELUARGA'
            ? '/api/assistance-recipients/available-families'
            : '/api/assistance-recipients/available-residents';

        const params: any = {
            program_id: props.program.id,
            page: page.value - 1, // Backend menggunakan 0-based
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

        if (props.program.target_penerima === 'INDIVIDU') {
            if (filterJenisKelamin.value) {
                params.filter_jenis_kelamin = filterJenisKelamin.value;
            }
        }

        const response = await axios.get(endpoint, { params });
        
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
watch([filterRw, filterRt, filterJenisKelamin], () => {
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
    if (selected.value.length === 0) {
        return toast({
            title: 'Pilih minimal satu penerima',
            variant: 'destructive',
        });
    }

    try {
        const recipients = selected.value.map((id) => {
            if (props.program.target_penerima === 'KELUARGA') {
                return { family_id: id };
            } else {
                return { resident_id: id };
            }
        });

        await router.post('/program-bantuan/penerima/store-multiple', {
            program_id: props.program.id,
            target_type: props.program.target_penerima,
            recipients,
        }, {
            onSuccess: () => {
                toast({
                    title: `${selected.value.length} penerima berhasil ditambahkan`,
                    variant: 'success',
                });
                // Redirect dengan preserve program_id dan trigger refresh
                router.visit(backUrl.value, {
                    onFinish: () => {
                        // Trigger refresh di Index page
                        window.dispatchEvent(new CustomEvent('refresh-assistance-recipients-index'));
                    },
                });
            },
            onError: (errors: any) => {
                const message = errors.message || 'Gagal menambahkan penerima';
                toast({
                    title: message,
                    variant: 'destructive',
                });
            },
        });
    } catch (error: any) {
        toast({
            title: error.response?.data?.message || 'Gagal menambahkan penerima',
            variant: 'destructive',
        });
    }
};

// Format untuk kolom "Pernah Dapat Bantuan"
const formatPernahDapatBantuan = (row: any) => {
    if (row.pernah_dapat_bantuan) {
        return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">✓</span>';
    }
    return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">-</span>';
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <PageCreate title="Tambah Penerima Bantuan" :breadcrumbs="breadcrumbs" :back-url="backUrl">
        <div class="space-y-4">
            <!-- Program Info -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Program: {{ program.nama_program }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Tahun:</span> {{ program.tahun }}
                        </div>
                        <div>
                            <span class="font-medium">Periode:</span> {{ program.periode }}
                        </div>
                        <div>
                            <span class="font-medium">Target Penerima:</span>
                            <Badge :variant="program.target_penerima === 'KELUARGA' ? 'default' : 'secondary'" class="ml-2">
                                {{ program.target_penerima === 'KELUARGA' ? 'Keluarga' : 'Individu' }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Filter</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
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

                        <!-- Jenis Kelamin Filter (hanya untuk INDIVIDU) -->
                        <div v-if="program.target_penerima === 'INDIVIDU'">
                            <label class="text-sm font-medium mb-2 block">Jenis Kelamin</label>
                            <Select v-model="filterJenisKelamin" @update:model-value="fetchData">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Jenis Kelamin" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Semua</SelectItem>
                                    <SelectItem
                                        v-for="jk in filterOptions.jenis_kelamin"
                                        :key="jk.value"
                                        :value="jk.value"
                                    >
                                        {{ jk.label }}
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
                        <CardTitle class="text-lg">
                            Pilih {{ program.target_penerima === 'KELUARGA' ? 'Keluarga' : 'Warga' }}
                        </CardTitle>
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
                                                Tidak ada data
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
                                                <span v-if="col.key === 'pernah_dapat_bantuan'" v-html="formatPernahDapatBantuan(row)"></span>
                                                <span v-else>{{ row[col.key] || '-' }}</span>
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
                                <span class="font-medium">{{ selected.length }}</span> item dipilih
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" @click="router.visit(backUrl)">
                                    Batal
                                </Button>
                                <Button @click="handleSubmit" :disabled="selected.length === 0">
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

