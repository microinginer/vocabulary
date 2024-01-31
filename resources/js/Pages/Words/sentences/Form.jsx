import InputLabel from "@/Components/InputLabel.jsx";
import TextInput from "@/Components/TextInput.jsx";
import InputError from "@/Components/InputError.jsx";
import Checkbox from "@/Components/Checkbox.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import TextArea from "@/Components/TextArea.jsx";

export default function Form({submit, setData, data, processing, errors}) {
    return (
        <form onSubmit={submit} className="mt-6 space-y-6">
            <div>
                <InputLabel htmlFor="content" value="Content"/>

                <TextArea
                    id="name"
                    className="mt-1 block w-full"
                    value={data.content}
                    onChange={(e) => setData('content', e.target.value)}
                    required
                    isFocused
                    autoComplete="content"
                />

                <InputError message={errors.content} className="mt-2"/>
            </div>
            <div>
                <InputLabel htmlFor="content_translate" value="Content Translate"/>

                <TextArea
                    id="name"
                    className="mt-1 block w-full"
                    value={data.content_translate}
                    onChange={(e) => setData('content_translate', e.target.value)}
                    required
                    autoComplete="content_translate"
                />

                <InputError message={errors.content_translate} className="mt-2"/>
            </div>

            <div className="flex items-center justify-end mt-4">
                {!processing && (
                    <PrimaryButton className="btn btn-success" disabled={processing}>
                        Save
                    </PrimaryButton>
                )}
            </div>
        </form>
    );
}
