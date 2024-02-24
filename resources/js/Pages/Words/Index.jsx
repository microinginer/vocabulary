import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {Head, Link} from '@inertiajs/react';
import NavLink from "@/Components/NavLink.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.jsx";
import MainLayout from "@/Layouts/MainLayout.jsx";

export default function Index({auth, words}) {
    return (
        <MainLayout auth={auth}>
            <Head title="Words Page"/>
            <div className="card shadow">
                <div className="card-header">
                    <h1 className="card-title">Words</h1>
                    <div className="card-tools">
                        <Link className={'btn btn-success btn-sm'} href={route('words.create')}>Создать</Link>
                    </div>
                </div>
                <div className="card-body">

                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"></h2>

                        <div className="">
                            <div className="">
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
            </div>

        </MainLayout>
    );
}
