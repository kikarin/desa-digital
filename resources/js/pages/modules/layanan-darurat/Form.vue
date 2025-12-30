<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, onMounted, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Search, MapPin, Navigation } from 'lucide-vue-next';

// Fix untuk default marker icon di Leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let marker: L.Marker | null = null;

const formData = ref({
    kategori: props.initialData?.kategori || '',
    latitude: props.initialData?.latitude || '',
    longitude: props.initialData?.longitude || '',
    title: props.initialData?.title || '',
    alamat: props.initialData?.alamat || '',
    nomor_whatsapp: props.initialData?.nomor_whatsapp || '',
});

const isLoading = ref(false);
const isSearching = ref(false);
const searchQuery = ref('');
const searchResults = ref<Array<{ display_name: string; lat: string; lon: string }>>([]);
const showSearchResults = ref(false);
const activeTab = ref<'search' | 'current'>('search');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// Koordinat pusat Desa Galuga
const galugaLat = -6.5641311;
const galugaLng = 106.6438673;

onMounted(() => {
    if (mapContainer.value) {
        map = L.map(mapContainer.value, {
            zoomControl: true,
            scrollWheelZoom: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        if (props.mode === 'edit' && props.initialData?.latitude && props.initialData?.longitude) {
            const lat = parseFloat(props.initialData.latitude);
            const lng = parseFloat(props.initialData.longitude);
            map.setView([lat, lng], 16);
            marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup('<b>Lokasi Layanan Darurat</b>').openPopup();
        } else {
            map.setView([galugaLat, galugaLng], 14);
        }

        map.on('click', (e: L.LeafletMouseEvent) => {
            const { lat, lng } = e.latlng;
            setMarker(lat, lng);
            reverseGeocode(lat, lng);
        });
    }
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
    marker = null;
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

const setMarker = (lat: number, lng: number) => {
    if (marker) {
        map?.removeLayer(marker);
    }
    marker = L.marker([lat, lng]).addTo(map!);
    marker.bindPopup('<b>Lokasi Layanan Darurat</b>').openPopup();
    map?.setView([lat, lng], 16);
    
    formData.value.latitude = lat.toString();
    formData.value.longitude = lng.toString();
};

const searchLocation = () => {
    if (!searchQuery.value.trim()) {
        showSearchResults.value = false;
        return;
    }

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(async () => {
        try {
            isSearching.value = true;
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5&accept-language=id`,
                {
                    headers: {
                        'User-Agent': 'SIGAP-Desa-Digital/1.0',
                    },
                }
            );

            if (!response.ok) throw new Error('Search failed');
            const data = await response.json();
            searchResults.value = data;
            showSearchResults.value = true;
        } catch (error) {
            console.error('Search error:', error);
            toast({ title: 'Gagal mencari lokasi', variant: 'destructive' });
        } finally {
            isSearching.value = false;
        }
    }, 500);
};

const selectSearchResult = (result: { lat: string; lon: string; display_name: string }) => {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    setMarker(lat, lng);
    reverseGeocode(lat, lng);
    searchQuery.value = '';
    showSearchResults.value = false;
};

const useCurrentLocation = () => {
    if (!navigator.geolocation) {
        toast({ title: 'Geolocation tidak didukung', variant: 'destructive' });
        return;
    }

    isLoading.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            setMarker(lat, lng);
            reverseGeocode(lat, lng);
            isLoading.value = false;
        },
        (error) => {
            toast({ title: 'Gagal mendapatkan lokasi', variant: 'destructive' });
            isLoading.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

const reverseGeocode = async (lat: number, lng: number) => {
    try {
        isLoading.value = true;
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=id`,
            {
                headers: {
                    'User-Agent': 'SIGAP-Desa-Digital/1.0',
                },
            }
        );

        if (!response.ok) throw new Error('Reverse geocoding failed');
        const data = await response.json();
        const address = data.address || {};
        const road = address.road || '';
        const village = address.village || address.suburb || '';
        const district = address.district || address.city_district || '';
        const city = address.city || address.county || '';
        const alamatParts = [road, village, district, city].filter(Boolean);
        const alamat = alamatParts.join(', ');

        formData.value.alamat = alamat || data.display_name || '';
        formData.value.title = road || data.display_name || 'Layanan Darurat';
        
        toast({ title: 'Lokasi berhasil diambil', variant: 'success' });
    } catch (error) {
        console.error('Reverse geocoding error:', error);
        formData.value.alamat = '';
        formData.value.title = 'Layanan Darurat';
    } finally {
        isLoading.value = false;
    }
};

const handleSave = () => {
    if (!formData.value.kategori) {
        toast({ title: 'Kategori wajib diisi', variant: 'destructive' });
        return;
    }
    if (!formData.value.latitude || !formData.value.longitude) {
        toast({ title: 'Silakan pilih lokasi di peta', variant: 'default' });
        return;
    }
    if (!formData.value.title) {
        toast({ title: 'Title wajib diisi', variant: 'destructive' });
        return;
    }

    const submitFormData = new FormData();
    submitFormData.append('kategori', formData.value.kategori);
    submitFormData.append('latitude', formData.value.latitude);
    submitFormData.append('longitude', formData.value.longitude);
    submitFormData.append('title', formData.value.title);
    submitFormData.append('alamat', formData.value.alamat || '');
    submitFormData.append('nomor_whatsapp', formData.value.nomor_whatsapp || '');

    if (props.mode === 'edit' && props.initialData?.id) {
        submitFormData.append('id', String(props.initialData.id));
        submitFormData.append('_method', 'PUT');
        router.post(`/layanan-darurat/${props.initialData.id}`, submitFormData, {
            forceFormData: true,
            onSuccess: () => {
                toast({ title: 'Data berhasil diperbarui', variant: 'success' });
                router.visit(`/layanan-darurat/${props.initialData?.id}`);
            },
            onError: () => {
                toast({ title: 'Gagal memperbarui data', variant: 'destructive' });
            },
        });
    } else {
        router.post('/layanan-darurat', submitFormData, {
            forceFormData: true,
            onSuccess: () => {
                toast({ title: 'Data berhasil ditambahkan', variant: 'success' });
                router.visit('/layanan-darurat');
            },
            onError: () => {
                toast({ title: 'Gagal menambahkan data', variant: 'destructive' });
            },
        });
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Peta Section -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Pilih Lokasi</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Tabs -->
                <div class="flex gap-2 border-b border-border">
                    <button
                        type="button"
                        @click="activeTab = 'search'"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                            activeTab === 'search'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <Search class="w-4 h-4" />
                            Cari Lokasi
                        </div>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'current'"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                            activeTab === 'current'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <Navigation class="w-4 h-4" />
                            Lokasi Saat Ini
                        </div>
                    </button>
                </div>

                <!-- Search Tab -->
                <div v-if="activeTab === 'search'" class="space-y-3">
                    <div class="relative">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                            <Input
                                v-model="searchQuery"
                                @input="searchLocation"
                                @keyup.enter="searchLocation"
                                placeholder="Cari alamat atau nama tempat..."
                                class="pl-10"
                                :disabled="isSearching"
                            />
                            <div v-if="isSearching" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                            </div>
                        </div>
                        
                        <div
                            v-if="showSearchResults && searchResults.length > 0"
                            class="absolute z-[9999] w-full mt-1 bg-card border border-border rounded-md shadow-lg max-h-60 overflow-y-auto"
                        >
                            <div
                                v-for="(result, index) in searchResults"
                                :key="index"
                                @click="selectSearchResult(result)"
                                class="px-4 py-3 hover:bg-accent cursor-pointer border-b border-border last:border-b-0 transition-colors"
                            >
                                <div class="flex items-start gap-2">
                                    <MapPin class="w-4 h-4 text-muted-foreground mt-0.5 shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">
                                            {{ result.display_name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Location Tab -->
                <div v-if="activeTab === 'current'" class="space-y-3">
                    <Button
                        type="button"
                        @click="useCurrentLocation"
                        :disabled="isLoading"
                        variant="outline"
                        class="w-full"
                    >
                        <Navigation class="w-4 h-4 mr-2" />
                        {{ isLoading ? 'Mengambil lokasi...' : 'Gunakan Lokasi Saat Ini' }}
                    </Button>
                </div>

                <!-- Map -->
                <div ref="mapContainer" class="h-[400px] w-full rounded-lg border border-border z-0"></div>
                <p v-if="formData.latitude && formData.longitude" class="text-xs text-muted-foreground">
                    Koordinat: {{ formData.latitude }}, {{ formData.longitude }}
                </p>
            </CardContent>
        </Card>

        <!-- Form Fields -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Informasi Layanan Darurat</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <label for="kategori" class="block text-sm font-medium mb-2">
                        Kategori <span class="text-destructive">*</span>
                    </label>
                    <Select v-model="formData.kategori">
                        <SelectTrigger id="kategori">
                            <SelectValue placeholder="Pilih kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="polsek">Polsek</SelectItem>
                            <SelectItem value="puskesmas">Puskesmas</SelectItem>
                            <SelectItem value="pemadam_kebakaran">Pemadam Kebakaran</SelectItem>
                            <SelectItem value="rumah_sakit">Rumah Sakit</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium mb-2">
                        Title <span class="text-destructive">*</span>
                    </label>
                    <Input
                        id="title"
                        v-model="formData.title"
                        type="text"
                        placeholder="Title akan terisi otomatis dari nama jalan"
                        required
                    />
                </div>

                <div>
                    <label for="alamat" class="block text-sm font-medium mb-2">Alamat</label>
                    <textarea
                        id="alamat"
                        v-model="formData.alamat"
                        rows="2"
                        class="w-full px-3 py-2 border border-input rounded-md bg-transparent text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                        placeholder="Alamat akan terisi otomatis"
                    ></textarea>
                </div>

                <div>
                    <label for="nomor_whatsapp" class="block text-sm font-medium mb-2">Nomor WhatsApp</label>
                    <Input
                        id="nomor_whatsapp"
                        v-model="formData.nomor_whatsapp"
                        type="text"
                        placeholder="Contoh: 081234567890"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex gap-4 justify-end">
            <Button
                type="button"
                @click="router.visit('/layanan-darurat')"
                variant="outline"
            >
                Batal
            </Button>
            <Button
                type="button"
                @click="handleSave"
                :disabled="isLoading"
                variant="default"
            >
                {{ isLoading ? 'Menyimpan...' : 'Simpan' }}
            </Button>
        </div>
    </div>
</template>

