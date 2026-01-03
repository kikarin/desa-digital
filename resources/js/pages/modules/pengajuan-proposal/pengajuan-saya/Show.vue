<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import LocationMapView from '@/components/LocationMapView.vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        kategori_proposal_id: number;
        kategori_proposal_nama: string;
        resident_id: number;
        resident_nama: string;
        resident_nik: string;
        nomor_telepon_pengaju: string | null;
        nama_kegiatan: string;
        deskripsi_kegiatan: string;
        usulan_anggaran: number;
        usulan_anggaran_formatted: string;
        file_pendukung: string[];
        latitude: string;
        longitude: string;
        nama_lokasi: string | null;
        alamat: string | null;
        thumbnail_foto_banner: string | null;
        tanda_tangan_digital: string | null;
        status: string;
        status_label: string;
        catatan_verifikasi: string | null;
        admin_verifikasi_nama: string;
        tanggal_diverifikasi: string | null;
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
}>();

const breadcrumbs = [
    { title: 'Pengajuan Proposal Saya', href: '/pengajuan-proposal-saya' },
    { title: 'Detail Pengajuan Proposal', href: `/pengajuan-proposal-saya/${props.item.id}` },
];


const fields = [
    { label: 'Kategori Proposal', value: props.item.kategori_proposal_nama || '-' },
    { label: 'Nama Kegiatan', value: props.item.nama_kegiatan || '-' },
    { label: 'Deskripsi Kegiatan', value: props.item.deskripsi_kegiatan || '-', className: 'sm:col-span-2' },
    { label: 'Usulan Anggaran', value: props.item.usulan_anggaran_formatted || '-' },
    { label: 'Status', value: props.item.status_label || '-' },
];

if (props.item.catatan_verifikasi) {
    fields.push({ label: 'Catatan Verifikasi', value: props.item.catatan_verifikasi, className: 'sm:col-span-2' });
}

if (props.item.admin_verifikasi_nama && props.item.admin_verifikasi_nama !== '-') {
    fields.push({ label: 'Diverifikasi Oleh', value: props.item.admin_verifikasi_nama });
}

if (props.item.tanggal_diverifikasi) {
    fields.push({ label: 'Tanggal Diverifikasi', value: new Date(props.item.tanggal_diverifikasi).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) });
}

const actionFields = [
    { label: 'Created At', value: props.item.created_at ? new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: props.item.updated_at ? new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    if (props.item.status === 'menunggu_verifikasi' || props.item.status === 'ditolak') {
        router.visit(`/pengajuan-proposal-saya/${props.item.id}/edit`);
    }
};

</script>

<template>
    <PageShow
        title="Pengajuan Proposal Saya"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/pengajuan-proposal-saya'"
        :on-edit="(item.status === 'menunggu_verifikasi' || item.status === 'ditolak') ? handleEdit : undefined"
    >
        <template #custom>
            <!-- File Pendukung -->
            <div v-if="item.file_pendukung && item.file_pendukung.length > 0" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">File Pendukung</div>
                <div class="space-y-2">
                    <a
                        v-for="(file, index) in item.file_pendukung"
                        :key="index"
                        :href="`/storage/${file}`"
                        target="_blank"
                        class="flex items-center gap-2 p-2 border rounded hover:bg-accent transition-colors"
                    >
                        <FileText class="w-4 h-4" />
                        <span class="text-sm">{{ file.split('/').pop() }}</span>
                    </a>
                </div>
            </div>

            <!-- Thumbnail/Foto Banner -->
            <div v-if="item.thumbnail_foto_banner" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">Thumbnail/Foto Banner</div>
                <img
                    :src="item.thumbnail_foto_banner.startsWith('http') ? item.thumbnail_foto_banner : `/storage/${item.thumbnail_foto_banner}`"
                    alt="Thumbnail"
                    class="w-full max-w-md h-auto rounded-lg border"
                />
            </div>

            <!-- Peta Lokasi -->
            <div class="mt-4">
                <LocationMapView
                    :latitude="item.latitude"
                    :longitude="item.longitude"
                    :marker-popup-text="item.nama_kegiatan"
                />
                <div v-if="item.nama_lokasi || item.alamat" class="mt-2 text-sm space-y-1">
                    <p v-if="item.nama_lokasi"><strong>Nama Lokasi:</strong> {{ item.nama_lokasi }}</p>
                    <p v-if="item.alamat"><strong>Alamat:</strong> {{ item.alamat }}</p>
                </div>
            </div>

            <!-- Tanda Tangan Digital -->
            <div v-if="item.tanda_tangan_digital" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">Tanda Tangan Digital</div>
                <img
                    :src="item.tanda_tangan_digital"
                    alt="Tanda Tangan Digital"
                    class="max-w-xs border rounded"
                />
            </div>
        </template>
    </PageShow>
</template>

