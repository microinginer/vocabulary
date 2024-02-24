export default function InputError({ message, className = '', ...props }) {
    return message ? (
        <div {...props} className={'text-sm text-red-600 dark:text-red-400 ' + className}>
            {message}
        </div>
    ) : null;
}
