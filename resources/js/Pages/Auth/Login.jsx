import {useEffect} from 'react';
import Checkbox from '@/Components/Checkbox';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import {Head, Link, useForm} from '@inertiajs/react';
import MainLayout from "@/Layouts/MainLayout.jsx";

export default function Login({status, canResetPassword}) {
    const {data, setData, post, processing, errors, reset} = useForm({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();

        post(route('login'));
    };

    return (
        <MainLayout>
            <Head title="Log in"/>
            <div className="row">
                <div className="col-4 mx-auto">
                    <form onSubmit={submit}>
                        <div className="card mb-4 rounded-3 shadow-sm">
                            <div className="card-header py-3">
                                <h4 className="my-0 fw-normal">Login</h4>
                            </div>
                            <div className="card-body">
                                <div className="mb-3">
                                    <InputLabel htmlFor="email" value="Email" className={'form-label'}/>
                                    <TextInput
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className={"form-control " + (errors.email ? 'is-invalid' : '')}
                                        autoComplete="username"
                                        isFocused={true}
                                        onChange={(e) => setData('email', e.target.value)}
                                    />
                                    <InputError message={errors.email} for={'email'} className="invalid-feedback"/>
                                </div>
                                <div className="mb-3">

                                    <InputLabel htmlFor="password" value="Password" className={'form-label'}/>

                                    <TextInput
                                        id="password"
                                        type="password"
                                        name="password"
                                        value={data.password}
                                        className={"form-control " + (errors.password ? 'is-invalid' : '')}
                                        autoComplete="current-password"
                                        onChange={(e) => setData('password', e.target.value)}
                                    />

                                    <InputError message={errors.password} className="invalid-feedback"/>
                                </div>
                                <div className="mb-3 form-check">
                                    <label className="form-check-label">
                                        <Checkbox
                                            name="remember"
                                            className={'form-check-input'}
                                            checked={data.remember}
                                            onChange={(e) => setData('remember', e.target.checked)}
                                        />
                                        <span
                                            className="ms-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                                    </label>
                                    &nbsp;
                                    {canResetPassword && (
                                        <Link
                                            href={route('password.request')}
                                            className="float-right"
                                        >
                                            Forgot your password?
                                        </Link>
                                    )}
                                </div>
                            </div>
                            <div className="card-footer">
                                <PrimaryButton className="btn btn-primary" disabled={processing}>
                                    Log in
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <form onSubmit={submit}>


                <div className="mt-4">
                </div>

                <div className="block mt-4">

                </div>


            </form>
        </MainLayout>
    );
}
