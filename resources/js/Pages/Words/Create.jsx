import {Head, useForm} from '@inertiajs/react';
import Form from "@/Pages/Words/Form.jsx";
import MainLayout from "@/Layouts/MainLayout.jsx";

export default function Create({auth, words}) {
    const {data, setData, post, processing, errors, reset} = useForm(words ?? {
        word: '',
        translate: '',
        is_active: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('words.store'))
    };

    return (
        <MainLayout auth={auth}>
            <Head title="Words Create"/>
            <Form data={data} formTitle={'Create Word'} processing={processing} setData={setData} errors={errors} submit={submit}/>
        </MainLayout>
    );
}
