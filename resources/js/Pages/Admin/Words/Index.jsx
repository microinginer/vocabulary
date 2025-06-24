import React, {useState, useEffect} from 'react';
import {Head, Link, useForm} from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';

export default function Index({auth, words, filters}) {
    console.log(filters)
    const {data, setData, put, get} = useForm({
        language: '',
        translation: '',
        difficulty_level: '',
    });

    const [editingTranslation, setEditingTranslation] = useState(null);
    const [currentTranslation, setCurrentTranslation] = useState('');
    const [editingDifficulty, setEditingDifficulty] = useState(null);
    const [currentDifficulty, setCurrentDifficulty] = useState('');
    const [language, setLanguage] = useState(filters.language || '');
    const [word, setWord] = useState(filters.word || '');

    const handleEditClick = (e, wordId, language, translation) => {
        e.stopPropagation();
        setEditingTranslation({wordId, language});
        setCurrentTranslation(translation);
        setData({
            language,
            translation,
        });
    };

    const handleChangeLanguage = (e) => {
        setLanguage(e.target.value);
        data.language = e.target.value;  // Устанавливаем значение напрямую
        get('/admin/words', {preserveScroll: true});
    }

    const handleSearchByWord = (e) => {
        data.word = word;
        data.language = language;
        get('/admin/words', {preserveScroll: true});
    }

    const handleEditDifficultyClick = (e, wordId, difficulty_level) => {
        e.stopPropagation();
        setEditingDifficulty(wordId);
        setCurrentDifficulty(difficulty_level);
        data.difficulty_level = difficulty_level;  // Устанавливаем значение напрямую
    };

    const handleTranslationChange = (e) => {
        setCurrentTranslation(e.target.value);
        data.translation = e.target.value;  // Устанавливаем значение напрямую
    };

    const handleDifficultyChange = (e) => {
        setCurrentDifficulty(e.target.value);
        data.difficulty_level = e.target.value;  // Устанавливаем значение напрямую
    };

    const handleSave = () => {
        if (editingTranslation) {
            put(`/admin/words/${editingTranslation.wordId}/translation`, {
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

        if (editingDifficulty) {
            put(`/admin/words/${editingDifficulty}/difficulty`, {
                preserveScroll: true,
                data: {
                    difficulty_level: currentDifficulty,
                },
                onSuccess: () => {
                    setEditingDifficulty(null);
                    setCurrentDifficulty('');
                },
                onError: () => {
                    console.error('Error updating difficulty level');
                },
            });
        }
    };

    const handleClickOutside = (e) => {
        if (editingTranslation && !e.target.closest('.editable-input')) {
            handleSave();
        }
        if (editingDifficulty && !e.target.closest('.editable-select')) {
            handleSave();
        }
    };

    useEffect(() => {
        document.addEventListener('click', handleClickOutside);
        return () => {
            document.removeEventListener('click', handleClickOutside);
        };
    }, [editingTranslation, editingDifficulty]);

    return (
        <MainLayout
            auth={auth}
            header={<h2 className="font-semibold text-xl">Words</h2>}
        >
            <Head title="Words"/>

            <div className="py-4">
                <div className="container">
                    <div className="card shadow-sm">
                        <div className="card-body">
                            <div className="row">
                                <div className="col-6">
                                    <div className="mb-3">
                                        <label htmlFor="languageFilter">Filter by Language:</label>
                                        <select
                                            id="languageFilter"
                                            value={language}
                                            onChange={(e) => handleChangeLanguage(e)}
                                            className="form-control">
                                            <option value="">All</option>
                                            <option value="en">English (en)</option>
                                            <option value="fr">French (fr)</option>
                                            <option value="de">German (de)</option>
                                            <option value="it">Italian (it)</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="col-6">
                                    <div className="mb-3">
                                        <label htmlFor="wordFilter">Filter by Word:</label>
                                        <input
                                            id="wordFilter"
                                            type="text"
                                            value={word}
                                            onKeyUp={(e) => e.key === 'Enter' && handleSearchByWord(e)}
                                            onChange={(e) => setWord(e.target.value)}
                                            className="form-control"
                                        />
                                    </div>
                                </div>
                            </div>
                            {/*<Link href="/admin/words/create" className="btn btn-primary mb-3">Create New Word</Link>*/}
                            <table className="table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Target</th>
                                    <th>Word</th>
                                    <th>Translation (RU)</th>
                                    <th>Translation (UZ)</th>
                                    <th>Difficulty Level</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                {words.data.map((word) => (
                                    <tr key={word.id}>
                                        <td>{word.id}</td>
                                        <td>{word.language}</td>
                                        <td>{word.word}</td>
                                        <td
                                            onClick={(e) => handleEditClick(e, word.id, 'ru', word.translations.find(t => t.language === 'ru')?.translation || '')}
                                        >
                                            {editingTranslation?.wordId === word.id && editingTranslation?.language === 'ru' ? (
                                                <input
                                                    type="text"
                                                    value={currentTranslation}
                                                    onChange={handleTranslationChange}
                                                    className="form-control editable-input"
                                                />
                                            ) : (
                                                <span
                                                    className="editable">{word.translations.find(t => t.language === 'ru')?.translation || 'N/A'}</span>
                                            )}
                                        </td>
                                        <td
                                            onClick={(e) => handleEditClick(e, word.id, 'uz', word.translations.find(t => t.language === 'uz')?.translation || '')}
                                        >
                                            {editingTranslation?.wordId === word.id && editingTranslation?.language === 'uz' ? (
                                                <input
                                                    type="text"
                                                    value={currentTranslation}
                                                    onChange={handleTranslationChange}
                                                    className="form-control editable-input"
                                                />
                                            ) : (
                                                <span
                                                    className="editable">{word.translations.find(t => t.language === 'uz')?.translation || 'N/A'}</span>
                                            )}
                                        </td>
                                        <td
                                            onClick={(e) => handleEditDifficultyClick(e, word.id, word.difficulty_level)}
                                        >
                                            {editingDifficulty === word.id ? (
                                                <select
                                                    value={currentDifficulty}
                                                    onChange={handleDifficultyChange}
                                                    className="form-control editable-select"
                                                >
                                                    <option value="1">Beginner</option>
                                                    <option value="2">Intermediate</option>
                                                    <option value="3">Advanced</option>
                                                </select>
                                            ) : (
                                                <span
                                                    className="editable">{["Beginner", "Intermediate", "Advanced"][word.difficulty_level - 1]}</span>
                                            )}
                                        </td>
                                        <td>
                                            <Link href={`/admin/words/${word.id}/edit`}
                                                  className="btn btn-sm btn-warning me-2">Edit</Link>
                                            <Link
                                                as="button"
                                                method="delete"
                                                href={`/admin/words/${word.id}`}
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
                                <span>Showing {words.from} to {words.to} of {words.total} words</span>
                                <nav>
                                    <ul className="pagination">
                                        {words.links.map((link, index) => (
                                            <li key={index} className={`page-item ${link.active ? 'active' : ''}`}>
                                                <Link className="page-link" href={link.url || '#'}
                                                      dangerouslySetInnerHTML={{__html: link.label}}/>
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
