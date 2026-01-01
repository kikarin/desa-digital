<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import LocationMapPicker from '@/components/LocationMapPicker.vue';

const { save } = useHandleFormSave();
const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    listRt?: { value: number; label: string }[];
    listResidents?: { value: number; label: string }[];
}>();

const rtOptions = computed(() => props.listRt || []);
const residentsOptions = computed(() => props.listResidents || []);

// Computed initialData yang menggabungkan props.initialData dengan form data (untuk rt_id, latitude, longitude)
const computedInitialData = computed(() => {
    return {
        ...props.initialData,
        rt_id: form.rt_id || props.initialData?.rt_id ? String(form.rt_id || props.initialData?.rt_id) : null,
        latitude: form.latitude || props.initialData?.latitude || null,
        longitude: form.longitude || props.initialData?.longitude || null,
    };
});

const getInitialFormData = () => {
    const data: Record<string, any> = {
        jenis_rumah: props.initialData?.jenis_rumah || null,
        deleted_media_ids: [], // Initialize deleted_media_ids
        latitude: props.initialData?.latitude || null,
        longitude: props.initialData?.longitude || null,
        rt_id: props.initialData?.rt_id ? String(props.initialData.rt_id) : null,
    };
    
    if (props.initialData) {
        if (props.initialData.pemilik_id) {
            data.pemilik_id = props.initialData.pemilik_id;
            data.pemilik = null;
        } else if (props.initialData.nama_pemilik) {
            data.pemilik_id = null;
            data.pemilik = props.initialData.nama_pemilik;
        }
        
        if (props.initialData.nama_pengelola) {
            data.pengelola_id = null;
            data.pengelola = props.initialData.nama_pengelola;
        }
        
        // Include fotos dari initialData
        if (props.initialData.fotos) {
            data.fotos = props.initialData.fotos;
        }
    }
    
    return data;
};

const form = useForm(getInitialFormData());

const selectedJenisRumah = computed({
    get: () => form.jenis_rumah,
    set: (value) => {
        form.jenis_rumah = value;
    },
});

watch(() => props.initialData?.jenis_rumah, (newVal) => {
    if (newVal) {
        form.jenis_rumah = newVal;
    }
}, { immediate: true });

watch(() => props.initialData?.rt_id, (newVal) => {
    if (newVal) {
        form.rt_id = String(newVal);
    }
}, { immediate: true });

watch(() => props.initialData?.latitude, (newVal) => {
    if (newVal !== undefined) {
        form.latitude = newVal;
    }
}, { immediate: true });

watch(() => props.initialData?.longitude, (newVal) => {
    if (newVal !== undefined) {
        form.longitude = newVal;
    }
}, { immediate: true });

