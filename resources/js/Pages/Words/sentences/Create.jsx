import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {Head, Link, router, useForm} from '@inertiajs/react';
import InputLabel from "@/Components/InputLabel.jsx";
import TextInput from "@/Components/TextInput.jsx";
import InputError from "@/Components/InputError.jsx";
import Checkbox from "@/Components/Checkbox.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import {useEffect} from "react";
import Form from "@/Pages/Words/sentences/Form.jsx";

export default function Create({auth, words}) {
    const {data, setData, post, processing, errors, reset} = useForm({
        content: '',
        content_translate: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('words.sentences.store', words), {
            onSuccess: function (params) {
                reset()
            },
        })
    };

    return (
        <div className="py-12">
            <Form data={data} processing={processing} setData={setData} errors={errors}
                  submit={submit}/>
        </div>
    );
}
