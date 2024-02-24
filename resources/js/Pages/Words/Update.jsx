import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {Head, Link, router, useForm} from '@inertiajs/react';
import InputLabel from "@/Components/InputLabel.jsx";
import TextInput from "@/Components/TextInput.jsx";
import InputError from "@/Components/InputError.jsx";
import Checkbox from "@/Components/Checkbox.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import {useEffect} from "react";
import Form from "@/Pages/Words/Form.jsx";
import MainLayout from "@/Layouts/MainLayout.jsx";

export default function Create({auth, words}) {
    const {data, setData, post, processing, errors, reset} = useForm(words);

    const submit = (e) => {
        e.preventDefault();
        post(route('words.update', words))
    };

    return (
        <MainLayout auth={auth}>
            <Head title="Words Create"/>
            <Form data={data} formTitle={'Update ' + data.word} processing={processing} setData={setData}
                  errors={errors} submit={submit}/>
        </MainLayout>
    );
}
