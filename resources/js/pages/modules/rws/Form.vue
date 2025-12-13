<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

// Form inputs configuration
const formInputs = [
    {
        name: 'nomor_rw',
        label: 'Nomor RW',
        type: 'text' as const,
        placeholder: '01',
        required: true,
        help: 'Masukkan nomor RW, contoh: 01',
    },
    {
        name: 'desa',
        label: 'Desa',
        type: 'text' as const,
        placeholder: 'Contoh: Galuga',
        required: true,
    },
    {
        name: 'kecamatan',
        label: 'Kecamatan',
        type: 'text' as const,
        placeholder: 'Contoh: Cibungbulang',
        required: true,
    },
    {
        name: 'kabupaten',
        label: 'Kabupaten',
        type: 'text' as const,
        placeholder: 'Contoh: Bogor',
        required: true,
    },
];

// Handle save form
const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/data-warga/rws',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/data-warga/rws',
        successMessage: props.mode === 'create' ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
        errorMessage: 'Gagal menyimpan data',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
</template>

