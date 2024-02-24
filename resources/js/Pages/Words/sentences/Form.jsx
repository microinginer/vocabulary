import InputLabel from "@/Components/InputLabel.jsx";
import TextInput from "@/Components/TextInput.jsx";
import InputError from "@/Components/InputError.jsx";
import Checkbox from "@/Components/Checkbox.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import TextArea from "@/Components/TextArea.jsx";

export default function Form({submit, setData, data, processing, errors}) {
    return (
        <form onSubmit={submit} className="mt-6 space-y-6">
            <div className="card">
                <div className="card-header">
                    <h1 className="card-title">Add new sentence</h1>
                </div>
                <div className="card-body">
                    <div className={'form-group'}>
                        <InputLabel htmlFor="content" value="Content"/>
                        <TextArea
                            id="name"
                            className={"form-control " + (errors.content ? 'is-invalid' : '')}
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            isFocused
                            autoComplete="content"
                        />
                        <InputError message={errors.content} className="invalid-feedback"/>
                    </div>
                    <div className={'form-group'}>
                        <InputLabel htmlFor="content_translate" value="Content Translate"/>
                        <TextArea
                            id="name"
                            className={"form-control " + (errors.content_translate ? 'is-invalid' : '')}
                            value={data.content_translate}
                            onChange={(e) => setData('content_translate', e.target.value)}
                            autoComplete="content_translate"
                        />
                        <InputError message={errors.content_translate} className="invalid-feedback"/>
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
