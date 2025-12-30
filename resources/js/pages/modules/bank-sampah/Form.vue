<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    latitude: props.initialData?.latitude || '',
    longitude: props.initialData?.longitude || '',
    nama_lokasi: props.initialData?.nama_lokasi || '',
    alamat: props.initialData?.alamat || '',
    title: props.initialData?.title || '',
    foto: props.initialData?.foto || null,
    deskripsi: props.initialData?.deskripsi || '',
});

const isLoading = ref(false);
const isSearching = ref(false);
const fotoFile = ref<File | null>(null);
const fotoPreview = ref<string | null>(props.initialData?.foto || null);
const searchQuery = ref('');
const searchResults = ref<Array<{ display_name: string; lat: string; lon: string; address?: any }>>([]);
const showSearchResults = ref(false);
const activeTab = ref<'search' | 'current'>('search');

// Koordinat pusat Desa Galuga
const galugaLat = -6.5641311;
const galugaLng = 106.6438673;

onMounted(() => {
    if (mapContainer.value) {
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

        // Set view berdasarkan mode
        if (props.mode === 'edit' && props.initialData?.latitude && props.initialData?.longitude) {
            const lat = parseFloat(props.initialData.latitude);
            const lng = parseFloat(props.initialData.longitude);
            map.setView([lat, lng], 16);
            
            // Tambahkan marker
            marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup('<b>Lokasi Bank Sampah</b>').openPopup();
        } else {
            map.setView([galugaLat, galugaLng], 14);
        }

        // Event click untuk marking manual
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
    if (!map) return;

    // Hapus marker lama jika ada
    if (marker) {
        map.removeLayer(marker);
    }

    // Tambahkan marker baru
    marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup('<b>Lokasi Bank Sampah</b>').openPopup();

    // Update form data
    formData.value.latitude = lat.toString();
    formData.value.longitude = lng.toString();
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const searchLocation = async () => {
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        showSearchResults.value = false;
        return;
    }

    // Debounce search - wait 1 second after user stops typing
    searchTimeout = setTimeout(async () => {
        try {
            isSearching.value = true;
            
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5&addressdetails=1&accept-language=id`,
                {
                    headers: {
                        'User-Agent': 'SIGAP-Desa-Digital/1.0',
                    },
                }
            );
            
            if (!response.ok) {
                throw new Error('Search failed');
            }
            
            const data = await response.json();
            searchResults.value = data;
            showSearchResults.value = data.length > 0;
            
            if (data.length === 0) {
                toast({
                    title: 'Lokasi tidak ditemukan',
                    variant: 'default',
                });
            }
        } catch (error) {
            console.error('Search error:', error);
            toast({
                title: 'Gagal mencari lokasi',
                variant: 'destructive',
            });
            searchResults.value = [];
            showSearchResults.value = false;
        } finally {
            isSearching.value = false;
        }
    }, 1000);
};

const selectSearchResult = (result: { display_name: string; lat: string; lon: string; address?: any }) => {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    
    setMarker(lat, lng);
    
    // Extract address dari result
    const address = result.address || {};
    const road = address.road || '';
    const village = address.village || address.suburb || '';
    const district = address.district || address.city_district || '';
    const city = address.city || address.county || '';
    
    const alamatParts = [road, village, district, city].filter(Boolean);
    const alamat = alamatParts.join(', ');
    
    formData.value.nama_lokasi = result.display_name;
    formData.value.alamat = alamat || result.display_name;
    formData.value.title = road || result.display_name || 'Bank Sampah';
    
    searchQuery.value = '';
    searchResults.value = [];
    showSearchResults.value = false;
    
    toast({
        title: 'Lokasi dipilih',
        variant: 'success',
    });
};

const useCurrentLocation = () => {
    if (!navigator.geolocation) {
        toast({
            title: 'Geolocation tidak didukung oleh browser Anda',
            variant: 'destructive',
        });
        return;
    }

    isLoading.value = true;
    
    // Opsi geolocation untuk akurasi tinggi
    const options = {
        enableHighAccuracy: true, // Gunakan GPS jika tersedia
        timeout: 10000, // Timeout 10 detik
        maximumAge: 0, // Jangan gunakan cache, selalu ambil lokasi baru
    };
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy; // Akurasi dalam meter
            
            // Validasi akurasi - jika terlalu tidak akurat (> 100 meter), beri peringatan
            if (accuracy > 100) {
                toast({
                    title: `Lokasi ditemukan dengan akurasi ±${Math.round(accuracy)}m. Pastikan GPS aktif untuk hasil lebih akurat.`,
                    variant: 'default',
                });
            }
            
            setMarker(lat, lng);
            reverseGeocode(lat, lng);
            isLoading.value = false;
        },
        (error) => {
            console.error('Geolocation error:', error);
            let errorMessage = 'Gagal mendapatkan lokasi saat ini';
            
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = 'Akses lokasi ditolak. Silakan izinkan akses lokasi di pengaturan browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = 'Informasi lokasi tidak tersedia. Pastikan GPS aktif.';
                    break;
                case error.TIMEOUT:
                    errorMessage = 'Waktu tunggu habis. Pastikan GPS aktif dan coba lagi.';
                    break;
            }
            
            toast({
                title: errorMessage,
                variant: 'destructive',
            });
            isLoading.value = false;
        },
        options
    );
};

const reverseGeocode = async (lat: number, lng: number) => {
    try {
        isLoading.value = true;
        
        // Debounce untuk menghindari rate limit
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=id`,
            {
                headers: {
                    'User-Agent': 'SIGAP-Desa-Digital/1.0',
                },
            }
        );
        
        if (!response.ok) {
            throw new Error('Reverse geocoding failed');
        }
        
        const data = await response.json();
        
        // Extract data dari response
        const address = data.address || {};
        const road = address.road || '';
        const village = address.village || address.suburb || '';
        const district = address.district || address.city_district || '';
        const city = address.city || address.county || '';
        
        // Build alamat
        const alamatParts = [road, village, district, city].filter(Boolean);
        const alamat = alamatParts.join(', ');
        
        // Update form data
        formData.value.nama_lokasi = data.display_name || alamat || 'Lokasi Bank Sampah';
        formData.value.alamat = alamat || data.display_name || '';
        formData.value.title = road || data.display_name || 'Bank Sampah';
        
        toast({
            title: 'Lokasi berhasil diambil',
            variant: 'success',
        });
    } catch (error) {
        console.error('Reverse geocoding error:', error);
        // Fallback jika geocoding gagal
        formData.value.nama_lokasi = `Lokasi Bank Sampah (${lat.toFixed(6)}, ${lng.toFixed(6)})`;
        formData.value.alamat = '';
        formData.value.title = 'Bank Sampah';
        
        toast({
            title: 'Gagal mengambil detail lokasi, silakan isi manual',
            variant: 'default',
        });
    } finally {
        isLoading.value = false;
    }
};

const handleFotoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        fotoFile.value = target.files[0];
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            fotoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(target.files[0]);
    }
};

const handleSave = () => {
    // Validasi
    if (!formData.value.latitude || !formData.value.longitude) {
        toast({
            title: 'Silakan pilih lokasi di peta atau gunakan lokasi saat ini',
            variant: 'default',
        });
        return;
    }

    if (!formData.value.title) {
        toast({
            title: 'Title wajib diisi',
            variant: 'destructive',
        });
        return;
    }

    const submitFormData = new FormData();
    
    submitFormData.append('latitude', formData.value.latitude);
    submitFormData.append('longitude', formData.value.longitude);
    submitFormData.append('nama_lokasi', formData.value.nama_lokasi);
    submitFormData.append('alamat', formData.value.alamat || '');
    submitFormData.append('title', formData.value.title);
    submitFormData.append('deskripsi', formData.value.deskripsi || '');
    
    // Handle file upload
    if (fotoFile.value) {
        submitFormData.append('foto', fotoFile.value);
    } else if (props.mode === 'edit' && !fotoFile.value && fotoPreview.value && !fotoPreview.value.startsWith('data:')) {
        // Keep existing foto
    } else if (props.mode === 'edit' && fotoPreview.value === null) {
        // Delete foto
        submitFormData.append('foto', '');
    }
    
    if (props.mode === 'edit' && props.initialData?.id) {
        submitFormData.append('id', String(props.initialData.id));
        submitFormData.append('_method', 'PUT');
        
        router.post(`/bank-sampah/${props.initialData.id}`, submitFormData, {
            forceFormData: true,
            onSuccess: () => {
                toast({
                    title: 'Data berhasil diperbarui',
                    variant: 'success',
                });
                router.visit(`/bank-sampah/${props.initialData?.id}`);
            },
            onError: (errors) => {
                toast({
                    title: 'Gagal memperbarui data',
                    variant: 'destructive',
                });
            },
        });
    } else {
        router.post('/bank-sampah', submitFormData, {
            forceFormData: true,
            onSuccess: () => {
                toast({
                    title: 'Data berhasil ditambahkan',
                    variant: 'success',
                });
                router.visit('/bank-sampah');
            },
            onError: (errors) => {
                toast({
                    title: 'Gagal menambahkan data',
                    variant: 'destructive',
                });
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
                            <div
                                v-if="isSearching"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2"
                            >
                                <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                            </div>
                        </div>
                        
                        <!-- Search Results Dropdown -->
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
                    <p class="text-xs text-muted-foreground">
                        Ketik nama jalan, tempat, atau alamat untuk mencari lokasi
                    </p>
                </div>

                <!-- Current Location Tab -->
                <div v-if="activeTab === 'current'" class="space-y-3">
                    <Button
                        type="button"
                        @click="useCurrentLocation"
                        :disabled="isLoading"
                        variant="default"
                        class="w-full"
                    >
                        <Navigation class="w-4 h-4 mr-2" />
                        {{ isLoading ? 'Memuat Lokasi...' : 'Gunakan Lokasi Saat Ini' }}
                    </Button>
                    <p class="text-xs text-muted-foreground">
                        Izinkan akses lokasi browser untuk menggunakan posisi saat ini
                    </p>
                </div>

                <!-- Map -->
                <div class="space-y-2">
                    <p class="text-sm text-muted-foreground">
                        Atau klik di peta untuk memilih lokasi secara manual
                    </p>
                    <div
                        ref="mapContainer"
                        class="relative z-0 h-[400px] w-full rounded-lg border border-border"
                    ></div>
                    <p class="text-xs text-muted-foreground">
                        Koordinat: {{ formData.latitude && formData.longitude ? `${formData.latitude}, ${formData.longitude}` : 'Belum dipilih' }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Form Fields -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Informasi Bank Sampah</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <label for="nama_lokasi" class="block text-sm font-medium mb-2">
                        Nama Lokasi <span class="text-destructive">*</span>
                    </label>
                    <Input
                        id="nama_lokasi"
                        v-model="formData.nama_lokasi"
                        type="text"
                        placeholder="Nama lokasi akan terisi otomatis"
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
                    <label for="foto" class="block text-sm font-medium mb-2">Foto</label>
                    <Input
                        id="foto"
                        type="file"
                        accept="image/*"
                        @change="handleFotoChange"
                    />
                    <p class="text-xs text-muted-foreground mt-1">Format: JPG, PNG, Max 2MB</p>
                    <div v-if="fotoPreview" class="mt-2">
                        <img :src="fotoPreview" alt="Preview" class="w-32 h-32 object-cover rounded border border-border" />
                    </div>
                </div>

                <div>
                    <label for="deskripsi" class="block text-sm font-medium mb-2">Deskripsi</label>
                    <textarea
                        id="deskripsi"
                        v-model="formData.deskripsi"
                        rows="4"
                        class="w-full px-3 py-2 border border-input rounded-md bg-transparent text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                        placeholder="Masukkan deskripsi bank sampah"
                    ></textarea>
                </div>
            </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex gap-4 justify-end">
            <Button
                type="button"
                @click="router.visit('/bank-sampah')"
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

