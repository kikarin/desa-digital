<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref } from 'vue';
import { Search, MapPin, Navigation } from 'lucide-vue-next';
import LocationMapPicker from '@/components/LocationMapPicker.vue';

const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const formData = ref({
    kategori: props.initialData?.kategori || '',
    latitude: props.initialData?.latitude || '',
    longitude: props.initialData?.longitude || '',
    title: props.initialData?.title || '',
    alamat: props.initialData?.alamat || '',
    nomor_whatsapp: props.initialData?.nomor_whatsapp || '',
});

const isLoading = ref(false);

// Handle location selected from LocationMapPicker
const handleLocationSelected = (data: { lat: number; lng: number; address?: string }) => {
    if (data.address) {
        formData.value.alamat = data.address;
        // Extract road name for title
        const addressParts = data.address.split(',');
        formData.value.title = addressParts[0] || 'Layanan Darurat';
    }
    toast({ title: 'Lokasi berhasil diambil', variant: 'success' });
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
        <LocationMapPicker
            v-model:latitude="formData.latitude"
            v-model:longitude="formData.longitude"
            marker-popup-text="Lokasi Layanan Darurat"
            @location-selected="handleLocationSelected"
        />

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

