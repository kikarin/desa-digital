<script setup lang="ts">
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import { ref, watch } from 'vue';
import { Ckeditor } from '@ckeditor/ckeditor5-vue';
// @ts-ignore
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { Label } from '@/components/ui/label';

const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const editor = ClassicEditor;
const editorConfig = {
    toolbar: [
        'heading', '|',
        'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
        'outdent', 'indent', '|',
        'blockQuote', 'insertTable', '|',
        'undo', 'redo'
    ],
    placeholder: 'Masukkan deskripsi berita/pengumuman...',
};

const deskripsi = ref<string>(props.initialData?.deskripsi || '');

watch(() => props.initialData?.deskripsi, (newValue) => {
    if (newValue !== undefined) {
        deskripsi.value = newValue || '';
    }
}, { immediate: true });

const formInputs = [
    {
        name: 'tipe',
        label: 'Tipe',
        type: 'select' as const,
        placeholder: 'Pilih tipe',
        required: true,
        options: [
            { value: 'berita', label: 'Berita' },
            { value: 'event', label: 'Pengumuman' },
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
];

const handleSave = (data: Record<string, any>) => {
    const formData = new FormData();
    
    formData.append('tipe', data.tipe || 'berita');
    formData.append('title', data.title || '');
    formData.append('tanggal', data.tanggal || '');
    formData.append('deskripsi', deskripsi.value || '');
    
    if (data.foto && data.foto instanceof File) {
        formData.append('foto', data.foto);
    } else if (props.mode === 'edit' && !data.foto) {
    } else if (props.mode === 'edit' && data.foto === null) {
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
    <div class="space-y-1">
        <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
        
        <div>
            <Label for="deskripsi" class="block text-sm font-medium mb-2">
                Deskripsi
            </Label>
            <div class="border border-input rounded-md">
                <Ckeditor
                    :editor="editor"
                    v-model="deskripsi"
                    :config="editorConfig"
                />
            </div>
            <p class="text-xs text-muted-foreground mt-1">
                Gunakan editor untuk memformat teks deskripsi
            </p>
        </div>
    </div>
</template>

<style>
.ck-editor__editable_inline {
    min-height: 100px;
}
</style>
