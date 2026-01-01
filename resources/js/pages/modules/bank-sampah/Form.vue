<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ref } from 'vue';
import { Search, MapPin, Navigation } from 'lucide-vue-next';
import LocationMapPicker from '@/components/LocationMapPicker.vue';

const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

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
const fotoFile = ref<File | null>(null);
const fotoPreview = ref<string | null>(props.initialData?.foto || null);

// Handle location selected from LocationMapPicker
const handleLocationSelected = (data: { lat: number; lng: number; address?: string }) => {
    if (data.address) {
        formData.value.alamat = data.address;
        // Extract road name for title and nama_lokasi
        const addressParts = data.address.split(',');
        formData.value.title = addressParts[0] || 'Bank Sampah';
        formData.value.nama_lokasi = data.address;
    } else {
        formData.value.nama_lokasi = `Lokasi Bank Sampah (${data.lat.toFixed(6)}, ${data.lng.toFixed(6)})`;
        formData.value.title = 'Bank Sampah';
    }
    toast({
        title: 'Lokasi berhasil diambil',
        variant: 'success',
    });
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
        <LocationMapPicker
            v-model:latitude="formData.latitude"
            v-model:longitude="formData.longitude"
            marker-popup-text="Lokasi Bank Sampah"
            @location-selected="handleLocationSelected"
        />

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