const formInputs = computed(() => {
    const baseInputs: Array<{
        name: string;
        label: string;
        type: 'text' | 'email' | 'password' | 'textarea' | 'select' | 'multi-select' | 'number' | 'radio' | 'icon' | 'checkbox' | 'date' | 'select-or-text' | 'multiple-file';
        placeholder?: string;
        required?: boolean;
        options?: { value: string | number; label: string }[];
    }> = [
        {
            name: 'jenis_rumah',
            label: 'Jenis Rumah',
            type: 'select' as const,
            placeholder: 'Pilih jenis rumah',
            required: true,
            options: [
                { value: 'RUMAH_TINGGAL', label: 'RUMAH TINGGAL' },
                { value: 'KONTRAKAN', label: 'KONTRAKAN' },
                { value: 'WARUNG_TOKO_USAHA', label: 'WARUNG / TOKO / USAHA' },
                { value: 'FASILITAS_UMUM', label: 'FASILITAS UMUM' },
            ],
        },
    ];

    if (!selectedJenisRumah.value) {
        return baseInputs;
    }

    // Field foto untuk semua jenis rumah
    const fotoField = {
        name: 'fotos',
        label: 'Foto',
        type: 'multiple-file' as const,
        placeholder: 'Pilih foto (bisa lebih dari 1)',
        required: false,
    };

    if (selectedJenisRumah.value === 'RUMAH_TINGGAL') {
        baseInputs.push(
            {
                name: 'rt_id',
                label: 'RT',
                type: 'select' as const,
                placeholder: 'Pilih RT',
                required: true,
                options: rtOptions.value.map(opt => ({ value: String(opt.value), label: opt.label })),
            },
            {
                name: 'nomor_rumah',
                label: 'Nomor Rumah',
                type: 'text' as const,
                placeholder: 'Masukkan nomor rumah',
                required: true,
            },
            {
                name: 'pemilik',
                label: 'Pemilik',
                type: 'select-or-text' as const,
                placeholder: 'Pilih pemilik atau ketik manual (opsional)',
                required: false,
                options: residentsOptions.value.map(opt => ({ value: opt.value, label: opt.label })),
            },
            fotoField,
            {
                name: 'keterangan',
                label: 'Keterangan',
                type: 'textarea' as const,
                placeholder: 'Keterangan (opsional)',
                required: false,
            }
        );
    }

    if (selectedJenisRumah.value === 'KONTRAKAN') {
        baseInputs.push(
            {
                name: 'rt_id',
                label: 'RT',
                type: 'select' as const,
                placeholder: 'Pilih RT',
                required: true,
                options: rtOptions.value.map(opt => ({ value: String(opt.value), label: opt.label })),
            },
            {
                name: 'nomor_rumah',
                label: 'Nomor Bangunan',
                type: 'text' as const,
                placeholder: 'Masukkan nomor bangunan',
                required: true,
            },
            {
                name: 'pemilik',
                label: 'Pemilik',
                type: 'select-or-text' as const,
                placeholder: 'Pilih pemilik atau ketik manual (opsional)',
                required: false,
                options: residentsOptions.value.map(opt => ({ value: opt.value, label: opt.label })),
            },
            {
                name: 'status_hunian',
                label: 'Status Hunian',
                type: 'select' as const,
                placeholder: 'Pilih status hunian',
                required: true,
                options: [
                    { value: 'DIHUNI', label: 'Dihuni' },
                    { value: 'KOSONG', label: 'Kosong' },
                ],
            },
            fotoField,
            {
                name: 'keterangan',
                label: 'Keterangan',
                type: 'textarea' as const,
                placeholder: 'Keterangan (opsional)',
                required: false,
            }
        );
    }

    if (selectedJenisRumah.value === 'WARUNG_TOKO_USAHA') {
        baseInputs.push(
            {
                name: 'rt_id',
                label: 'RT',
                type: 'select' as const,
                placeholder: 'Pilih RT',
                required: true,
                options: rtOptions.value.map(opt => ({ value: String(opt.value), label: opt.label })),
            },
            {
                name: 'nomor_rumah',
                label: 'Nomor Bangunan',
                type: 'text' as const,
                placeholder: 'Masukkan nomor bangunan',
                required: true,
            },
            {
                name: 'nama_usaha',
                label: 'Nama Usaha',
                type: 'text' as const,
                placeholder: 'Masukkan nama usaha',
                required: true,
            },
            {
                name: 'pengelola',
                label: 'Nama Pemilik / Pengelola',
                type: 'select-or-text' as const,
                placeholder: 'Pilih pemilik atau ketik manual (opsional)',
                required: false,
                options: residentsOptions.value.map(opt => ({ value: opt.value, label: opt.label })),
            },
            {
                name: 'jenis_usaha',
                label: 'Jenis Usaha',
                type: 'text' as const,
                placeholder: 'Jenis usaha (opsional)',
                required: false,
            },
            fotoField,
            {
                name: 'keterangan',
                label: 'Keterangan',
                type: 'textarea' as const,
                placeholder: 'Keterangan',
                required: false,
            }
        );
    }

    if (selectedJenisRumah.value === 'FASILITAS_UMUM') {
        baseInputs.push(
            {
                name: 'rt_id',
                label: 'RT',
                type: 'select' as const,
                placeholder: 'Pilih RT',
                required: true,
                options: rtOptions.value.map(opt => ({ value: String(opt.value), label: opt.label })),
            },
            {
                name: 'nomor_rumah',
                label: 'Nomor Bangunan',
                type: 'text' as const,
                placeholder: 'Nomor bangunan (boleh kosong)',
                required: false,
            },
            {
                name: 'nama_fasilitas',
                label: 'Nama Fasilitas',
                type: 'text' as const,
                placeholder: 'Masukkan nama fasilitas',
                required: true,
            },
            {
                name: 'pengelola',
                label: 'Pengelola',
                type: 'select' as const,
                placeholder: 'Pilih pengelola',
                required: true,
                options: [
                    { value: 'DESA', label: 'Desa' },
                    { value: 'RT', label: 'RT' },
                    { value: 'DINAS', label: 'Dinas' },
                ],
            },
            fotoField,
            {
                name: 'keterangan',
                label: 'Keterangan',
                type: 'textarea' as const,
                placeholder: 'Keterangan',
                required: false,
            }
        );
    }

    return baseInputs;
});

const handleFieldUpdated = (field: { field: string; value: any }) => {
    if (field.field === 'jenis_rumah') {
        selectedJenisRumah.value = field.value;
    }
};

const getMarkerPopupText = () => {
    if (!form.jenis_rumah) return 'Lokasi Rumah';
    
    const labels: Record<string, string> = {
        'RUMAH_TINGGAL': 'Rumah Tinggal',
        'KONTRAKAN': 'Kontrakan',
        'WARUNG_TOKO_USAHA': 'Warung / Toko / Usaha',
        'FASILITAS_UMUM': 'Fasilitas Umum',
    };
    
    return labels[form.jenis_rumah] || 'Lokasi Rumah';
};

