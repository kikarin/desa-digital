<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed, ref, watch } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    listFamily?: { value: number; label: string }[];
    listStatus?: { value: number; label: string }[];
}>();

const selectedStatusId = ref<number | null>(props.initialData?.status_id || null);

watch(() => props.initialData, (newVal) => {
    if (newVal?.status_id) {
        selectedStatusId.value = newVal.status_id;
    }
}, { immediate: true, deep: true });

const selectedStatus = computed(() => {
    if (!selectedStatusId.value || !props.listStatus) return null;
    return props.listStatus.find(s => s.value === selectedStatusId.value);
});

const isStatusPindah = computed(() => {
    return selectedStatus.value?.label?.toUpperCase() === 'PINDAH';
});

const isStatusMeninggal = computed(() => {
    return selectedStatus.value?.label?.toUpperCase() === 'MENINGGAL';
});

const formInputs = computed(() => {
    const baseInputs = [
        {
            name: 'family_id',
            label: 'Kartu Keluarga',
            type: 'select' as const,
            placeholder: 'Pilih Kartu Keluarga',
            required: true,
            options: props.listFamily || [],
        },
        {
            name: 'nik',
            label: 'NIK',
            type: 'text' as const,
            placeholder: 'Masukkan NIK (16 digit)',
            required: true,
            help: 'NIK harus 16 digit angka',
            maxlength: 16,
            pattern: '[0-9]*',
        },
        {
            name: 'nama',
            label: 'Nama',
            type: 'text' as const,
            placeholder: 'Masukkan nama lengkap',
            required: true,
        },
        {
            name: 'tempat_lahir',
            label: 'Tempat Lahir',
            type: 'text' as const,
            placeholder: 'Masukkan tempat lahir',
            required: true,
        },
        {
            name: 'tanggal_lahir',
            label: 'Tanggal Lahir',
            type: 'date' as const,
            placeholder: 'Pilih tanggal lahir',
            required: true,
        },
        {
            name: 'jenis_kelamin',
            label: 'Jenis Kelamin',
            type: 'select' as const,
            placeholder: 'Pilih jenis kelamin',
            required: true,
            options: [
                { value: 'L', label: 'Laki-laki' },
                { value: 'P', label: 'Perempuan' },
            ],
        },
        {
            name: 'status_id',
            label: 'Status',
            type: 'select' as const,
            placeholder: 'Pilih status',
            required: true,
            options: props.listStatus || [],
        },
        {
            name: 'status_note',
            label: 'Status Note',
            type: 'textarea' as const,
            placeholder: 'Keterangan status (opsional)',
            required: false,
        },
    ];

        if (isStatusPindah.value) {
            baseInputs.push(
                {
                    name: 'jenis_pindah',
                    label: 'Jenis Pindah',
                    type: 'select' as const,
                    placeholder: 'Pilih jenis pindah',
                    required: false,
                    options: [
                        { value: 'INDIVIDU', label: 'INDIVIDU' },
                        { value: 'KELUARGA', label: 'KELUARGA' },
                    ],
                },
                {
                    name: 'alamat_tujuan',
                    label: 'Alamat Tujuan',
                    type: 'textarea' as const,
                    placeholder: 'Masukkan alamat tujuan (opsional)',
                    required: false,
                },
                {
                    name: 'desa',
                    label: 'Desa',
                    type: 'text' as const,
                    placeholder: 'Masukkan desa (opsional)',
                    required: false,
                },
                {
                    name: 'kecamatan',
                    label: 'Kecamatan',
                    type: 'text' as const,
                    placeholder: 'Masukkan kecamatan (opsional)',
                    required: false,
                },
                {
                    name: 'kabupaten',
                    label: 'Kabupaten',
                    type: 'text' as const,
                    placeholder: 'Masukkan kabupaten (opsional)',
                    required: false,
                },
                {
                    name: 'tanggal_pindah',
                    label: 'Tanggal Pindah',
                    type: 'date' as const,
                    placeholder: 'Pilih tanggal pindah (opsional)',
                    required: false,
                }
            );
        }

    if (isStatusMeninggal.value) {
        baseInputs.push(
            {
                name: 'tanggal_meninggal',
                label: 'Tanggal Meninggal',
                type: 'date' as const,
                placeholder: 'Pilih tanggal meninggal',
                required: true,
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

    return baseInputs;
});

const handleFieldUpdated = (field: { field: string; value: any }) => {
    if (field.field === 'status_id') {
        selectedStatusId.value = field.value;
    }
};

const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
        family_id: Number(data.family_id),
        status_id: Number(data.status_id),
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/data-warga/residents',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/data-warga/residents',
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

