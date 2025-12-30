<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Search, MapPin, Navigation, X, Upload } from 'lucide-vue-next';
import axios from 'axios';

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
    kategori_aduan_id: props.initialData?.kategori_aduan_id || '',
    judul: props.initialData?.judul || '',
    detail_aduan: props.initialData?.detail_aduan || '',
    latitude: props.initialData?.latitude || '',
    longitude: props.initialData?.longitude || '',
    nama_lokasi: props.initialData?.nama_lokasi || '',
    kecamatan_id: props.initialData?.kecamatan_id || '',
    desa_id: props.initialData?.desa_id || '',
    deskripsi_lokasi: props.initialData?.deskripsi_lokasi || '',
    jenis_aduan: props.initialData?.jenis_aduan || 'publik',
    alasan_melaporkan: props.initialData?.alasan_melaporkan || '',
});

const isLoading = ref(false);
const isSearching = ref(false);
const searchQuery = ref('');
const searchResults = ref<Array<{ display_name: string; lat: string; lon: string; address?: any }>>([]);
const showSearchResults = ref(false);
const activeTab = ref<'search' | 'current'>('search');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// Dropdown options
const kategoriOptions = ref<Array<{ value: number; label: string }>>([]);
const kecamatanOptions = ref<Array<{ value: number; label: string }>>([]);
const desaOptions = ref<Array<{ value: number; label: string }>>([]);

// Files
const files = ref<File[]>([]);
const filePreviews = ref<Array<{ url: string; type: 'foto' | 'video'; name: string }>>([]);
const existingFiles = ref<Array<{ id: number; file_path: string; file_type: string; file_name: string }>>(props.initialData?.files || []);
const deletedFileIds = ref<number[]>([]);

// Koordinat pusat Desa Galuga
const galugaLat = -6.5641311;
const galugaLng = 106.6438673;

// Load dropdown options
onMounted(async () => {
    // Load kategori aduan
    try {
        const response = await axios.get('/api/aduan/kategori-aduan');
        // Response dari getKategoriAduan() adalah array langsung
        if (response.data && Array.isArray(response.data)) {
            kategoriOptions.value = response.data.map((item: any) => ({
                value: item.value || item.id,
                label: item.label || item.nama,
            }));
        } else {
            console.error('Format response tidak valid:', response.data);
        }
    } catch (error) {
        console.error('Gagal mengambil kategori aduan:', error);
        toast({
            title: 'Gagal memuat kategori aduan. Pastikan data kategori sudah ada.',
            variant: 'destructive',
        });
    }

    // Load kecamatan
    try {
        const response = await axios.get('/api/kecamatan');
        kecamatanOptions.value = response.data || [];
    } catch (error) {
        console.error('Gagal mengambil kecamatan:', error);
    }

    // Load desa jika kecamatan sudah dipilih
    if (formData.value.kecamatan_id) {
        loadDesa(formData.value.kecamatan_id);
    }

    // Initialize map
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
            marker.bindPopup('<b>Lokasi Aduan</b>').openPopup();
        } else {
            map.setView([galugaLat, galugaLng], 14);
        }

        map.on('click', (e: L.LeafletMouseEvent) => {
            const { lat, lng } = e.latlng;
            setMarker(lat, lng);
            reverseGeocode(lat, lng);
        });
    }

    // Load existing files preview
    if (existingFiles.value.length > 0) {
        filePreviews.value = existingFiles.value.map((file) => ({
            url: file.file_path,
            type: file.file_type as 'foto' | 'video',
            name: file.file_name || 'File',
        }));
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

const loadDesa = async (kecamatanId: number) => {
    try {
        const response = await axios.get(`/api/desa/${kecamatanId}`);
        desaOptions.value = response.data || [];
    } catch (error) {
        console.error('Gagal mengambil desa:', error);
        desaOptions.value = [];
    }
};

watch(() => formData.value.kecamatan_id, (newVal) => {
    if (newVal) {
        loadDesa(newVal);
        formData.value.desa_id = ''; // Reset desa saat kecamatan berubah
    } else {
        desaOptions.value = [];
        formData.value.desa_id = '';
    }
});

const setMarker = (lat: number, lng: number) => {
    if (!map) return;

    if (marker) {
        map.removeLayer(marker);
    }

    marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup('<b>Lokasi Aduan</b>').openPopup();

    formData.value.latitude = lat.toString();
    formData.value.longitude = lng.toString();
};

const searchLocation = async () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        showSearchResults.value = false;
        return;
    }

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
    
    const address = result.address || {};
    const road = address.road || '';
    const village = address.village || address.suburb || '';
    const district = address.district || address.city_district || '';
    const city = address.city || address.county || '';
    
    const alamatParts = [road, village, district, city].filter(Boolean);
    const alamat = alamatParts.join(', ');
    
    formData.value.nama_lokasi = result.display_name;
    formData.value.deskripsi_lokasi = alamat || result.display_name;
    
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
    
    const options = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
    };
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
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
        const address = data.address || {};
        const road = address.road || '';
        const village = address.village || address.suburb || '';
        const district = address.district || address.city_district || '';
        const city = address.city || address.county || '';
        
        const alamatParts = [road, village, district, city].filter(Boolean);
        const alamat = alamatParts.join(', ');
        
        formData.value.nama_lokasi = data.display_name || alamat || 'Lokasi Aduan';
        formData.value.deskripsi_lokasi = alamat || data.display_name || '';
        
        toast({
            title: 'Lokasi berhasil diambil',
            variant: 'success',
        });
    } catch (error) {
        console.error('Reverse geocoding error:', error);
        formData.value.nama_lokasi = `Lokasi Aduan (${lat.toFixed(6)}, ${lng.toFixed(6)})`;
        formData.value.deskripsi_lokasi = '';
        
        toast({
            title: 'Gagal mengambil detail lokasi, silakan isi manual',
            variant: 'default',
        });
    } finally {
        isLoading.value = false;
    }
};

const handleFilesChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        Array.from(target.files).forEach((file) => {
            files.value.push(file);
            
            const reader = new FileReader();
            reader.onload = (e) => {
                const isVideo = file.type.startsWith('video/');
                filePreviews.value.push({
                    url: e.target?.result as string,
                    type: isVideo ? 'video' : 'foto',
                    name: file.name,
                });
            };
            reader.readAsDataURL(file);
        });
    }
};

const removeFile = (index: number) => {
    files.value.splice(index, 1);
    filePreviews.value.splice(index, 1);
};

const removeExistingFile = (fileId: number) => {
    deletedFileIds.value.push(fileId);
    existingFiles.value = existingFiles.value.filter((f) => f.id !== fileId);
    filePreviews.value = filePreviews.value.filter((f, idx) => {
        const existingFile = existingFiles.value.find((ef) => ef.id === fileId);
        return !existingFile || idx < existingFiles.value.length;
    });
};

const handleSave = () => {
    // Validasi
    if (!formData.value.kategori_aduan_id) {
        toast({
            title: 'Kategori aduan wajib diisi',
            variant: 'destructive',
        });
        return;
    }

    if (!formData.value.judul) {
        toast({
            title: 'Judul wajib diisi',
            variant: 'destructive',
        });
        return;
    }

    if (!formData.value.detail_aduan) {
        toast({
            title: 'Detail aduan wajib diisi',
            variant: 'destructive',
        });
        return;
    }

    const submitFormData = new FormData();
    
    submitFormData.append('kategori_aduan_id', String(formData.value.kategori_aduan_id));
    submitFormData.append('judul', formData.value.judul);
    submitFormData.append('detail_aduan', formData.value.detail_aduan);
    submitFormData.append('latitude', formData.value.latitude || '');
    submitFormData.append('longitude', formData.value.longitude || '');
    submitFormData.append('nama_lokasi', formData.value.nama_lokasi || '');
    submitFormData.append('kecamatan_id', formData.value.kecamatan_id || '');
    submitFormData.append('desa_id', formData.value.desa_id || '');
    submitFormData.append('deskripsi_lokasi', formData.value.deskripsi_lokasi || '');
    submitFormData.append('jenis_aduan', formData.value.jenis_aduan);
    submitFormData.append('alasan_melaporkan', formData.value.alasan_melaporkan || '');
    
    // Append files
    files.value.forEach((file) => {
        submitFormData.append('files[]', file);
    });
    
    // Append deleted file IDs
    deletedFileIds.value.forEach((fileId) => {
        submitFormData.append('deleted_files[]', String(fileId));
    });
    
    if (props.mode === 'edit' && props.initialData?.id) {
        submitFormData.append('id', String(props.initialData.id));
        submitFormData.append('_method', 'PUT');
        
        router.post(`/aduan-saya/${props.initialData.id}`, submitFormData, {
            forceFormData: true,
            onSuccess: () => {
                toast({
                    title: 'Data berhasil diperbarui',
                    variant: 'success',
                });
                router.visit(`/aduan-saya/${props.initialData?.id}`);
            },
            onError: (errors) => {
                toast({
                    title: 'Gagal memperbarui data',
                    variant: 'destructive',
                });
            },
        });
    } else {
        router.post('/aduan-saya', submitFormData, {
            forceFormData: true,
            onSuccess: () => {
                toast({
                    title: 'Aduan berhasil dibuat',
                    variant: 'success',
                });
                router.visit('/aduan-saya');
            },
            onError: (errors) => {
                toast({
                    title: 'Gagal membuat aduan',
                    variant: 'destructive',
                });
            },
        });
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Kategori & Judul -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Informasi Aduan</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <Label for="kategori_aduan_id">
                        Kategori Aduan <span class="text-destructive">*</span>
                    </Label>
                    <Select
                        v-model="formData.kategori_aduan_id"
                        :disabled="kategoriOptions.length === 0"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih kategori aduan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in kategoriOptions"
                                :key="option.value"
                                :value="String(option.value)"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label for="judul">
                        Judul <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="judul"
                        v-model="formData.judul"
                        type="text"
                        placeholder="Masukkan judul aduan"
                        required
                    />
                </div>

                <div>
                    <Label for="detail_aduan">
                        Detail Aduan <span class="text-destructive">*</span>
                    </Label>
                    <textarea
                        id="detail_aduan"
                        v-model="formData.detail_aduan"
                        rows="4"
                        class="w-full px-3 py-2 border border-input rounded-md bg-transparent text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                        placeholder="Masukkan detail aduan"
                        required
                    ></textarea>
                </div>
            </CardContent>
        </Card>

        <!-- Lokasi Aduan -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Lokasi Aduan</CardTitle>
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

                <!-- Kecamatan & Desa -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label for="kecamatan_id">Kecamatan</Label>
                        <Select
                            v-model="formData.kecamatan_id"
                            :disabled="kecamatanOptions.length === 0"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih kecamatan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in kecamatanOptions"
                                    :key="option.value"
                                    :value="String(option.value)"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label for="desa_id">Kelurahan/Desa</Label>
                        <Select
                            v-model="formData.desa_id"
                            :disabled="!formData.kecamatan_id || desaOptions.length === 0"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih kelurahan/desa" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in desaOptions"
                                    :key="option.value"
                                    :value="String(option.value)"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div>
                    <Label for="nama_lokasi">Nama Lokasi</Label>
                    <Input
                        id="nama_lokasi"
                        v-model="formData.nama_lokasi"
                        type="text"
                        placeholder="Nama lokasi akan terisi otomatis"
                    />
                </div>

                <div>
                    <Label for="deskripsi_lokasi">Deskripsi Lokasi</Label>
                    <textarea
                        id="deskripsi_lokasi"
                        v-model="formData.deskripsi_lokasi"
                        rows="2"
                        class="w-full px-3 py-2 border border-input rounded-md bg-transparent text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                        placeholder="Deskripsi lokasi akan terisi otomatis"
                    ></textarea>
                </div>
            </CardContent>
        </Card>

        <!-- Bukti Laporan -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Bukti Laporan</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <Label for="files">Upload Foto/Video</Label>
                    <Input
                        id="files"
                        type="file"
                        accept="image/*,video/*"
                        multiple
                        @change="handleFilesChange"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Format: JPG, PNG, MP4, MOV, AVI. Max 10MB per file. Bisa upload lebih dari 1 file.
                    </p>
                </div>

                <!-- File Previews -->
                <div v-if="filePreviews.length > 0" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div
                        v-for="(preview, index) in filePreviews"
                        :key="index"
                        class="relative group"
                    >
                        <div v-if="preview.type === 'foto'">
                            <img :src="preview.url" :alt="preview.name" class="w-full h-32 object-cover rounded border" />
                        </div>
                        <div v-else class="border rounded p-4 text-center">
                            <video :src="preview.url" controls class="w-full h-32 rounded"></video>
                        </div>
                        <button
                            type="button"
                            @click="index < existingFiles.length ? removeExistingFile(existingFiles[index].id) : removeFile(index - existingFiles.length)"
                            class="absolute top-2 right-2 p-1 bg-destructive text-destructive-foreground rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <X class="w-4 h-4" />
                        </button>
                        <p class="text-xs text-muted-foreground mt-1 truncate">{{ preview.name }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Jenis Aduan & Alasan -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Informasi Tambahan</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <Label>Jenis Aduan <span class="text-destructive">*</span></Label>
                    <RadioGroup v-model="formData.jenis_aduan" class="mt-2">
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="publik" id="publik" />
                            <Label for="publik" class="cursor-pointer">Publik</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="private" id="private" />
                            <Label for="private" class="cursor-pointer">Private</Label>
                        </div>
                    </RadioGroup>
                </div>

                <div>
                    <Label for="alasan_melaporkan">Alasan Melaporkan</Label>
                    <textarea
                        id="alasan_melaporkan"
                        v-model="formData.alasan_melaporkan"
                        rows="3"
                        class="w-full px-3 py-2 border border-input rounded-md bg-transparent text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                        placeholder="Masukkan alasan melaporkan (opsional)"
                    ></textarea>
                </div>
            </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex gap-4 justify-end">
            <Button
                type="button"
                @click="router.visit('/aduan-saya')"
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

