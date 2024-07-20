import React, { useState, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';

export default function Index({ auth, wordSentences }) {
    const { data, setData, put } = useForm({
        language: '',
        translation: '',
    });
    const [editingTranslation, setEditingTranslation] = useState(null);
    const [currentTranslation, setCurrentTranslation] = useState('');

    const handleEditClick = (e, sentenceId, language, translation) => {
        e.stopPropagation();
        setEditingTranslation({ sentenceId, language });
        setCurrentTranslation(translation);
        setData({
            language,
            translation,
        });
    };

    const handleTranslationChange = (e) => {
        setCurrentTranslation(e.target.value);
        data.translation = e.target.value;
    };

    const handleSave = () => {
        if (editingTranslation) {
            put(`/admin/word-sentences/${editingTranslation.sentenceId}/translation`, {
                preserveScroll: true,
                onSuccess: () => {
                    setEditingTranslation(null);
                    setCurrentTranslation('');
                },
                onError: () => {
                    console.error('Error updating translation');
                },
            });
        }
    };

    const handleClickOutside = (e) => {
        if (editingTranslation && !e.target.closest('.editable-input')) {
            handleSave();
        }
    };

    useEffect(() => {
        document.addEventListener('click', handleClickOutside);
        return () => {
            document.removeEventListener('click', handleClickOutside);
        };
    }, [editingTranslation]);

    return (
        <MainLayout
            auth={auth}
            header={<h2 className="font-semibold text-xl">Word Sentences</h2>}
        >
            <Head title="Word Sentences" />

            <div className="py-4">
                <div className="container">
                    <div className="card shadow-sm">
                        <div className="card-body">
                            {/*<Link href="/admin/word-sentences/create" className="btn btn-primary mb-3">Create New Sentence</Link>*/}
                            <table className="table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Content</th>
                                    <th>Translation (RU)</th>
                                    <th>Translation (UZ)</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                {wordSentences.data.map((sentence) => (
                                    <tr key={sentence.id}>
                                        <td>{sentence.id}</td>
                                        <td>{sentence.content}</td>
                                        <td
                                            onClick={(e) => handleEditClick(e, sentence.id, 'ru', sentence.translations.find(t => t.language === 'ru')?.translation || '')}
                                        >
                                            {editingTranslation?.sentenceId === sentence.id && editingTranslation?.language === 'ru' ? (
                                                <input
                                                    type="text"
                                                    value={currentTranslation}
                                                    onChange={handleTranslationChange}
                                                    className="form-control editable-input"
                                                />
                                            ) : (
                                                <span className="editable">{sentence.translations.find(t => t.language === 'ru')?.translation || 'N/A'}</span>
                                            )}
                                        </td>
                                        <td
                                            onClick={(e) => handleEditClick(e, sentence.id, 'uz', sentence.translations.find(t => t.language === 'uz')?.translation || '')}
                                        >
                                            {editingTranslation?.sentenceId === sentence.id && editingTranslation?.language === 'uz' ? (
                                                <input
                                                    type="text"
                                                    value={currentTranslation}
                                                    onChange={handleTranslationChange}
                                                    className="form-control editable-input"
                                                />
                                            ) : (
                                                <span className="editable">{sentence.translations.find(t => t.language === 'uz')?.translation || 'N/A'}</span>
                                            )}
                                        </td>
                                        <td style={{minWidth: 130}}>
                                            <Link href={`/admin/word-sentences/${sentence.id}/edit`} className="btn btn-sm btn-warning me-2">Edit</Link>
                                            <Link
                                                as="button"
                                                method="delete"
                                                href={`/admin/word-sentences/${sentence.id}`}
                                                className="btn btn-sm btn-danger"
                                                onClick={() => confirm('Are you sure?')}
                                            >
                                                Delete
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                </tbody>
                            </table>

                            <div className="d-flex justify-content-between">
                                <span>Showing {wordSentences.from} to {wordSentences.to} of {wordSentences.total} sentences</span>
                                <nav>
                                    <ul className="pagination">
                                        {wordSentences.links.map((link, index) => (
                                            <li key={index} className={`page-item ${link.active ? 'active' : ''}`}>
                                                <Link className="page-link" href={link.url || '#'} dangerouslySetInnerHTML={{ __html: link.label }} />
                                            </li>
                                        ))}
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
