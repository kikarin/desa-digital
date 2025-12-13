<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    listHouse?: { value: number; label: string }[];
}>();

const formInputs = computed(() => [
    {
        name: 'house_id',
        label: 'Rumah',
        type: 'select' as const,
        placeholder: 'Pilih Rumah',
        required: true,
        options: props.listHouse || [],
    },
    {
        name: 'no_kk',
        label: 'No. KK',
        type: 'text' as const,
        placeholder: 'Masukkan nomor kartu keluarga (16 digit)',
        required: true,
        help: 'Nomor KK harus 16 digit angka',
        maxlength: 16,
        pattern: '[0-9]*',
    },
    {
        name: 'status',
        label: 'Status',
        type: 'select' as const,
        placeholder: 'Pilih status',
        required: true,
        options: [
            { value: 'AKTIF', label: 'AKTIF' },
            { value: 'NON_AKTIF', label: 'NON_AKTIF' },
        ],
    },
]);

const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
        house_id: Number(data.house_id),
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/data-warga/families',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/data-warga/families',
        successMessage: props.mode === 'create' ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
        errorMessage: 'Gagal menyimpan data',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
</template>

