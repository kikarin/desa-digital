<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { CreditCard, LoaderCircle, Lock, Mail, User, UserPlus } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    nik: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Buat Akun Baru" description="Isi data Anda di bawah ini untuk membuat akun">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-5">
                <div class="grid gap-2.5">
                    <Label for="name" class="text-sm font-medium">Nama Lengkap</Label>
                    <div class="relative">
                        <User class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="name"
                            v-model="form.name"
                            placeholder="Masukkan nama lengkap"
                            class="h-11 pl-10 transition-all focus-visible:ring-2 focus-visible:ring-primary/20"
                        />
                    </div>
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2.5">
                    <Label for="nik" class="text-sm font-medium">NIK</Label>
                    <div class="relative">
                        <CreditCard class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                        <Input
                            id="nik"
                            type="text"
                            required
                            :tabindex="2"
                            autocomplete="off"
                            v-model="form.nik"
                            placeholder="Masukkan NIK Anda"
                            class="h-11 pl-10 transition-all focus-visible:ring-2 focus-visible:ring-primary/20"
                        />
                    </div>
                    <InputError :message="form.errors.nik" />
                    <p class="text-muted-foreground text-xs">NIK harus terdaftar dalam data warga</p>
                </div>

                <div class="grid gap-2.5">
                    <Label for="email" class="text-sm font-medium">Email</Label>
                    <div class="relative">
                        <Mail class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="3"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="nama@email.com"
                            class="h-11 pl-10 transition-all focus-visible:ring-2 focus-visible:ring-primary/20"
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2.5">
                    <Label for="password" class="text-sm font-medium">Password</Label>
                    <div class="relative">
                        <Lock class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            v-model="form.password"
                            placeholder="Minimal 8 karakter"
                            class="h-11 pl-10 transition-all focus-visible:ring-2 focus-visible:ring-primary/20"
                        />
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2.5">
                    <Label for="password_confirmation" class="text-sm font-medium">Konfirmasi Password</Label>
                    <div class="relative">
                        <Lock class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            :tabindex="5"
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Ulangi password Anda"
                            class="h-11 pl-10 transition-all focus-visible:ring-2 focus-visible:ring-primary/20"
                        />
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 h-11 w-full font-semibold shadow-sm transition-all hover:shadow-md"
                    tabindex="6"
                    :disabled="form.processing"
                >
                    <UserPlus v-if="!form.processing" class="mr-2 h-4 w-4" />
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    <span v-if="!form.processing">Daftar</span>
                    <span v-else>Memproses...</span>
                </Button>
            </div>

            <Separator class="my-2" />

            <div class="text-muted-foreground text-center text-sm">
                Sudah punya akun?
                <TextLink :href="route('login')" class="font-semibold text-primary hover:underline" :tabindex="7">
                    Masuk di sini
                </TextLink>
            </div>
        </form>
    </AuthBase>
</template>
