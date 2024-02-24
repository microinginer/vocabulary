import {useEffect} from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import {Head, Link, useForm} from '@inertiajs/react';
import MainLayout from "@/Layouts/MainLayout.jsx";

export default function Register() {
    const {data, setData, post, processing, errors, reset} = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        return () => {
            reset('password', 'password_confirmation');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();

        post(route('register'));
    };

    return (
        <MainLayout>
            <Head title="Register"/>
            <div className="row">
                <div className="col-4 mx-auto">
                    <form onSubmit={submit}>
                        <div className="card mb-4 rounded-3 shadow-sm">
                            <div className="card-header py-3">
                                <h4 className="my-0 fw-normal">Registration</h4>
                            </div>
                            <div className="card-body">
                                <div className={'mb-3'}>
                                    <InputLabel htmlFor="name" className={'form-label'} value="Name"/>

                                    <TextInput
                                        id="name"
                                        name="name"
                                        value={data.name}
                                        className={"form-control " + (errors.name ? 'is-invalid' : '')}
                                        autoComplete="name"
                                        isFocused={true}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                    />

                                    <InputError message={errors.name} className="invalid-feedback"/>
                                </div>

                                <div className="mb-3">
                                    <InputLabel htmlFor="email" className={'form-label'} value="Email"/>

                                    <TextInput
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className={"form-control " + (errors.email ? 'is-invalid' : '')}
                                        autoComplete="username"
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                    />

                                    <InputError message={errors.email} className="invalid-feedback"/>
                                </div>

                                <div className="mb-3">
                                    <InputLabel htmlFor="password" className={'form-label'} value="Password"/>

                                    <TextInput
                                        id="password"
                                        type="password"
                                        name="password"
                                        value={data.password}
                                        className={"form-control " + (errors.password ? 'is-invalid' : '')}
                                        autoComplete="new-password"
                                        onChange={(e) => setData('password', e.target.value)}
                                        required
                                    />

                                    <InputError message={errors.password} className="invalid-feedback"/>
                                </div>

                                <div className="mt-4">
                                    <InputLabel htmlFor="password_confirmation" className={'form-label'}
                                                value="Confirm Password"/>

                                    <TextInput
                                        id="password_confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        value={data.password_confirmation}
                                        className={"form-control " + (errors.password_confirmation ? 'is-invalid' : '')}
                                        autoComplete="new-password"
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        required
                                    />

                                    <InputError message={errors.password_confirmation} className="invalid-feedback"/>
                                </div>

                                <div className="flex items-center justify-end mt-4">
                                    <Link
                                        href={route('login')}
                                        className="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                                    >
                                        Already registered?
                                    </Link>
                                </div>
                            </div>
                            <div className="card-footer">
                                <PrimaryButton className="btn btn-primary" disabled={processing}>
                                    Register
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </MainLayout>
    );
}
