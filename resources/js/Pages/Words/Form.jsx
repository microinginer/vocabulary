import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {Head, Link, router, useForm} from '@inertiajs/react';
import InputLabel from "@/Components/InputLabel.jsx";
import TextInput from "@/Components/TextInput.jsx";
import InputError from "@/Components/InputError.jsx";
import Checkbox from "@/Components/Checkbox.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import {useEffect} from "react";

export default function Form({submit, setData, data, processing, errors, formTitle}) {
    return (
        <form onSubmit={submit} className="mt-6 space-y-6">
            <div className="card card-primary">
                <div className="card-header">
                    <h1 className="card-title">{formTitle}</h1>
                </div>
                <div className="card-body">
                    <div>
                        <InputLabel htmlFor="word" value="Word"/>
                        <TextInput
                            id="name"
                            className={"form-control " + (errors.word ? 'is-invalid' : '')}
                            value={data.word}
                            onChange={(e) => setData('word', e.target.value)}
                            isFocused
                            autoComplete="word"
                        />
                        <InputError message={errors.word} className="invalid-feedback"/>
                    </div>
                    <div>
                        <InputLabel htmlFor="translate" value="Translate"/>
                        <TextInput
                            id="translate"
                            className={"form-control " + (errors.word ? 'is-invalid' : '')}
                            value={data.translate}
                            onChange={(e) => setData('translate', e.target.value)}
                            autoComplete="translate"
                        />
                        <InputError message={errors.word} className="invalid-feedback"/>
                    </div>
                    <div className="block mt-4">
                        <label className="flex items-center">
                            <Checkbox
                                name="is_active"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                            />
                            <span className="ms-2 text-sm text-gray-600 dark:text-gray-400">Activate this word?</span>
                        </label>
                    </div>
                </div>
                <div className="card-footer">
                    {!processing && (
                        <PrimaryButton className="btn btn-success" disabled={processing}>
                            Save
                        </PrimaryButton>
                    )}
                </div>
            </div>
        </form>
    );
}
