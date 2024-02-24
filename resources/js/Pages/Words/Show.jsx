import {Head, Link} from '@inertiajs/react';
import Create from "@/Pages/Words/sentences/Create.jsx";
import MainLayout from "@/Layouts/MainLayout.jsx";

export default function Show({auth, words, sentences}) {
    return (
        <MainLayout auth={auth}>
            <Head title="Word Page"/>
            <div className="card">
                <div className="card-header">
                    <h1 className="card-title">{words.word} - <b>{words.translate}</b></h1>
                    <div className="card-tools">
                        <Link className={'btn btn-success'} href={route('words')}>Назад</Link>
                    </div>
                </div>
                <div className="card-body">
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
                        {sentences.map(sentence => (
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
            <hr/>
            <Create words={words} auth={auth}/>
        </MainLayout>
    );
}
