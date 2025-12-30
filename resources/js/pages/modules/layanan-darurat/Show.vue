<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed } from 'vue';
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
        kategori: string;
        kategori_label: string;
        latitude: string;
        longitude: string;
        title: string;
        alamat: string | null;
        nomor_whatsapp: string | null;
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
    { title: 'Layanan Darurat', href: '/layanan-darurat' },
    { title: 'Detail Layanan Darurat', href: `/layanan-darurat/${props.item.id}` },
];

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let marker: L.Marker | null = null;

const getWhatsAppLink = computed(() => {
    if (!props.item.nomor_whatsapp) return '#';
    const cleanNumber = props.item.nomor_whatsapp.replace(/[^0-9+]/g, '');
    const finalNumber = cleanNumber.startsWith('+') ? cleanNumber : `62${cleanNumber.replace(/^0/, '')}`;
    return `https://wa.me/${finalNumber}`;
});

const fields = [
    { label: 'Kategori', value: props.item.kategori_label },
    { label: 'Title', value: props.item.title },
    { label: 'Alamat', value: props.item.alamat || '-', className: 'sm:col-span-2' },
    {
        label: 'Nomor WhatsApp',
        value: props.item.nomor_whatsapp || '-',
        className: 'sm:col-span-2',
    },
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
        marker.bindPopup(`<b>${props.item.title}</b><br>${props.item.alamat || ''}`).openPopup();
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
    router.visit(`/layanan-darurat/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/layanan-darurat/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/layanan-darurat');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Layanan Darurat"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/layanan-darurat'"
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

            <!-- WhatsApp Link -->
            <div v-if="item.nomor_whatsapp" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">Kontak WhatsApp</div>
                <a
                    :href="getWhatsAppLink"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Chat via WhatsApp: {{ item.nomor_whatsapp }}
                </a>
            </div>
        </template>
    </PageShow>
</template>

