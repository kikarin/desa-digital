<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

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
        </template>
    </PageShow>
</template>

