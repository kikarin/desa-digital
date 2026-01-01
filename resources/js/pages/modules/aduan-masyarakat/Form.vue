<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { ref, onMounted, watch } from 'vue';
import { Search, MapPin, Navigation, X, Upload } from 'lucide-vue-next';
import axios from 'axios';
import LocationMapPicker from '@/components/LocationMapPicker.vue';

const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

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

// Dropdown options
const kategoriOptions = ref<Array<{ value: number; label: string }>>([]);
const kecamatanOptions = ref<Array<{ value: number; label: string }>>([]);
const desaOptions = ref<Array<{ value: number; label: string }>>([]);

// Files
const files = ref<File[]>([]);
const filePreviews = ref<Array<{ url: string; type: 'foto' | 'video'; name: string }>>([]);
const existingFiles = ref<Array<{ id: number; file_path: string; file_type: string; file_name: string }>>(props.initialData?.files || []);
const deletedFileIds = ref<number[]>([]);

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

    // Load existing files preview
    if (existingFiles.value.length > 0) {
        filePreviews.value = existingFiles.value.map((file) => ({
            url: file.file_path,
            type: file.file_type as 'foto' | 'video',
            name: file.file_name || 'File',
        }));
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

// Handle location selected from LocationMapPicker
const handleLocationSelected = (data: { lat: number; lng: number; address?: string }) => {
    if (data.address) {
        formData.value.deskripsi_lokasi = data.address;
        // Extract display name from address or use coordinates
        const addressParts = data.address.split(',');
        formData.value.nama_lokasi = addressParts[0] || `Lokasi Aduan (${data.lat.toFixed(6)}, ${data.lng.toFixed(6)})`;
    } else {
        formData.value.nama_lokasi = `Lokasi Aduan (${data.lat.toFixed(6)}, ${data.lng.toFixed(6)})`;
    }
    toast({
        title: 'Lokasi berhasil diambil',
        variant: 'success',
    });
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
        <LocationMapPicker
            v-model:latitude="formData.latitude"
            v-model:longitude="formData.longitude"
            marker-popup-text="Lokasi Aduan"
            @location-selected="handleLocationSelected"
        />

        <!-- Informasi Lokasi -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Informasi Lokasi</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
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

