<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Package, Calendar, User, FileText } from 'lucide-vue-next';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        house_id: number;
        no_kk: string;
        kepala_keluarga_id: number | null;
        status: string;
        house: {
            id: number;
            nomor_rumah: string;
            rt: {
                id: number;
                nomor_rt: string;
                rw: {
                    id: number;
                    nomor_rw: string;
                    desa: string;
                    kecamatan: string;
                    kabupaten: string;
                };
            };
        };
        created_at: string;
        created_by_user: {
            id: number;
            name: string;
        } | null;
        updated_at: string;
        updated_by_user: {
            id: number;
            name: string;
        } | null;
    };
    fields: Array<{ label: string; value: string }>;
    actionFields: Array<{ label: string; value: string }>;
    residents?: Array<{
        id: number;
        nik: string;
        nama: string;
        status: string;
        is_kepala_keluarga: boolean;
        tempat_lahir: string;
        tanggal_lahir: string;
        jenis_kelamin: string;
    }>;
    assistancePrograms?: Array<{
        id: number;
        program_id: number;
        nama_program: string;
        tahun: string;
        periode: string;
        status: string;
        tanggal_penyaluran: string | null;
        perwakilan: string;
        catatan: string;
        items?: Array<{
            nama_item: string;
            tipe: string;
            satuan: string;
            jumlah: number;
        }>;
    }>;
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Kartu Keluarga', href: '/data-warga/families' },
    { title: 'Detail Kartu Keluarga', href: `/data-warga/families/${props.item.id}` },
];

const handleEdit = () => {
    router.visit(`/data-warga/families/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/data-warga/families/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/data-warga/families');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};

const formatDate = (dateString: string): string => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const getJenisKelaminLabel = (jenisKelamin: string): string => {
    return jenisKelamin === 'L' ? 'Laki-laki' : jenisKelamin === 'P' ? 'Perempuan' : '-';
};

const getStatusBadgeClass = (status: string): string => {
    if (status === 'SELESAI' || status === 'DATANG') {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    } else if (status === 'TIDAK_DATANG') {
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    } else {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    }
};

const getStatusLabel = (status: string): string => {
    if (status === 'TIDAK_DATANG') {
        return 'TIDAK DATANG';
    }
    return status;
};

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
};

const isCashItem = (tipe: string): boolean => {
    return tipe === 'UANG' || tipe === 'UANG_TUNAI' || tipe?.toUpperCase().includes('UANG');
};
</script>

<template>
    <PageShow
        title="Kartu Keluarga"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/data-warga/families'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
        <template #custom>
            <div v-if="props.residents && props.residents.length > 0" class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Daftar Anggota Keluarga</h3>
                <div class="border rounded-lg overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>NIK</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Tempat Lahir</TableHead>
                                <TableHead>Tanggal Lahir</TableHead>
                                <TableHead>Jenis Kelamin</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Keterangan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="resident in props.residents" :key="resident.id">
                                <TableCell>{{ resident.nik }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <span>{{ resident.nama }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>{{ resident.tempat_lahir || '-' }}</TableCell>
                                <TableCell>{{ formatDate(resident.tanggal_lahir) }}</TableCell>
                                <TableCell>{{ getJenisKelaminLabel(resident.jenis_kelamin) }}</TableCell>
                                <TableCell>
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': resident.status === 'Aktif',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': resident.status === 'Pindah',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': resident.status === 'Meninggal',
                                        }"
                                    >
                                        {{ resident.status }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <span v-if="resident.is_kepala_keluarga" class="text-sm text-muted-foreground">Kepala Keluarga</span>
                                    <span v-else class="text-sm text-muted-foreground">-</span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <div v-if="props.assistancePrograms && props.assistancePrograms.length > 0" class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Program Bantuan yang Diterima</h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="program in props.assistancePrograms"
                        :key="program.id"
                        class="hover:shadow-lg transition-shadow cursor-pointer"
                        @click="router.visit(`/program-bantuan/program-bantuan/${program.program_id}`)"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <CardTitle class="text-base mb-1 line-clamp-2">
                                        {{ program.nama_program }}
                                    </CardTitle>
                                    <CardDescription class="text-xs">
                                        {{ program.tahun }} • {{ program.periode }}
                                    </CardDescription>
                                </div>
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap ml-2"
                                    :class="getStatusBadgeClass(program.status)"
                                >
                                    {{ getStatusLabel(program.status) }}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <!-- Item Bantuan -->
                            <div v-if="program.items && program.items.length > 0" class="space-y-3">
                                <div class="flex items-center gap-2 text-sm font-medium text-foreground">
                                    <Package class="h-4 w-4 text-muted-foreground" />
                                    <span>Item Bantuan yang Diterima</span>
                                </div>
                                <div class="space-y-2">
                                    <div
                                        v-for="(item, index) in program.items"
                                        :key="index"
                                        class="flex items-center justify-between p-2 bg-muted/50 rounded-lg"
                                    >
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-foreground truncate">
                                                {{ item.nama_item }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ item.tipe }}
                                            </p>
                                        </div>
                                        <div class="text-right ml-2">
                                            <p class="text-sm font-semibold text-foreground">
                                                <span v-if="isCashItem(item.tipe)">
                                                    {{ formatCurrency(item.jumlah) }}
                                                </span>
                                                <span v-else>
                                                    {{ item.jumlah }}
                                                </span>
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                <span v-if="isCashItem(item.tipe)">Rupiah</span>
                                                <span v-else>{{ item.satuan }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-sm text-muted-foreground italic">
                                Tidak ada item bantuan
                            </div>

                            <!-- Informasi Tambahan -->
                            <div class="mt-4 pt-4 border-t space-y-2">
                                <div v-if="program.tanggal_penyaluran" class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Calendar class="h-3.5 w-3.5" />
                                    <span>Tanggal: {{ formatDate(program.tanggal_penyaluran) }}</span>
                                </div>
                                <div v-if="program.perwakilan && program.perwakilan !== '-'" class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <User class="h-3.5 w-3.5" />
                                    <span>Perwakilan: {{ program.perwakilan }}</span>
                                </div>
                                <div v-if="program.catatan && program.catatan !== '-'" class="flex items-start gap-2 text-xs text-muted-foreground">
                                    <FileText class="h-3.5 w-3.5 mt-0.5" />
                                    <span class="line-clamp-2">{{ program.catatan }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
            <div v-else-if="props.assistancePrograms && props.assistancePrograms.length === 0" class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Program Bantuan yang Diterima</h3>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-center py-8 text-muted-foreground">
                            <Package class="h-12 w-12 mx-auto mb-3 opacity-50" />
                            <p class="text-sm">Belum ada program bantuan yang diterima oleh keluarga ini.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>
    </PageShow>
</template>

