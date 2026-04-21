import PrimaryButton from '@/Components/PrimaryButton';
import FormField from '@/Components/Ui/FormField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    branches: Array<{
        id: string;
        name_en: string;
        name_am?: string | null;
    }>;
};

export default function ComplaintRegister({ branches }: Props) {
    const form = useForm({
        name: '',
        email: '',
        phone: '',
        branch_id: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(route('complaints.register.store'));
    };

    return (
        <GuestLayout>
            <Head title="Complaint Client Registration" />

            <div className="mb-6">
                <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">Complaint portal</p>
                <h1 className="mt-2 text-3xl font-semibold text-[color:var(--text)]">Create a complaint account</h1>
                <p className="mt-3 text-sm leading-7 text-[color:var(--muted-strong)]">
                    Register as a complaint client to submit a complaint and track responses and committee decisions.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Full name" required error={form.errors.name}>
                        <input className="input-ui" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                    </FormField>
                    <FormField label="Email" required error={form.errors.email}>
                        <input type="email" className="input-ui" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} />
                    </FormField>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Phone" optional error={form.errors.phone}>
                        <input className="input-ui" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} />
                    </FormField>
                    <FormField label="Branch" optional error={form.errors.branch_id}>
                        <select className="select-ui" value={form.data.branch_id} onChange={(event) => form.setData('branch_id', event.target.value)}>
                            <option value="">Select branch</option>
                            {branches.map((branch) => (
                                <option key={branch.id} value={branch.id}>
                                    {branch.name_en}
                                </option>
                            ))}
                        </select>
                    </FormField>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Password" required error={form.errors.password}>
                        <input type="password" className="input-ui" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} />
                    </FormField>
                    <FormField label="Confirm password" required error={form.errors.password_confirmation}>
                        <input
                            type="password"
                            className="input-ui"
                            value={form.data.password_confirmation}
                            onChange={(event) => form.setData('password_confirmation', event.target.value)}
                        />
                    </FormField>
                </div>

                <div className="flex items-center justify-between gap-3 border-t border-[color:var(--border)] pt-5">
                    <Link href={route('login')} className="text-sm font-medium text-[color:var(--primary)] underline">
                        Already have an account?
                    </Link>
                    <PrimaryButton disabled={form.processing}>Create account</PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
