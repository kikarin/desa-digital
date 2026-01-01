<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { computed, onMounted, onUnmounted, nextTick } from 'vue';
import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.css';
import LocationMapView from '@/components/LocationMapView.vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        rt_id: number;
        nomor_rumah: string;
        jenis_rumah: string;
        keterangan: string | null;
        pemilik_id: number | null;
        pemilik?: {
            id: number;
            nik: string;
            nama: string;
        } | null;
        nama_pemilik: string | null;
        status_hunian: string | null;
        nama_usaha: string | null;
        nama_pengelola: string | null;
        jenis_usaha: string | null;
        nama_fasilitas: string | null;
        pengelola: string | null;
        latitude?: string | null;
        longitude?: string | null;
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
        fotos?: Array<{ id: number; url: string; name: string }>;
    };
    residents?: Array<{
        id: number;
        nik: string;
        nama: string;
        status: string;
        family_id: number;
        no_kk: string;
        is_kepala_keluarga: boolean;
        jenis_kelamin: string;
        tanggal_lahir: string;
    }>;
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Rumah', href: '/data-warga/houses' },
    { title: 'Detail Rumah', href: `/data-warga/houses/${props.item.id}` },
];

const getJenisRumahLabel = (jenis: string): string => {
    const labels: Record<string, string> = {
        'RUMAH_TINGGAL': 'Rumah Tinggal',
        'KONTRAKAN': 'Kontrakan',
        'WARUNG_TOKO_USAHA': 'Warung / Toko / Usaha',
        'FASILITAS_UMUM': 'Fasilitas Umum',
    };
    return labels[jenis] || jenis;
};

const fields = computed(() => {
    const rtValue = props.item.rt?.rw 
        ? `${props.item.rt.nomor_rt} - RW ${props.item.rt.rw.nomor_rw} - ${props.item.rt.rw.desa}, ${props.item.rt.rw.kecamatan}, ${props.item.rt.rw.kabupaten}`
        : props.item.rt?.nomor_rt || '-';
    
    const baseFields = [
        { label: 'Jenis Rumah', value: getJenisRumahLabel(props.item.jenis_rumah) },
        { label: 'RT', value: rtValue },
    ];

    if (props.item.jenis_rumah === 'RUMAH_TINGGAL') {
        baseFields.push(
            { label: 'Nomor Rumah', value: props.item.nomor_rumah },
            { 
                label: 'Pemilik', 
                value: props.item.pemilik 
                    ? `${props.item.pemilik.nik} - ${props.item.pemilik.nama}` 
                    : (props.item.nama_pemilik || '-')
            },
            { label: 'Keterangan', value: props.item.keterangan || '-' }
        );
    }

    if (props.item.jenis_rumah === 'KONTRAKAN') {
        baseFields.push(
            { label: 'Nomor Bangunan', value: props.item.nomor_rumah },
            { label: 'Nama Pemilik', value: props.item.nama_pemilik || '-' },
            { label: 'Status Hunian', value: props.item.status_hunian === 'DIHUNI' ? 'Dihuni' : props.item.status_hunian === 'KOSONG' ? 'Kosong' : '-' },
            { label: 'Keterangan', value: props.item.keterangan || '-' }
        );
    }

    if (props.item.jenis_rumah === 'WARUNG_TOKO_USAHA') {
        baseFields.push(
            { label: 'Nomor Bangunan', value: props.item.nomor_rumah },
            { label: 'Nama Usaha', value: props.item.nama_usaha || '-' },
            { label: 'Nama Pemilik / Pengelola', value: props.item.nama_pengelola || '-' },
            { label: 'Jenis Usaha', value: props.item.jenis_usaha || '-' },
            { label: 'Keterangan', value: props.item.keterangan || '-' }
        );
    }

    if (props.item.jenis_rumah === 'FASILITAS_UMUM') {
        baseFields.push(
            { label: 'Nomor Bangunan', value: props.item.nomor_rumah || '-' },
            { label: 'Nama Fasilitas', value: props.item.nama_fasilitas || '-' },
            { label: 'Pengelola', value: props.item.pengelola || '-' },
            { label: 'Keterangan', value: props.item.keterangan || '-' }
        );
    }

    return baseFields;
});

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/data-warga/houses/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/data-warga/houses/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/data-warga/houses');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};

const calculateAge = (tanggalLahir: string): number => {
    if (!tanggalLahir) return 0;
    const birthDate = new Date(tanggalLahir);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

const getJenisKelaminLabel = (jenisKelamin: string): string => {
    return jenisKelamin === 'L' ? 'Laki-laki' : jenisKelamin === 'P' ? 'Perempuan' : '-';
};

// GLightbox setup
let lightboxInstance: any = null;

onMounted(() => {
    // Initialize GLightbox setelah component mounted dan foto ter-render
    if (props.item.fotos && props.item.fotos.length > 0) {
        nextTick(() => {
            lightboxInstance = GLightbox({
                selector: '.glightbox-trigger',
                touchNavigation: true,
                loop: true,
                autoplayVideos: false,
            });
        });
    }
});

onUnmounted(() => {
    // Cleanup GLightbox
    if (lightboxInstance) {
        lightboxInstance.destroy();
        lightboxInstance = null;
    }
});

</script>

<template>
    <PageShow
        title="Rumah"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/data-warga/houses'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
        <template #custom>
            <!-- Peta Lokasi -->
            <div v-if="item.latitude && item.longitude" class="mt-6">
                <LocationMapView
                    :latitude="item.latitude"
                    :longitude="item.longitude"
                    :jenis-rumah="item.jenis_rumah"
                    :marker-popup-text="getJenisRumahLabel(item.jenis_rumah)"
                />
            </div>

            <!-- Gallery Foto -->
            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Foto</h3>
                <div v-if="props.item.fotos && Array.isArray(props.item.fotos) && props.item.fotos.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a
                        v-for="(foto, index) in props.item.fotos"
                        :key="foto.id || index"
                        :href="foto.url"
                        class="glightbox-trigger relative group cursor-pointer"
                        :data-gallery="`gallery-${props.item.id}`"
                    >
                        <img
                            :src="foto.url"
                            :alt="foto.name || 'Foto'"
                            class="w-full h-48 object-cover rounded-lg border hover:opacity-90 transition-opacity"
                            loading="lazy"
                            @error="(e: Event) => {
                                const img = e.target as HTMLImageElement;
                                img.style.display = 'none';
                            }"
                        />
                    </a>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                    <p>Tidak ada foto</p>
                </div>
            </div>

            <!-- Daftar Warga -->
            <div v-if="props.residents && props.residents.length > 0" class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Daftar Warga</h3>
                <div class="border rounded-lg overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>NIK</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Jenis Kelamin</TableHead>
                                <TableHead>Usia</TableHead>
                                <TableHead>No. KK</TableHead>
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
                                <TableCell>{{ getJenisKelaminLabel(resident.jenis_kelamin) }}</TableCell>
                                <TableCell>{{ calculateAge(resident.tanggal_lahir) }} tahun</TableCell>
                                <TableCell>{{ resident.no_kk }}</TableCell>
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

