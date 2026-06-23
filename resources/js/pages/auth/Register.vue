<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

const roleOptions = [
    {
        value: 'landlord',
        label: 'Landlord',
        description: 'I own or manage properties to rent out.',
    },
    {
        value: 'tenant',
        label: 'Tenant',
        description: 'I rent, or am looking to rent, a place to live.',
    },
] as const;

const selectedRoles = reactive<Record<string, boolean>>({
    landlord: false,
    tenant: true,
});

const checkedRoles = computed(() =>
    roleOptions
        .map((role) => role.value)
        .filter((value) => selectedRoles[value]),
);

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="grid gap-3">
                <Label>I am a…</Label>
                <div
                    v-for="role in roleOptions"
                    :key="role.value"
                    class="flex items-start gap-3 rounded-md border border-input p-3"
                >
                    <Checkbox
                        :id="`role-${role.value}`"
                        v-model="selectedRoles[role.value]"
                        class="mt-0.5"
                    />
                    <div class="grid gap-1 leading-none">
                        <Label
                            :for="`role-${role.value}`"
                            class="font-medium"
                            >{{ role.label }}</Label
                        >
                        <p class="text-sm text-muted-foreground">
                            {{ role.description }}
                        </p>
                    </div>
                </div>
                <input
                    v-for="value in checkedRoles"
                    :key="value"
                    type="hidden"
                    name="roles[]"
                    :value="value"
                />
                <InputError :message="errors.roles" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="6"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
