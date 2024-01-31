import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {Head, Link, router, useForm} from '@inertiajs/react';
import InputLabel from "@/Components/InputLabel.jsx";
import TextInput from "@/Components/TextInput.jsx";
import InputError from "@/Components/InputError.jsx";
import Checkbox from "@/Components/Checkbox.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import {useEffect} from "react";
import Form from "@/Pages/Words/Form.jsx";

export default function Create({auth, words}) {
    const {data, setData, post, processing, errors, reset} = useForm(words);

    const submit = (e) => {
        e.preventDefault();
        post(route('words.update', words))
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">New Words</h2>}
        >
            <Head title="Words Create"/>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className=" dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className=" dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 text-gray-900 dark:text-gray-100">
                                <Form data={data} processing={processing} setData={setData} errors={errors} submit={submit}/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
