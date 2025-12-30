<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const formInputs = [
    {
        name: 'nama',
        label: 'Nama',
        type: 'text' as const,
        placeholder: 'Masukkan nama kategori aduan',
        required: true,
        help: 'Masukkan nama kategori aduan, contoh: Fasilitas Umum, Kebersihan & Lingkungan',
    },
];

const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/data-master/kategori-aduan',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/data-master/kategori-aduan',
        successMessage: props.mode === 'create' ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
        errorMessage: 'Gagal menyimpan data',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
</template>

