import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {Head, Link} from '@inertiajs/react';
import NavLink from "@/Components/NavLink.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.jsx";

export default function Index({auth, words}) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Words</h2>}
            actionButtons={<Link className={'btn btn-success'} href={route('words.create')}>Создать</Link>}
        >
            <Head title="Words Page"/>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8  dark:bg-gray-800 shadow sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">Welcome to Words page!</div>
                        <table className="table table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Word</th>
                                <th>Translate</th>
                                <th>Is activate?</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            {words.data.map(word => (
                                <tr key={word.id} className={'table-row'}>
                                    <td>{word.id}</td>
                                    <td><Link href={route('words.show', word)}>{word.word}</Link></td>
                                    <td>{word.translate}</td>
                                    <td>{word.is_active}</td>
                                    <td>
                                        <Link className={'btn btn-sm btn-primary'}
                                              href={route('words.edit', word)}>Edit</Link>
                                        <Link className={'btn btn-sm btn-danger'}
                                              href={route('words.delete', word)}>Delete</Link>
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
