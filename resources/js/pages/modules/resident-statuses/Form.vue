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
        name: 'code',
        label: 'Code',
        type: 'text' as const,
        placeholder: 'AKTIF, PINDAH, MENINGGAL',
        required: true,
        help: 'Masukkan code status, contoh: AKTIF, PINDAH, MENINGGAL',
    },
    {
        name: 'name',
        label: 'Name',
        type: 'text' as const,
        placeholder: 'Aktif, Pindah, Meninggal',
        required: true,
        help: 'Masukkan nama status, contoh: Aktif, Pindah, Meninggal',
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
        url: '/data-master/resident-statuses',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/data-master/resident-statuses',
        successMessage: props.mode === 'create' ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
        errorMessage: 'Gagal menyimpan data',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
</template>

