<script setup lang="ts">
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';

const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const formInputs = [
    {
        name: 'tipe',
        label: 'Tipe',
        type: 'select' as const,
        placeholder: 'Pilih tipe',
        required: true,
        options: [
            { value: 'berita', label: 'Berita' },
            { value: 'event', label: 'Event' },
        ],
    },
    {
        name: 'title',
        label: 'Title',
        type: 'text' as const,
        placeholder: 'Masukkan title',
        required: true,
    },
    {
        name: 'foto',
        label: 'Foto',
        type: 'file' as const,
        placeholder: 'Pilih foto',
        required: false,
        help: 'Format: JPG, PNG, Max 2MB',
    },
    {
        name: 'tanggal',
        label: 'Tanggal',
        type: 'date' as const,
        placeholder: 'Pilih tanggal',
        required: true,
    },
    {
        name: 'deskripsi',
        label: 'Deskripsi',
        type: 'textarea' as const,
        placeholder: 'Masukkan deskripsi',
        required: false,
    },
];

const handleSave = (data: Record<string, any>) => {
    const formData = new FormData();
    
    formData.append('tipe', data.tipe || 'berita');
    formData.append('title', data.title || '');
    formData.append('tanggal', data.tanggal || '');
    formData.append('deskripsi', data.deskripsi || '');
    
    // Handle file upload - jika ada file baru, kirim. Jika tidak ada file baru dan edit mode, keep existing
    if (data.foto && data.foto instanceof File) {
        formData.append('foto', data.foto);
    } else if (props.mode === 'edit' && !data.foto) {
        // Jika edit mode dan tidak ada file baru, tidak kirim foto (repository akan keep existing)
        // Jangan append foto ke FormData
    } else if (props.mode === 'edit' && data.foto === null) {
        // Explicitly set to null jika user ingin hapus foto
        formData.append('foto', '');
    }
    
    if (props.mode === 'edit' && props.initialData?.id) {
        formData.append('id', String(props.initialData.id));
        formData.append('_method', 'PUT');
        
        router.post(`/berita-pengumuman/${props.initialData.id}`, formData, {
            forceFormData: true,
            onSuccess: () => {
                toast({
                    title: 'Data berhasil diperbarui',
                    variant: 'success',
                });
                router.visit(`/berita-pengumuman/${props.initialData?.id}`);
            },
            onError: (errors) => {
                toast({
                    title: 'Gagal memperbarui data',
                    variant: 'destructive',
                });
            },
        });
    } else {
        router.post('/berita-pengumuman', formData, {
            forceFormData: true,
            onSuccess: () => {
                toast({
                    title: 'Data berhasil ditambahkan',
                    variant: 'success',
                });
                router.visit('/berita-pengumuman');
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
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
</template>

