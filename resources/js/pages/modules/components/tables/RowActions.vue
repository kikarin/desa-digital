<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    Activity,
    Bolt,
    CircleCheckBig,
    Edit,
    Eye,
    FileText,
    FolderKanban,
    MoreVertical,
    Trash2,
    X,
    PictureInPicture,
    FormInput,
    Notebook,
    LogIn,
    ShieldCheck,
    UserCheck,
    Package,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    id: string | number;
    baseUrl: string;
    actions?: { label: string; onClick: () => void; permission?: boolean }[];
    show?: boolean;
    edit?: boolean;
    delete?: boolean;
    onDelete?: () => void;
}>();

// Function to get icon based on label
const getIconByLabel = (label: string) => {
    switch (label) {
        case 'Detail':
            return Eye;
        case 'Edit':
            return Edit;
        case 'Delete':
            return Trash2;
        case 'Set Permissions':
            return Bolt;
        case 'Login As':
            return LogIn;
        case 'Set Kepala Keluarga':
            return UserCheck;
        case 'Lihat':
            return FolderKanban;
        case 'Riwayat Pemeriksaan':
            return Activity;
        case 'Setujui':
            return CircleCheckBig;
        case 'Tolak':
            return X;
        case 'Setup':
            return PictureInPicture;
        case 'Rekap Absen':
            return Notebook;
        case 'Penyaluran':
            return Package;
default:
            return FileText;
    }
};

const items = computed(() => {
    if (props.actions && props.actions.length > 0) {
        // Filter actions berdasarkan permission dan map dengan icon
        return props.actions
            .filter((action) => action.permission !== false) // Hanya tampilkan jika permission !== false
            .map((action) => ({
            label: action.label,
            action: action.onClick,
                icon: getIconByLabel(action.label),
        }));
    }

    const links: { label: string; action: () => void; icon?: any }[] = [];

    return links;
});
</script>

<template>
    <DropdownMenu v-if="items.length > 0">
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="icon">
                <MoreVertical class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent class="w-40">
            <DropdownMenuItem
                v-for="item in items"
                :key="item.label"
                @click="item.action"
                class="flex items-center gap-2"
                :class="item.label === 'Delete' ? 'text-red-600' : ''"
            >
                <component :is="item.icon" class="h-4 w-4" v-if="item.icon" />
                {{ item.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
