import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';

export default function Edit({ auth, word }) {
    const { data, setData, put, errors } = useForm({
        word: word.word,
        translate: word.translate,
        length: word.length,
        pronunciation: word.pronunciation,
        difficulty_level: word.difficulty_level,
        is_active: word.is_active,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/words/${word.id}`);
    };

    return (
        <MainLayout
            auth={auth}
            header={<h2 className="font-semibold text-xl">Edit Word</h2>}
        >
            <Head title="Edit Word" />

            <div className="py-4">
                <div className="container">
                    <div className="card shadow-sm">
                        <div className="card-body">
                            <form onSubmit={handleSubmit}>
                                <div className="mb-3">
                                    <label className="form-label">Word</label>
                                    <input
                                        type="text"
                                        value={data.word}
                                        onChange={e => setData('word', e.target.value)}
                                        className="form-control"
                                    />
                                    {errors.word && <div className="text-danger">{errors.word}</div>}
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Translate</label>
                                    <input
                                        type="text"
                                        value={data.translate}
                                        onChange={e => setData('translate', e.target.value)}
                                        className="form-control"
                                    />
                                    {errors.translate && <div className="text-danger">{errors.translate}</div>}
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Length</label>
                                    <input
                                        type="number"
                                        value={data.length}
                                        onChange={e => setData('length', e.target.value)}
                                        className="form-control"
                                    />
                                    {errors.length && <div className="text-danger">{errors.length}</div>}
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Pronunciation</label>
                                    <input
                                        type="text"
                                        value={data.pronunciation}
                                        onChange={e => setData('pronunciation', e.target.value)}
                                        className="form-control"
                                    />
                                    {errors.pronunciation && <div className="text-danger">{errors.pronunciation}</div>}
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Difficulty Level</label>
                                    <input
                                        type="number"
                                        value={data.difficulty_level}
                                        onChange={e => setData('difficulty_level', e.target.value)}
                                        className="form-control"
                                    />
                                    {errors.difficulty_level && <div className="text-danger">{errors.difficulty_level}</div>}
                                </div>
                                <div className="mb-3 form-check">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={e => setData('is_active', e.target.checked)}
                                        className="form-check-input"
                                    />
                                    <label className="form-check-label">Is Active</label>
                                    {errors.is_active && <div className="text-danger">{errors.is_active}</div>}
                                </div>
                                <button type="submit" className="btn btn-primary">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