const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
        // Pastikan rt_id diambil dari data atau form, dan convert ke number
        rt_id: data.rt_id ? Number(data.rt_id) : (form.rt_id ? Number(form.rt_id) : null),
        // Include latitude dan longitude dari form (karena di-update via v-model di LocationMapPicker)
        latitude: form.latitude || null,
        longitude: form.longitude || null,
    };

    if (selectedJenisRumah.value === 'RUMAH_TINGGAL') {
        if (data.pemilik_id) {
            formData.pemilik_id = Number(data.pemilik_id);
            formData.nama_pemilik = null;
        } else if (data.pemilik) {
            formData.pemilik_id = null;
            formData.nama_pemilik = data.pemilik;
        } else {
            formData.pemilik_id = null;
            formData.nama_pemilik = null;
        }
        delete formData.pemilik;
    }

    if (selectedJenisRumah.value === 'KONTRAKAN') {
        if (data.pemilik_id) {
            const selectedResident = residentsOptions.value.find((r: any) => r.value === Number(data.pemilik_id));
            formData.nama_pemilik = selectedResident?.label || data.pemilik || null;
        } else if (data.pemilik) {
            formData.nama_pemilik = data.pemilik;
        } else {
            formData.nama_pemilik = null;
        }
        delete formData.pemilik;
        delete formData.pemilik_id;
    }

    if (selectedJenisRumah.value === 'WARUNG_TOKO_USAHA') {
        if (data.pengelola_id) {
            const selectedResident = residentsOptions.value.find((r: any) => r.value === Number(data.pengelola_id));
            formData.nama_pengelola = selectedResident?.label || data.pengelola || null;
        } else if (data.pengelola) {
            formData.nama_pengelola = data.pengelola;
        } else {
            formData.nama_pengelola = null;
        }
        delete formData.pengelola;
        delete formData.pengelola_id;
    }

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    // Selalu gunakan FormData untuk memastikan file dan deleted_media_ids ter-handle dengan benar
    const formDataToSend = new FormData();
    
    Object.keys(formData).forEach((key) => {
        if (key === 'fotos' && Array.isArray(formData[key]) && formData[key].length > 0) {
            formData[key].forEach((file: File) => {
                // Pastikan ini adalah File object, bukan string atau object lain
                if (file instanceof File) {
                    formDataToSend.append('fotos[]', file);
                }
            });
        } else if (key === 'deleted_media_ids' && Array.isArray(formData[key]) && formData[key].length > 0) {
            formData[key].forEach((id: number) => {
                if (id !== null && id !== undefined) {
                    formDataToSend.append('deleted_media_ids[]', id.toString());
                }
            });
        } else if (formData[key] !== null && formData[key] !== undefined && key !== 'fotos' && key !== 'deleted_media_ids') {
            // Convert value to string untuk FormData
            const value = formData[key];
            if (typeof value === 'object' && !(value instanceof File)) {
                formDataToSend.append(key, JSON.stringify(value));
            } else {
                formDataToSend.append(key, value);
            }
        }
    });

    // Gunakan router.post dengan FormData
    if (props.mode === 'create') {
        router.post('/data-warga/houses', formDataToSend, {
            forceFormData: true,
            onSuccess: () => {
                toast({ title: 'Data berhasil ditambahkan', variant: 'success' });
                router.visit('/data-warga/houses');
            },
            onError: (errors) => {
                console.error('Error:', errors);
                toast({ title: 'Gagal menyimpan data', variant: 'destructive' });
            },
        });
    } else {
        formDataToSend.append('_method', 'PUT');
        router.post(`/data-warga/houses/${props.initialData?.id}`, formDataToSend, {
            forceFormData: true,
            onSuccess: () => {
                toast({ title: 'Data berhasil diperbarui', variant: 'success' });
                router.visit('/data-warga/houses');
            },
            onError: (errors) => {
                console.error('Error:', errors);
                toast({ title: 'Gagal menyimpan data', variant: 'destructive' });
            },
        });
    }
};
</script>

<template>
    <div class="space-y-6">
        <FormInput
            :form-inputs="formInputs"
            :initial-data="computedInitialData"
            @save="handleSave"
            @field-updated="handleFieldUpdated"
        />
        
        <!-- Location Map Picker -->
        <LocationMapPicker
            v-if="form.jenis_rumah"
            v-model:latitude="form.latitude"
            v-model:longitude="form.longitude"
            :jenis-rumah="form.jenis_rumah"
            :marker-popup-text="getMarkerPopupText()"
        />
    </div>
</template>

