<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
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
        latitude: string;
        longitude: string;
        nama_lokasi: string;
        alamat: string | null;
        title: string;
        foto: string | null;
        deskripsi: string | null;
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
    { title: 'Bank Sampah', href: '/bank-sampah' },
    { title: 'Detail Bank Sampah', href: `/bank-sampah/${props.item.id}` },
];

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let marker: L.Marker | null = null;

const fields = [
    { label: 'Nama Lokasi', value: props.item.nama_lokasi },
    { label: 'Alamat', value: props.item.alamat || '-', className: 'sm:col-span-2' },
    { label: 'Title', value: props.item.title },
    { label: 'Deskripsi', value: props.item.deskripsi || '-', className: 'sm:col-span-2' },
];

const actionFields = [
    {
        label: 'Created At',
        value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }),
    },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    {
        label: 'Updated At',
        value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }),
    },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

onMounted(() => {
    if (mapContainer.value && props.item.latitude && props.item.longitude) {
        const lat = parseFloat(props.item.latitude);
        const lng = parseFloat(props.item.longitude);

        // Inisialisasi peta
        map = L.map(mapContainer.value, {
            zoomControl: true,
            scrollWheelZoom: true,
        });

        // Tambahkan tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        // Set view ke lokasi
        map.setView([lat, lng], 16);

        // Tambahkan marker
        marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(`<b>${props.item.title}</b><br>${props.item.nama_lokasi}`).openPopup();
    }
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
    marker = null;
});

const handleEdit = () => {
    router.visit(`/bank-sampah/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/bank-sampah/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/bank-sampah');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Bank Sampah"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/bank-sampah'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
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

            <!-- Foto -->
            <div v-if="item.foto" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">Foto</div>
                <img :src="item.foto" :alt="item.title" class="max-w-full h-auto rounded-lg border" />
            </div>
        </template>
    </PageShow>
</template>

