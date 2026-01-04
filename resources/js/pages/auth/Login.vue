<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, LoaderCircle, Lock, Mail, Sparkles } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="Selamat Datang Kembali" description="Masuk ke akun Anda untuk melanjutkan">
        <Head title="Log in" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div v-if="status" class="flex items-center gap-3 rounded-lg bg-green-50 p-4 text-sm font-medium text-green-800 dark:bg-green-950 dark:text-green-200">
                <Sparkles class="h-5 w-5 shrink-0" />
                <span>{{ status }}</span>
            </div>

            <div class="grid gap-5">
                <div class="grid gap-2.5">
                    <Label for="email" class="text-sm font-medium">Email</Label>
                    <div class="relative">
                        <Mail class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                        <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="nama@email.com"
                            class="h-11 pl-10 transition-all focus-visible:ring-2 focus-visible:ring-primary/20"
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2.5">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-sm font-medium">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs font-medium text-primary hover:underline"
                            :tabindex="5"
                        >
                            Lupa password?
                        </TextLink>
                    </div>
                    <div class="relative">
                        <Lock class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="Masukkan password Anda"
                            class="h-11 pl-10 transition-all focus-visible:ring-2 focus-visible:ring-primary/20"
                        />
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <Label for="remember" class="flex cursor-pointer items-center gap-2.5 text-sm font-normal">
                        <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                        <span class="text-muted-foreground">Ingat saya</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-2 h-11 w-full font-semibold shadow-sm transition-all hover:shadow-md"
                    :tabindex="4"
                    :disabled="form.processing"
                >
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    <span v-if="!form.processing">Masuk</span>
                    <span v-else>Memproses...</span>
                </Button>
            </div>

            <Separator class="my-2" />

            <div class="text-muted-foreground text-center text-sm">
                Belum punya akun?
                <TextLink :href="route('register')" class="font-semibold text-primary hover:underline" :tabindex="5">
                    Daftar sekarang
                </TextLink>
            </div>
        </form>
    </AuthBase>
</template>
