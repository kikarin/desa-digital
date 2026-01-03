<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { ref, onMounted, onUnmounted, nextTick, watch, computed } from 'vue';
import { Search, MapPin, Navigation, X, Upload, FileText } from 'lucide-vue-next';
import axios from 'axios';
import LocationMapPicker from '@/components/LocationMapPicker.vue';

const { toast } = useToast();
const page = usePage();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    listKategoriProposal?: Record<number, string> | any;
    listResident?: Array<{ id: number; label: string }> | any;
}>();

// Format Rupiah untuk display
const formatRupiah = (value: string | number): string => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    if (isNaN(num) || num === 0) return '';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(num);
};

// Form Data
const getInitialAnggaran = () => {
    if (props.initialData?.usulan_anggaran) {
        const num = typeof props.initialData.usulan_anggaran === 'string' 
            ? parseFloat(props.initialData.usulan_anggaran.replace(/[^\d]/g, ''))
            : props.initialData.usulan_anggaran;
        return num || '';
    }
    return '';
};

const formData = ref({
    kategori_proposal_id: props.initialData?.kategori_proposal_id ? String(props.initialData.kategori_proposal_id) : '',
    resident_id: props.initialData?.resident_id ? String(props.initialData.resident_id) : '',
    nomor_telepon_pengaju: props.initialData?.nomor_telepon_pengaju || '',
    nama_kegiatan: props.initialData?.nama_kegiatan || '',
    deskripsi_kegiatan: props.initialData?.deskripsi_kegiatan || '',
    usulan_anggaran: getInitialAnggaran(),
    file_pendukung: [] as File[],
    existing_files: props.initialData?.file_pendukung || [],
    latitude: props.initialData?.latitude || '',
    longitude: props.initialData?.longitude || '',
    nama_lokasi: props.initialData?.nama_lokasi || '',
    alamat: props.initialData?.alamat || '',
    thumbnail_foto_banner: null as File | null,
    existing_thumbnail: props.initialData?.thumbnail_foto_banner || null,
    tanda_tangan_digital: props.initialData?.tanda_tangan_digital || '',
});

// Map - menggunakan LocationMapPicker component
const isLoading = ref(false);

// Canvas untuk TTD Digital
const canvasRef = ref<HTMLCanvasElement | null>(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
const canvasWidth = 400;
const canvasHeight = 200;

// File uploads
const fileInputRef = ref<HTMLInputElement | null>(null);
const thumbnailInputRef = ref<HTMLInputElement | null>(null);
const filePreviews = ref<string[]>([]);
const thumbnailPreview = ref<string | null>(null);


// Initialize Canvas
const initCanvas = () => {
    if (canvasRef.value) {
        const canvas = canvasRef.value;
        canvas.width = canvasWidth;
        canvas.height = canvasHeight;
        
        const ctx = canvas.getContext('2d');
        if (ctx) {
            ctx.strokeStyle = '#111827';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseleave', stopDrawing);
        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);
        
        // Load existing TTD if in edit mode
        if (props.mode === 'edit' && props.initialData?.tanda_tangan_digital) {
            const img = new Image();
            img.onload = () => {
                if (ctx) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    hasDrawn.value = true;
                }
            };
            img.src = props.initialData.tanda_tangan_digital;
        }
        
        return true;
    }
    return false;
};

watch(() => canvasRef.value, () => {
    if (canvasRef.value) {
        nextTick(() => {
            const tryInit = (attempt = 1) => {
                if (initCanvas()) {
                    // Success
                } else {
                    if (attempt < 5) {
                        setTimeout(() => tryInit(attempt + 1), attempt * 100);
                    }
                }
            };
            tryInit();
        });
    }
}, { immediate: true });

