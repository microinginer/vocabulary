import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';

export default function Edit({ auth, word }) {
    // Initialize translations from word data or use empty values
    const ruTranslation = word.translations?.find(t => t.language === 'ru')?.translation || '';
    const uzTranslation = word.translations?.find(t => t.language === 'uz')?.translation || '';

    const { data, setData, put, errors, processing } = useForm({
        word: word.word,
        language: word.language || 'en',
        translations: {
            ru: ruTranslation,
            uz: uzTranslation
        },
        length: word.length || '',
        pronunciation: word.pronunciation || '',
        difficulty_level: word.difficulty_level || '1',
        is_active: word.is_active !== undefined ? word.is_active : true,
        sentences: word.sentences || [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/words/${word.id}`, {
            onSuccess: () => {
                // Show success message or redirect
            }
        });
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
                                <div className="row">
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">Word *</label>
                                        <input
                                            type="text"
                                            value={data.word}
                                            onChange={e => setData('word', e.target.value)}
                                            className={`form-control ${errors.word ? 'is-invalid' : ''}`}
                                            required
                                        />
                                        {errors.word && <div className="invalid-feedback">{errors.word}</div>}
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">Language *</label>
                                        <select
                                            value={data.language}
                                            onChange={e => setData('language', e.target.value)}
                                            className={`form-select ${errors.language ? 'is-invalid' : ''}`}
                                            required
                                        >
                                            <option value="en">English (en)</option>
                                            <option value="fr">French (fr)</option>
                                            <option value="de">German (de)</option>
                                            <option value="it">Italian (it)</option>
                                        </select>
                                        {errors.language && <div className="invalid-feedback">{errors.language}</div>}
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">Russian Translation</label>
                                        <input
                                            type="text"
                                            value={data.translations.ru}
                                            onChange={e => setData('translations', {...data.translations, ru: e.target.value})}
                                            className={`form-control ${errors['translations.ru'] ? 'is-invalid' : ''}`}
                                        />
                                        {errors['translations.ru'] && <div className="invalid-feedback">{errors['translations.ru']}</div>}
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">Uzbek Translation</label>
                                        <input
                                            type="text"
                                            value={data.translations.uz}
                                            onChange={e => setData('translations', {...data.translations, uz: e.target.value})}
                                            className={`form-control ${errors['translations.uz'] ? 'is-invalid' : ''}`}
                                        />
                                        {errors['translations.uz'] && <div className="invalid-feedback">{errors['translations.uz']}</div>}
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">Length</label>
                                        <input
                                            type="number"
                                            value={data.length}
                                            onChange={e => setData('length', e.target.value)}
                                            className={`form-control ${errors.length ? 'is-invalid' : ''}`}
                                        />
                                        {errors.length && <div className="invalid-feedback">{errors.length}</div>}
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">Pronunciation</label>
                                        <input
                                            type="text"
                                            value={data.pronunciation}
                                            onChange={e => setData('pronunciation', e.target.value)}
                                            className={`form-control ${errors.pronunciation ? 'is-invalid' : ''}`}
                                        />
                                        {errors.pronunciation && <div className="invalid-feedback">{errors.pronunciation}</div>}
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">Difficulty Level *</label>
                                        <select
                                            value={data.difficulty_level}
                                            onChange={e => setData('difficulty_level', e.target.value)}
                                            className={`form-select ${errors.difficulty_level ? 'is-invalid' : ''}`}
                                            required
                                        >
                                            <option value="1">Beginner</option>
                                            <option value="2">Intermediate</option>
                                            <option value="3">Advanced</option>
                                        </select>
                                        {errors.difficulty_level && <div className="invalid-feedback">{errors.difficulty_level}</div>}
                                    </div>

                                    <div className="col-md-6 mb-3 d-flex align-items-center">
                                        <div className="form-check mt-4">
                                            <input
                                                type="checkbox"
                                                checked={data.is_active}
                                                onChange={e => setData('is_active', e.target.checked)}
                                                className={`form-check-input ${errors.is_active ? 'is-invalid' : ''}`}
                                                id="is_active"
                                            />
                                            <label className="form-check-label" htmlFor="is_active">Is Active</label>
                                            {errors.is_active && <div className="invalid-feedback">{errors.is_active}</div>}
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-4">
                                    <h4>Example Sentences</h4>
                                    {data.sentences.map((sentence, index) => (
                                        <div key={index} className="card mb-3">
                                            <div className="card-body">
                                                <div className="row mb-3">
                                                    <div className="col-md-6">
                                                        <label className="form-label">Sentence</label>
                                                        <input
                                                            type="text"
                                                            value={sentence.content}
                                                            onChange={e => {
                                                                const updatedSentences = [...data.sentences];
                                                                updatedSentences[index].content = e.target.value;
                                                                setData('sentences', updatedSentences);
                                                            }}
                                                            className={`form-control ${errors[`sentences.${index}.content`] ? 'is-invalid' : ''}`}
                                                        />
                                                        {errors[`sentences.${index}.content`] &&
                                                            <div className="invalid-feedback">{errors[`sentences.${index}.content`]}</div>
                                                        }
                                                    </div>
                                                    <div className="col-md-6">
                                                        <label className="form-label">Translation</label>
                                                        <input
                                                            type="text"
                                                            value={sentence.content_translate}
                                                            onChange={e => {
                                                                const updatedSentences = [...data.sentences];
                                                                updatedSentences[index].content_translate = e.target.value;
                                                                setData('sentences', updatedSentences);
                                                            }}
                                                            className={`form-control ${errors[`sentences.${index}.content_translate`] ? 'is-invalid' : ''}`}
                                                        />
                                                        {errors[`sentences.${index}.content_translate`] &&
                                                            <div className="invalid-feedback">{errors[`sentences.${index}.content_translate`]}</div>
                                                        }
                                                    </div>
                                                </div>

                                                <h5>Translations</h5>
                                                {sentence.translations && sentence.translations.map((translation, tIndex) => (
                                                    <div key={tIndex} className="row mb-2">
                                                        <div className="col-md-5">
                                                            <select
                                                                value={translation.language}
                                                                onChange={e => {
                                                                    const updatedSentences = [...data.sentences];
                                                                    updatedSentences[index].translations[tIndex].language = e.target.value;
                                                                    setData('sentences', updatedSentences);
                                                                }}
                                                                className="form-select"
                                                            >
                                                                <option value="ru">Russian (ru)</option>
                                                                <option value="uz">Uzbek (uz)</option>
                                                            </select>
                                                        </div>
                                                        <div className="col-md-5">
                                                            <input
                                                                type="text"
                                                                value={translation.translation}
                                                                onChange={e => {
                                                                    const updatedSentences = [...data.sentences];
                                                                    updatedSentences[index].translations[tIndex].translation = e.target.value;
                                                                    setData('sentences', updatedSentences);
                                                                }}
                                                                className="form-control"
                                                                placeholder="Translation"
                                                            />
                                                        </div>
                                                        <div className="col-md-2">
                                                            <button
                                                                type="button"
                                                                className="btn btn-danger btn-sm"
                                                                onClick={() => {
                                                                    const updatedSentences = [...data.sentences];
                                                                    updatedSentences[index].translations.splice(tIndex, 1);
                                                                    setData('sentences', updatedSentences);
                                                                }}
                                                            >
                                                                Remove
                                                            </button>
                                                        </div>
                                                    </div>
                                                ))}

                                                <div className="mt-2">
                                                    <button
                                                        type="button"
                                                        className="btn btn-secondary btn-sm"
                                                        onClick={() => {
                                                            const updatedSentences = [...data.sentences];
                                                            if (!updatedSentences[index].translations) {
                                                                updatedSentences[index].translations = [];
                                                            }
                                                            updatedSentences[index].translations.push({
                                                                language: 'ru',
                                                                translation: ''
                                                            });
                                                            setData('sentences', updatedSentences);
                                                        }}
                                                    >
                                                        Add Translation
                                                    </button>
                                                    <button
                                                        type="button"
                                                        className="btn btn-danger btn-sm ms-2"
                                                        onClick={() => {
                                                            const updatedSentences = [...data.sentences];
                                                            updatedSentences.splice(index, 1);
                                                            setData('sentences', updatedSentences);
                                                        }}
                                                    >
                                                        Remove Sentence
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}

                                    <button
                                        type="button"
                                        className="btn btn-secondary"
                                        onClick={() => {
                                            setData('sentences', [
                                                ...data.sentences,
                                                {
                                                    content: '',
                                                    content_translate: '',
                                                    translations: []
                                                }
                                            ]);
                                        }}
                                    >
                                        Add Sentence
                                    </button>
                                </div>

                                <div className="d-flex justify-content-between mt-4">
                                    <Link href="/admin/words" className="btn btn-secondary">Cancel</Link>
                                    <button type="submit" className="btn btn-primary" disabled={processing}>
                                        {processing ? 'Updating...' : 'Update Word'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
