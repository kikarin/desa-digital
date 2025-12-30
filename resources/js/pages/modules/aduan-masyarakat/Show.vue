<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix untuk default marker icon di Leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        kategori_aduan_nama: string;
        judul: string;
        detail_aduan: string;
        latitude: string | null;
        longitude: string | null;
        nama_lokasi: string | null;
        kecamatan_nama: string;
        desa_nama: string;
        deskripsi_lokasi: string | null;
        jenis_aduan: string;
        alasan_melaporkan: string | null;
        status: string;
        files: Array<{
            id: number;
            file_path: string;
            file_type: string;
            file_name: string;
        }>;
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
    can?: {
        Verifikasi?: boolean;
        Delete?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Aduan Masyarakat', href: '/aduan-masyarakat' },
    { title: 'Detail Aduan', href: `/aduan-masyarakat/${props.item.id}` },
];

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let marker: L.Marker | null = null;

const fields = [
    { label: 'Kategori', value: props.item.kategori_aduan_nama },
    { label: 'Judul', value: props.item.judul },
    { label: 'Detail Aduan', value: props.item.detail_aduan, className: 'sm:col-span-2' },
    { label: 'Jenis Aduan', value: props.item.jenis_aduan === 'publik' ? 'Publik' : 'Private' },
    { label: 'Status', value: props.item.status === 'selesai' ? 'Selesai' : props.item.status === 'dibatalkan' ? 'Dibatalkan' : 'Menunggu Verifikasi' },
    { label: 'Kecamatan', value: props.item.kecamatan_nama || '-' },
    { label: 'Kelurahan/Desa', value: props.item.desa_nama || '-' },
    { label: 'Nama Lokasi', value: props.item.nama_lokasi || '-', className: 'sm:col-span-2' },
    { label: 'Deskripsi Lokasi', value: props.item.deskripsi_lokasi || '-', className: 'sm:col-span-2' },
    { label: 'Alasan Melaporkan', value: props.item.alasan_melaporkan || '-', className: 'sm:col-span-2' },
];

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

onMounted(() => {
    if (mapContainer.value && props.item.latitude && props.item.longitude) {
        const lat = parseFloat(props.item.latitude);
        const lng = parseFloat(props.item.longitude);

        map = L.map(mapContainer.value, {
            zoomControl: true,
            scrollWheelZoom: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        map.setView([lat, lng], 16);

        marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(`<b>${props.item.judul}</b><br>${props.item.nama_lokasi || ''}`).openPopup();
    }
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
    marker = null;
});

const handleVerifikasi = () => {
    router.visit(`/aduan-masyarakat/${props.item.id}/verifikasi`);
};

const handleDelete = () => {
    router.delete(`/aduan-masyarakat/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/aduan-masyarakat');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Aduan Masyarakat"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/aduan-masyarakat'"
        :on-delete="item.status !== 'selesai' && can?.Delete ? handleDelete : undefined"
    >
        <template #custom-action>
            <Button
                v-if="item.status === 'menunggu_verifikasi' && can?.Verifikasi"
                @click="handleVerifikasi"
                variant="default"
            >
                Verifikasi
            </Button>
        </template>
        <template #custom>
            <!-- Peta Lokasi -->
            <div v-if="item.latitude && item.longitude" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">Lokasi di Peta</div>
                <div
                    ref="mapContainer"
                    class="h-[400px] w-full rounded-lg border border-border"
                ></div>
                <p class="text-xs text-muted-foreground mt-2">
                    Koordinat: {{ item.latitude }}, {{ item.longitude }}
                </p>
            </div>

            <!-- Files -->
            <div v-if="item.files && item.files.length > 0" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">Bukti Laporan</div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div v-for="file in item.files" :key="file.id" class="space-y-2">
                        <div v-if="file.file_type === 'foto'">
                            <img :src="file.file_path" :alt="file.file_name" class="w-full h-32 object-cover rounded border" />
                        </div>
                        <div v-else class="border rounded p-4 text-center">
                            <video :src="file.file_path" controls class="w-full h-32 rounded"></video>
                        </div>
                        <p class="text-xs text-muted-foreground truncate">{{ file.file_name }}</p>
                    </div>
                </div>
            </div>
        </template>
    </PageShow>
</template>