onMounted(async () => {
    // Initialize component

    // Initialize canvas
    nextTick(() => {
        if (!initCanvas()) {
            setTimeout(() => {
                if (!initCanvas()) {
                    setTimeout(() => initCanvas(), 300);
                }
            }, 100);
        }
    });

    // Load existing files preview
    if (props.initialData?.file_pendukung && Array.isArray(props.initialData.file_pendukung)) {
        filePreviews.value = props.initialData.file_pendukung.map((file: string) => {
            return file.startsWith('http') ? file : `/storage/${file}`;
        });
    }

    // Load existing thumbnail preview
    if (props.initialData?.thumbnail_foto_banner) {
        thumbnailPreview.value = props.initialData.thumbnail_foto_banner.startsWith('http')
            ? props.initialData.thumbnail_foto_banner
            : `/storage/${props.initialData.thumbnail_foto_banner}`;
    }
});

onUnmounted(() => {
    if (canvasRef.value) {
        const canvas = canvasRef.value;
        canvas.removeEventListener('mousedown', startDrawing);
        canvas.removeEventListener('mousemove', draw);
        canvas.removeEventListener('mouseup', stopDrawing);
        canvas.removeEventListener('mouseleave', stopDrawing);
        canvas.removeEventListener('touchstart', startDrawing);
        canvas.removeEventListener('touchmove', draw);
        canvas.removeEventListener('touchend', stopDrawing);
    }
});

// Handle location selected from LocationMapPicker
const handleLocationSelected = (data: { lat: number; lng: number; address?: string }) => {
    if (data.address) {
        formData.value.alamat = data.address;
        // Extract road name for nama_lokasi
        const addressParts = data.address.split(',');
        formData.value.nama_lokasi = data.address;
    } else {
        formData.value.nama_lokasi = `Lokasi Kegiatan (${data.lat.toFixed(6)}, ${data.lng.toFixed(6)})`;
    }
    toast({ title: 'Lokasi berhasil diambil', variant: 'success' });
};

// Canvas functions
const getEventPos = (e: MouseEvent | TouchEvent) => {
    if (!canvasRef.value) return { x: 0, y: 0 };
    const rect = canvasRef.value.getBoundingClientRect();
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    
    const scaleX = canvasRef.value.width / rect.width;
    const scaleY = canvasRef.value.height / rect.height;
    
    const x = (clientX - rect.left) * scaleX;
    const y = (clientY - rect.top) * scaleY;
    
    return { x, y };
};

const startDrawing = (e: MouseEvent | TouchEvent) => {
    if (!canvasRef.value) return;
    e.preventDefault();
    e.stopPropagation();
    
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    isDrawing.value = true;
    hasDrawn.value = true;
    const pos = getEventPos(e);
    
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
};

const draw = (e: MouseEvent | TouchEvent) => {
    if (!isDrawing.value || !canvasRef.value) return;
    e.preventDefault();
    e.stopPropagation();
    
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    const pos = getEventPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
};

const stopDrawing = (e?: MouseEvent | TouchEvent) => {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    if (isDrawing.value && canvasRef.value) {
        const ctx = canvasRef.value.getContext('2d');
        if (ctx) {
            ctx.closePath();
        }
    }
    isDrawing.value = false;
};

const clearCanvas = () => {
    if (!canvasRef.value) return;
    const ctx = canvasRef.value.getContext('2d');
    if (!ctx) return;
    
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvasRef.value.width, canvasRef.value.height);
    ctx.strokeStyle = '#111827';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    hasDrawn.value = false;
    formData.value.tanda_tangan_digital = '';
};

const getCanvasData = (): string | null => {
    if (!canvasRef.value) return null;
    return canvasRef.value.toDataURL('image/png');
};

// File upload functions
const handleFileSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const files = Array.from(target.files);
        formData.value.file_pendukung = [...formData.value.file_pendukung, ...files];
        
        files.forEach((file) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                filePreviews.value.push(e.target?.result as string);
            };
            reader.readAsDataURL(file);
        });
    }
};

const removeFile = (index: number) => {
    formData.value.file_pendukung.splice(index, 1);
    filePreviews.value.splice(index, 1);
};

const removeExistingFile = (index: number) => {
    formData.value.existing_files.splice(index, 1);
};

const handleThumbnailSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        formData.value.thumbnail_foto_banner = target.files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
            thumbnailPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(target.files[0]);
    }
};

