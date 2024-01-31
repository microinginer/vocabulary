import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {Head, Link} from '@inertiajs/react';
import NavLink from "@/Components/NavLink.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.jsx";
import Create from "@/Pages/Words/sentences/Create.jsx";
import {useEffect} from "react";

export default function Show({auth, words, sentences}) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2
                className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{words.word}</h2>}
            actionButtons={<Link className={'btn btn-success'} href={route('words')}>Назад</Link>}
        >
            <Head title="Word Page"/>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8  dark:bg-gray-800 shadow sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h1 style={{fontSize: '26px'}}>{words.word} - <b>{words.translate}</b></h1>
                        </div>

                    </div>
                    <div className="p-4 sm:p-8  dark:bg-gray-800 shadow sm:rounded-lg">
                        <table className={'table table-hover'}>
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Content</th>
                                <th>Translate</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            {sentences && sentences.map(sentence => (
                                <tr key={sentence.id}>
                                    <td>{sentence.id}</td>
                                    <td>{sentence.content}</td>
                                    <td>{sentence.content_translate}</td>
                                    <td>
                                        <Link href={route('words.sentences.delete', sentence)}
                                              className={'btn btn-danger btn-sm'}>Удалить</Link>
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <Create words={words} auth={auth}/>
        </AuthenticatedLayout>
    );
}
