<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    listRt?: { value: number; label: string }[];
    listResidents?: { value: number; label: string }[];
}>();

const rtOptions = computed(() => props.listRt || []);
const residentsOptions = computed(() => props.listResidents || []);

const getInitialFormData = () => {
    const data: Record<string, any> = {
        jenis_rumah: props.initialData?.jenis_rumah || null,
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

const formInputs = computed(() => {
    const baseInputs: Array<{
        name: string;
        label: string;
        type: 'text' | 'email' | 'password' | 'textarea' | 'select' | 'multi-select' | 'number' | 'radio' | 'icon' | 'checkbox' | 'date' | 'select-or-text';
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

const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
        rt_id: Number(data.rt_id),
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

    save(formData, {
        url: '/data-warga/houses',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/data-warga/houses',
        successMessage: props.mode === 'create' ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
        errorMessage: 'Gagal menyimpan data',
    });
};
</script>

<template>
    <FormInput
        :form-inputs="formInputs"
        :initial-data="initialData"
        @save="handleSave"
        @field-updated="handleFieldUpdated"
    />
</template>