const removeThumbnail = () => {
    formData.value.thumbnail_foto_banner = null;
    thumbnailPreview.value = null;
    if (thumbnailInputRef.value) {
        thumbnailInputRef.value.value = '';
    }
};

// Validation
const canSubmit = computed(() => {
    if (!formData.value.kategori_proposal_id) return false;
    if (!formData.value.resident_id) return false;
    if (!formData.value.nama_kegiatan.trim()) return false;
    if (!formData.value.deskripsi_kegiatan.trim()) return false;
    if (!formData.value.usulan_anggaran || Number(formData.value.usulan_anggaran) <= 0) return false;
    if (!formData.value.latitude || !formData.value.longitude) return false;
    // Tanda tangan digital wajib untuk create, optional untuk edit
    if (props.mode === 'create') {
        if (!hasDrawn.value && !formData.value.tanda_tangan_digital) return false;
    }
    return true;
});

// Submit
const handleSubmit = async () => {
    if (!canSubmit.value) {
        toast({ title: 'Lengkapi semua field yang diperlukan', variant: 'destructive' });
        return;
    }

    try {
        const submitData = new FormData();
        submitData.append('kategori_proposal_id', formData.value.kategori_proposal_id);
        submitData.append('resident_id', formData.value.resident_id);
        submitData.append('nomor_telepon_pengaju', formData.value.nomor_telepon_pengaju);
        submitData.append('nama_kegiatan', formData.value.nama_kegiatan);
        submitData.append('deskripsi_kegiatan', formData.value.deskripsi_kegiatan);
        
        // Format anggaran (langsung dari number)
        const anggaran = formData.value.usulan_anggaran ? Number(formData.value.usulan_anggaran) : 0;
        submitData.append('usulan_anggaran', anggaran.toString());
        
        // File pendukung - new files
        formData.value.file_pendukung.forEach((file, index) => {
            submitData.append(`file_pendukung[${index}]`, file);
        });
        
        // Existing files (keep them) - send as array
        if (formData.value.existing_files.length > 0) {
            formData.value.existing_files.forEach((file, index) => {
                submitData.append(`existing_files[${index}]`, file);
            });
        }
        
        submitData.append('latitude', formData.value.latitude);
        submitData.append('longitude', formData.value.longitude);
        submitData.append('kecamatan_id', formData.value.kecamatan_id || '');
        submitData.append('desa_id', formData.value.desa_id || '');
        submitData.append('deskripsi_lokasi_tambahan', formData.value.deskripsi_lokasi_tambahan);
        
        // Thumbnail
        if (formData.value.thumbnail_foto_banner) {
            submitData.append('thumbnail_foto_banner', formData.value.thumbnail_foto_banner);
        }
        
        // Tanda tangan digital
        const canvasData = getCanvasData();
        if (canvasData) {
            submitData.append('tanda_tangan_digital', canvasData);
        } else if (formData.value.tanda_tangan_digital) {
            submitData.append('tanda_tangan_digital', formData.value.tanda_tangan_digital);
        }

        // Determine route based on current URL
        const currentUrl = window.location.pathname;
        const isPengajuanSaya = currentUrl.includes('pengajuan-proposal-saya');
        const baseRoute = isPengajuanSaya ? '/pengajuan-proposal-saya' : '/pengajuan-proposal';

        if (props.mode === 'edit' && props.initialData?.id) {
            submitData.append('id', String(props.initialData.id));
            submitData.append('_method', 'PUT');
            
            try {
                await axios.post(`${baseRoute}/${props.initialData.id}`, submitData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                
                toast({ title: 'Pengajuan proposal berhasil diupdate', variant: 'success' });
                router.visit(`${baseRoute}/${props.initialData.id}`);
            } catch (error: any) {
                console.error('Error:', error);
                toast({
                    title: error.response?.data?.message || 'Gagal mengupdate pengajuan proposal',
                    variant: 'destructive',
                });
            }
        } else {
            router.post(baseRoute, submitData, {
                forceFormData: true,
                onSuccess: () => {
                    toast({ title: 'Pengajuan proposal berhasil dibuat', variant: 'success' });
                },
                onError: (errors) => {
                    console.error('Error:', errors);
                    toast({
                        title: 'Gagal membuat pengajuan proposal',
                        variant: 'destructive',
                    });
                },
            });
        }
    } catch (error: any) {
        console.error('Error submitting form:', error);
        toast({ title: 'Gagal menyimpan pengajuan proposal', variant: 'destructive' });
    }
};

// Get options from props or page props
const kategoriOptions = computed(() => {
    if (props.listKategoriProposal) {
        if (typeof props.listKategoriProposal === 'object' && !Array.isArray(props.listKategoriProposal)) {
            return Object.entries(props.listKategoriProposal).map(([id, nama]) => ({
                value: id,
                label: nama,
            }));
        }
    }
    return [];
});

const residentOptions = computed(() => {
    if (props.listResident && Array.isArray(props.listResident)) {
        return props.listResident;
    }
    return [];
});
</script>

<template>
    <div class="space-y-6">
        <!-- Informasi Dasar -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Informasi Dasar</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <Label for="kategori_proposal_id">
                        Kategori Proposal <span class="text-destructive">*</span>
                    </Label>
                    <Select v-model="formData.kategori_proposal_id" required>
                        <SelectTrigger id="kategori_proposal_id">
                            <SelectValue placeholder="Pilih Kategori Proposal" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in kategoriOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label for="resident_id">
                        Data Pemohon <span class="text-destructive">*</span>
                    </Label>
                    <Select v-model="formData.resident_id" required>
                        <SelectTrigger id="resident_id">
                            <SelectValue placeholder="Pilih Pemohon" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in residentOptions"
                                :key="option.id"
                                :value="String(option.id)"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label for="nomor_telepon_pengaju">
                        Nomor Telepon Pengaju
                    </Label>
                    <Input
                        id="nomor_telepon_pengaju"
                        v-model="formData.nomor_telepon_pengaju"
                        type="text"
                        placeholder="Masukkan nomor telepon pengaju"
                    />
                </div>

                <div>
                    <Label for="nama_kegiatan">
                        Nama Kegiatan <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="nama_kegiatan"
                        v-model="formData.nama_kegiatan"
                        type="text"
                        placeholder="Masukkan nama kegiatan"
                        required
                    />
                </div>

                <div>
                    <Label for="deskripsi_kegiatan">
                        Deskripsi Kegiatan <span class="text-destructive">*</span>
                    </Label>
                    <Textarea
                        id="deskripsi_kegiatan"
                        v-model="formData.deskripsi_kegiatan"
                        placeholder="Masukkan deskripsi kegiatan"
                        rows="4"
                        required
                    />
                </div>

                <div>
                    <Label for="usulan_anggaran">
                        Usulan Anggaran (IDR) <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="usulan_anggaran"
                        v-model.number="formData.usulan_anggaran"
                        type="number"
                        min="0"
                        step="1"
                        placeholder="Masukkan usulan anggaran"
                        required
                    />
                    <p v-if="formData.usulan_anggaran" class="text-xs text-muted-foreground mt-1">
                        {{ formatRupiah(formData.usulan_anggaran) }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- File Pendukung -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">File Pendukung</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <Label>Upload File Pendukung</Label>
                    <div class="mt-2">
                        <input
                            ref="fileInputRef"
                            type="file"
                            multiple
                            accept=".pdf,.doc,.docx,.xls,.xlsx"
                            @change="handleFileSelect"
                            class="hidden"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            @click="fileInputRef?.click()"
                            class="w-full"
                        >
                            <Upload class="w-4 h-4 mr-2" />
                            Pilih File
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Format: PDF, DOC, DOCX, XLS, XLSX (Max 10MB per file)
                    </p>
                </div>

                <!-- Existing Files -->
                <div v-if="formData.existing_files.length > 0" class="space-y-2">
                    <Label>File yang Sudah Ada:</Label>
                    <div
                        v-for="(file, index) in formData.existing_files"
                        :key="index"
                        class="flex items-center justify-between p-2 border rounded"
                    >
                        <div class="flex items-center gap-2">
                            <FileText class="w-4 h-4" />
                            <a
                                :href="`/storage/${file}`"
                                target="_blank"
                                class="text-sm text-primary hover:underline"
                            >
                                {{ file.split('/').pop() }}
                            </a>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removeExistingFile(index)"
                        >
                            <X class="w-4 h-4" />
                        </Button>
                    </div>
                </div>

                <!-- New Files -->
                <div v-if="formData.file_pendukung.length > 0" class="space-y-2">
                    <Label>File Baru:</Label>
                    <div
                        v-for="(file, index) in formData.file_pendukung"
                        :key="index"
                        class="flex items-center justify-between p-2 border rounded"
                    >
                        <div class="flex items-center gap-2">
                            <FileText class="w-4 h-4" />
                            <span class="text-sm">{{ file.name }}</span>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removeFile(index)"
                        >
                            <X class="w-4 h-4" />
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Peta Lokasi -->
        <LocationMapPicker
            v-model:latitude="formData.latitude"
            v-model:longitude="formData.longitude"
            marker-popup-text="Lokasi Kegiatan"
            @location-selected="handleLocationSelected"
        />

        <!-- Informasi Lokasi -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Informasi Lokasi</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <Label for="nama_lokasi">
                        Nama Lokasi <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="nama_lokasi"
                        v-model="formData.nama_lokasi"
                        type="text"
                        placeholder="Nama lokasi akan terisi otomatis"
                        required
                    />
                </div>

                <div>
                    <Label for="alamat">Alamat</Label>
                    <Textarea
                        id="alamat"
                        v-model="formData.alamat"
                        placeholder="Alamat akan terisi otomatis"
                        rows="3"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Thumbnail/Foto Banner -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Thumbnail/Foto Banner</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <Label>Upload Thumbnail/Foto Banner</Label>
                    <div class="mt-2">
                        <input
                            ref="thumbnailInputRef"
                            type="file"
                            accept="image/*"
                            @change="handleThumbnailSelect"
                            class="hidden"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            @click="thumbnailInputRef?.click()"
                            class="w-full"
                        >
                            <Upload class="w-4 h-4 mr-2" />
                            Pilih Foto
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Format: JPG, PNG (Max 5MB)
                    </p>
                </div>

                <div v-if="thumbnailPreview" class="relative">
                    <img
                        :src="thumbnailPreview"
                        alt="Thumbnail preview"
                        class="w-full max-w-md h-auto rounded-lg border"
                    />
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="absolute top-2 right-2"
                        @click="removeThumbnail"
                    >
                        <X class="w-4 h-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Tanda Tangan Digital -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Verifikasi Dokumen - Tanda Tangan Digital</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <Label>
                        Tanda Tangan Digital <span class="text-destructive">*</span>
                    </Label>
                    <p class="text-xs text-muted-foreground mb-2">
                        Silakan tanda tangan di canvas di bawah ini
                    </p>
                    <div class="border rounded overflow-hidden bg-white" style="width: 100%; max-width: 600px;">
                        <canvas
                            ref="canvasRef"
                            class="w-full cursor-crosshair"
                            style="touch-action: none; display: block; max-height: 300px;"
                        ></canvas>
                    </div>
                    <div class="mt-2 flex gap-2">
                        <Button type="button" variant="outline" size="sm" @click="clearCanvas">
                            Hapus Tanda Tangan
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex gap-4 justify-end">
            <Button
                type="button"
                @click="router.visit(mode === 'create' ? (window.location.pathname.includes('pengajuan-proposal-saya') ? '/pengajuan-proposal-saya' : '/pengajuan-proposal') : (window.location.pathname.includes('pengajuan-proposal-saya') ? `/pengajuan-proposal-saya/${props.initialData?.id}` : `/pengajuan-proposal/${props.initialData?.id}`))"
                variant="outline"
            >
                Batal
            </Button>
            <Button
                type="button"
                @click="handleSubmit"
                :disabled="!canSubmit || isLoading"
                variant="default"
            >
                {{ isLoading ? 'Menyimpan...' : 'Simpan' }}
            </Button>
        </div>
    </div>
</template>

